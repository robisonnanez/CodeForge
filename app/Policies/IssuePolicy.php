<?php

namespace App\Policies;

use App\Models\Issue;
use App\Models\User;

class IssuePolicy
{
    public function view(User $user, Issue $issue): bool
    {
        return $user->can('view', $issue->repository);
    }

    public function create(User $user): bool
    {
        return $user !== null;
    }

    public function update(User $user, Issue $issue): bool
    {
        return $issue->user_id === $user->id || $user->can('update', $issue->repository);
    }

    public function delete(User $user, Issue $issue): bool
    {
        return $issue->user_id === $user->id || $user->can('delete', $issue->repository);
    }
}
