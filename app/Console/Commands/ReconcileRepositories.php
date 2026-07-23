<?php

namespace App\Console\Commands;

use App\Models\Repository;
use App\Services\GitService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;

class ReconcileRepositories extends Command
{
    protected $signature = 'codeforge:repositories:reconcile';

    protected $description = 'Reconcile repository database state with Git storage without deleting data';

    public function handle(GitService $gitService): int
    {
        $known = Repository::query()
            ->withTrashed()
            ->pluck('state', 'storage_uuid');
        $missing = 0;

        Repository::query()
            ->where('state', 'ready')
            ->each(function (Repository $repository) use ($gitService, &$missing): void {
                try {
                    $gitService->repositoryPath($repository->storage_uuid);
                } catch (RuntimeException) {
                    $repository->forceFill(['state' => 'error'])->save();
                    $missing++;
                }
            });

        $root = realpath((string) config('codeforge.repositories_root'));
        $orphans = [];

        if ($root !== false && ! is_link($root)) {
            foreach (File::directories($root) as $directory) {
                $name = basename($directory);

                if (str_ends_with($name, '.git')) {
                    $uuid = substr($name, 0, -4);

                    if (! $known->has($uuid)) {
                        $orphans[] = $name;
                    }
                }
            }
        }

        $this->components->info("Missing repositories marked error: {$missing}");
        $this->components->info('Orphan storage objects (not deleted): '.count($orphans));

        foreach ($orphans as $orphan) {
            $this->line($orphan);
        }

        return ($missing === 0 && $orphans === []) ? self::SUCCESS : self::FAILURE;
    }
}
