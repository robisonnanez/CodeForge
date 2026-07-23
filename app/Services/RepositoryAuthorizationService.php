<?php

namespace App\Services;

use App\Models\Repository;
use App\Models\User;

class RepositoryAuthorizationService
{
    public const READ = 'read';

    public const WRITE = 'write';

    public const ADMIN = 'admin';

    public function roleFor(?User $user, Repository $repository): ?string
    {
        if ($user?->hasRole('super-admin')) {
            return self::ADMIN;
        }

        if ($user !== null && $repository->user_id === $user->id) {
            return self::ADMIN;
        }

        if ($user !== null) {
            $membership = $repository->members()
                ->where('user_id', $user->id)
                ->value('role');

            if (in_array($membership, [self::READ, self::WRITE, self::ADMIN], true)) {
                return $membership;
            }
        }

        return $repository->isPublic() ? self::READ : null;
    }

    public function allows(?User $user, Repository $repository, string $operation): bool
    {
        $role = $this->roleFor($user, $repository);

        return match ($operation) {
            self::READ => $role !== null,
            self::WRITE => in_array($role, [self::WRITE, self::ADMIN], true),
            self::ADMIN => $role === self::ADMIN,
            default => false,
        };
    }

    /**
     * @return array{read: bool, write: bool, admin: bool, delete: bool}
     */
    public function capabilities(?User $user, Repository $repository): array
    {
        return [
            'read' => $this->allows($user, $repository, self::READ),
            'write' => $this->allows($user, $repository, self::WRITE),
            'admin' => $this->allows($user, $repository, self::ADMIN),
            'delete' => $user !== null
                && ($repository->user_id === $user->id || $user->hasRole('super-admin')),
        ];
    }
}
