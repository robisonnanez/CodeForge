<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRepositoryRequest;
use App\Jobs\ProvisionRepository;
use App\Models\Repository;
use App\Models\RepositoryMember;
use App\Models\User;
use App\Services\GitService;
use App\Services\RepositoryAuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class RepositoryController extends Controller
{
    public function __construct(
        private readonly GitService $gitService,
        private readonly RepositoryAuthorizationService $authorization,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Repository::query()
            ->with(['owner:id,name,email', 'members.user:id,name,email'])
            ->latest();

        if ($user === null) {
            $query->where('visibility', 'public');
        } elseif (! $user->hasRole('super-admin')) {
            $query->where(function ($scopedQuery) use ($user): void {
                $scopedQuery
                    ->where('visibility', 'public')
                    ->orWhere('user_id', $user->id)
                    ->orWhereHas('members', fn ($membersQuery) => $membersQuery->where('user_id', $user->id));
            });
        }

        $repositories = $query->paginate(min(max($request->integer('per_page', 20), 1), 100));

        return response()->json([
            'repositories' => $repositories->getCollection()
                ->map(fn (Repository $repository) => $this->serializeRepository($repository, $user))
                ->values(),
            'meta' => [
                'current_page' => $repositories->currentPage(),
                'last_page' => $repositories->lastPage(),
                'per_page' => $repositories->perPage(),
                'total' => $repositories->total(),
            ],
        ]);
    }

    public function store(StoreRepositoryRequest $request): JsonResponse
    {
        $this->authorize('create', Repository::class);

        $user = $request->user();
        $slug = $request->filled('slug')
            ? Str::slug((string) $request->string('slug'))
            : Str::slug((string) $request->string('name'));
        $defaultBranch = $request->string('default_branch')->toString() ?: 'main';

        if ($slug === '') {
            throw ValidationException::withMessages([
                'slug' => 'The repository slug is invalid after normalization.',
            ]);
        }

        if (Repository::query()->where('user_id', $user->id)->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages([
                'slug' => 'You already have a repository with this slug.',
            ]);
        }

        $storageUuid = (string) Str::uuid();

        $repository = DB::transaction(function () use ($request, $user, $slug, $defaultBranch, $storageUuid): Repository {
            $repository = Repository::create([
                'user_id' => $user->id,
                'name' => $request->string('name')->toString(),
                'slug' => $slug,
                'description' => $request->string('description')->toString() ?: null,
                'visibility' => $request->string('visibility')->toString(),
                'storage_uuid' => $storageUuid,
                'state' => 'provisioning',
                'default_branch' => $defaultBranch,
            ]);

            RepositoryMember::create([
                'repository_id' => $repository->id,
                'user_id' => $user->id,
                'role' => 'admin',
            ]);

            return $repository;
        });

        ProvisionRepository::dispatch($repository->id);

        activity('codeforge')
            ->causedBy($user)
            ->performedOn($repository)
            ->withProperties([
                'visibility' => $repository->visibility,
                'state' => $repository->state,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'result' => 'allowed',
            ])
            ->log('repository.created');

        return response()->json([
            'message' => 'Repository provisioning started.',
            'repository' => $this->serializeRepository($repository->load('owner:id,name,username,email'), $user),
        ], 202);
    }

    public function show(Request $request, Repository $repository): JsonResponse
    {
        $this->authorize('view', $repository);

        return response()->json([
            'repository' => $this->serializeRepository(
                $repository->load(['owner:id,name,username', 'members.user:id,name,username']),
                $request->user()
            ),
        ]);
    }

    public function destroy(Request $request, Repository $repository): JsonResponse
    {
        $this->authorize('delete', $repository);
        $repository->forceFill(['state' => 'deleting'])->save();

        try {
            $this->gitService->moveToTrash($repository->storage_uuid);
            $repository->forceFill(['state' => 'trashed'])->save();
            $repository->delete();

            activity('codeforge')
                ->causedBy($request->user())
                ->performedOn($repository)
                ->withProperties([
                    'state' => 'trashed',
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'result' => 'allowed',
                ])
                ->log('repository.trashed');
        } catch (Throwable $throwable) {
            $repository->forceFill(['state' => 'error'])->save();

            throw $throwable;
        }

        return response()->json([
            'message' => 'Repository deleted successfully.',
        ]);
    }

    public function files(Request $request, Repository $repository): JsonResponse
    {
        $this->authorize('view', $repository);
        $branch = $request->string('branch')->toString() ?: $repository->default_branch;

        return response()->json([
            'files' => $this->gitService->getFiles($repository->storage_uuid, $branch),
            'branch' => $branch,
        ]);
    }

    public function commits(Request $request, Repository $repository): JsonResponse
    {
        $this->authorize('view', $repository);

        return response()->json([
            'commits' => $this->gitService->getCommits(
                $repository->storage_uuid,
                (int) $request->integer('limit', 20),
                (int) $request->integer('skip', 0)
            ),
        ]);
    }

    public function branches(Repository $repository): JsonResponse
    {
        $this->authorize('view', $repository);

        return response()->json([
            'branches' => $this->gitService->getBranches($repository->storage_uuid),
            'default_branch' => $repository->default_branch,
        ]);
    }

    public function tags(Repository $repository): JsonResponse
    {
        $this->authorize('view', $repository);

        return response()->json(['tags' => $this->gitService->getTags($repository->storage_uuid)]);
    }

    public function tree(Request $request, Repository $repository): JsonResponse
    {
        $this->authorize('view', $repository);
        $revision = $request->string('ref')->toString() ?: $repository->default_branch;

        return response()->json([
            'ref' => $revision,
            'entries' => $this->gitService->getTree(
                $repository->storage_uuid,
                $revision,
                $request->string('path')->toString()
            ),
        ]);
    }

    public function blob(Request $request, Repository $repository): JsonResponse
    {
        $this->authorize('view', $repository);
        $request->validate(['path' => ['required', 'string', 'max:4096']]);

        return response()->json([
            'blob' => $this->gitService->getBlob(
                $repository->storage_uuid,
                $request->string('ref')->toString() ?: $repository->default_branch,
                $request->string('path')->toString()
            ),
        ]);
    }

    public function compare(Request $request, Repository $repository): JsonResponse
    {
        $this->authorize('view', $repository);
        $validated = $request->validate([
            'base' => ['required', 'string', 'max:255'],
            'head' => ['required', 'string', 'max:255'],
        ]);

        return response()->json([
            'base' => $validated['base'],
            'head' => $validated['head'],
            'diff' => $this->gitService->compare(
                $repository->storage_uuid,
                $validated['base'],
                $validated['head']
            ),
        ]);
    }

    public function archive(Request $request, Repository $repository): BinaryFileResponse
    {
        $this->authorize('view', $repository);
        $revision = $request->string('ref')->toString() ?: $repository->default_branch;
        $archive = $this->gitService->createArchive($repository->storage_uuid, $revision);

        return response()
            ->download($archive, "{$repository->slug}-{$revision}.zip", ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend();
    }

    private function serializeRepository(Repository $repository, ?User $viewer): array
    {
        $repository->loadMissing('owner:id,name,username');

        $owner = $repository->owner;
        $ownerSlug = $owner !== null
            ? $owner->username
            : 'user-'.$repository->user_id;
        $sshUrl = $this->gitService->buildSshUrl($ownerSlug, $repository->slug);

        return [
            'id' => $repository->id,
            'name' => $repository->name,
            'namespace' => $ownerSlug,
            'slug' => $repository->slug,
            'description' => $repository->description,
            'visibility' => $repository->visibility,
            'state' => $repository->state,
            'default_branch' => $repository->default_branch,
            'owner' => $owner?->only(['id', 'name', 'username']),
            'created_at' => $repository->created_at,
            'updated_at' => $repository->updated_at,
            'ssh_url' => $sshUrl,
            'https_url' => $this->gitService->buildHttpUrl($ownerSlug, $repository->slug),
            'capabilities' => $this->authorization->capabilities($viewer, $repository),
        ];
    }
}
