<?php

namespace App\Jobs;

use App\Models\Repository;
use App\Services\GitService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Throwable;

class ProvisionRepository implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $uniqueFor = 300;

    public function __construct(
        public readonly int $repositoryId,
    ) {
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return (string) $this->repositoryId;
    }

    public function handle(GitService $gitService): void
    {
        Cache::lock("codeforge:repository:{$this->repositoryId}", 60)->block(10, function () use ($gitService): void {
            $repository = Repository::query()->findOrFail($this->repositoryId);

            if ($repository->state === 'ready') {
                return;
            }

            try {
                $gitService->createBareRepository($repository->storage_uuid, $repository->default_branch);
                $repository->forceFill(['state' => 'ready'])->save();
            } catch (Throwable $throwable) {
                $repository->forceFill(['state' => 'error'])->save();

                throw $throwable;
            }
        });
    }
}
