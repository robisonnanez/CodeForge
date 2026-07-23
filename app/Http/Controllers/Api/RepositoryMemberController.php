<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Repository;
use App\Models\RepositoryMember;
use App\Models\User;
use App\Services\RepositoryAuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RepositoryMemberController extends Controller
{
    public function __construct(
        private readonly RepositoryAuthorizationService $authorization,
    ) {}

    public function index(Request $request, Repository $repository): JsonResponse
    {
        $this->ensureAdmin($request, $repository);

        return response()->json([
            'members' => $repository->members()
                ->with('user:id,name,username')
                ->orderBy('id')
                ->paginate(min(max($request->integer('per_page', 20), 1), 100)),
        ]);
    }

    public function store(Request $request, Repository $repository): JsonResponse
    {
        $this->ensureAdmin($request, $repository);
        $validated = $request->validate([
            'username' => ['required', 'string', Rule::exists('users', 'username')],
            'role' => ['required', Rule::in(['read', 'write', 'admin'])],
        ]);
        $user = User::query()->where('username', $validated['username'])->firstOrFail();

        abort_if($user->id === $repository->user_id, 422, 'The repository owner is not a member entry.');

        $member = RepositoryMember::query()->updateOrCreate(
            ['repository_id' => $repository->id, 'user_id' => $user->id],
            ['role' => $validated['role']]
        );

        activity('security')
            ->causedBy($request->user())
            ->performedOn($repository)
            ->withProperties(['member_user_id' => $user->id, 'role' => $member->role, 'result' => 'allowed'])
            ->log('repository.member.updated');

        return response()->json(['member' => $member->load('user:id,name,username')], 201);
    }

    public function update(
        Request $request,
        Repository $repository,
        RepositoryMember $member,
    ): JsonResponse {
        $this->ensureAdmin($request, $repository);
        abort_unless($member->repository_id === $repository->id, 404);
        $validated = $request->validate(['role' => ['required', Rule::in(['read', 'write', 'admin'])]]);
        $member->update(['role' => $validated['role']]);

        activity('security')
            ->causedBy($request->user())
            ->performedOn($repository)
            ->withProperties(['member_user_id' => $member->user_id, 'role' => $member->role, 'result' => 'allowed'])
            ->log('repository.member.updated');

        return response()->json(['member' => $member->load('user:id,name,username')]);
    }

    public function destroy(
        Request $request,
        Repository $repository,
        RepositoryMember $member,
    ): JsonResponse {
        $this->ensureAdmin($request, $repository);
        abort_unless($member->repository_id === $repository->id, 404);
        $memberUserId = $member->user_id;
        $member->delete();

        activity('security')
            ->causedBy($request->user())
            ->performedOn($repository)
            ->withProperties(['member_user_id' => $memberUserId, 'result' => 'allowed'])
            ->log('repository.member.removed');

        return response()->json(['message' => 'Repository member removed.']);
    }

    private function ensureAdmin(Request $request, Repository $repository): void
    {
        abort_unless(
            $this->authorization->allows($request->user(), $repository, RepositoryAuthorizationService::ADMIN),
            403
        );
    }
}
