<?php

namespace App\Policies;

use App\Models\Repository;
use App\Models\User;
use App\Services\RepositoryAuthorizationService;

class RepositoryPolicy
{
    public function __construct(
        private readonly RepositoryAuthorizationService $authorization,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user !== null;
    }

    public function view(?User $user, Repository $repository): bool
    {
        return $this->authorization->allows($user, $repository, RepositoryAuthorizationService::READ);
    }

    public function create(User $user): bool
    {
        return $user !== null;
    }

    public function update(User $user, Repository $repository): bool
    {
        return $this->authorization->allows($user, $repository, RepositoryAuthorizationService::WRITE);
    }

    public function delete(User $user, Repository $repository): bool
    {
        return $repository->user_id === $user->id || $user->hasRole('super-admin');
    }
}
