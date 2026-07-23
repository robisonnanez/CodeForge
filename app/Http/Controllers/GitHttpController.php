<?php

namespace App\Http\Controllers;

use App\Models\Repository;
use App\Models\User;
use App\Services\GitHttpBackendService;
use App\Services\RepositoryAuthorizationService;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class GitHttpController extends Controller
{
    public function __construct(
        private readonly GitHttpBackendService $backend,
        private readonly RepositoryAuthorizationService $authorization,
    ) {}

    public function __invoke(
        Request $request,
        string $namespace,
        string $repository,
        string $path,
    ): Response {
        abort_if(
            is_file((string) config('codeforge.maintenance_lock_path')),
            503,
            'CodeForge Git transport is temporarily unavailable.'
        );

        $repositoryModel = Repository::query()
            ->where('state', 'ready')
            ->where('slug', $repository)
            ->whereHas('owner', fn ($query) => $query->where('username', $namespace))
            ->firstOrFail();
        $service = $this->resolveService($request, $path);
        $operation = $service === 'git-receive-pack'
            ? RepositoryAuthorizationService::WRITE
            : RepositoryAuthorizationService::READ;
        $actor = $this->authenticate($request, $repositoryModel, $operation);

        $activity = activity('git')
            ->performedOn($repositoryModel)
            ->withProperties([
                'transport' => 'https',
                'operation' => $operation,
                'result' => 'allowed',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

        if ($actor !== null) {
            $activity->causedBy($actor);
        }

        $activity->log('git.transport');

        return $this->backend->response($request, $repositoryModel, $actor, $path, $service);
    }

    private function resolveService(Request $request, string $path): string
    {
        if ($request->isMethod('GET') && $path === 'info/refs') {
            $service = (string) $request->query('service');
        } elseif ($request->isMethod('POST') && in_array($path, ['git-upload-pack', 'git-receive-pack'], true)) {
            $service = $path;
        } else {
            abort(404);
        }

        abort_unless(in_array($service, ['git-upload-pack', 'git-receive-pack'], true), 404);

        return $service;
    }

    private function authenticate(Request $request, Repository $repository, string $operation): ?User
    {
        $plainTextToken = $request->getPassword();

        if (
            ($plainTextToken === null || $plainTextToken === '')
            && $operation === RepositoryAuthorizationService::READ
            && $repository->isPublic()
        ) {
            return null;
        }

        $token = is_string($plainTextToken) && $plainTextToken !== ''
            ? PersonalAccessToken::findToken($plainTextToken)
            : null;
        $actor = $token?->tokenable;
        $requiredAbility = $operation === RepositoryAuthorizationService::WRITE ? 'repo:write' : 'repo:read';
        $tokenIsUsable = $token !== null
            && $actor instanceof User
            && hash_equals($actor->username, (string) $request->getUser())
            && ($token->expires_at === null || $token->expires_at->isFuture())
            && $token->can($requiredAbility);

        abort_unless(
            $tokenIsUsable && $this->authorization->allows($actor, $repository, $operation),
            401,
            'Valid CodeForge credentials are required.',
            ['WWW-Authenticate' => 'Basic realm="CodeForge Git"']
        );

        $token->forceFill(['last_used_at' => now()])->save();

        return $actor;
    }
}
