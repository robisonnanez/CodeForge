<?php

namespace App\Console\Commands;

use App\Models\Repository;
use App\Models\UserSshKey;
use App\Services\GitService;
use App\Services\RepositoryAuthorizationService;
use Illuminate\Console\Command;

class GitSshGateway extends Command
{
    protected $signature = 'codeforge:git-ssh-gateway {fingerprint}';

    protected $description = 'Authorize and execute a restricted SSH Git operation';

    public function handle(
        GitService $gitService,
        RepositoryAuthorizationService $authorization,
    ): int {
        if (is_file((string) config('codeforge.maintenance_lock_path'))) {
            $this->components->error('CodeForge Git transport is temporarily unavailable.');

            return self::FAILURE;
        }

        $fingerprint = (string) $this->argument('fingerprint');
        $originalCommand = (string) getenv('SSH_ORIGINAL_COMMAND');

        if (! preg_match(
            "/\\A(git-upload-pack|git-receive-pack) '([a-z0-9]+(?:-[a-z0-9]+)*)\\/([a-z0-9]+(?:-[a-z0-9]+)*)\\.git'\\z/",
            $originalCommand,
            $matches
        )) {
            $this->components->error('Only CodeForge Git transport commands are permitted.');

            return self::FAILURE;
        }

        $key = UserSshKey::query()
            ->with('user')
            ->where('fingerprint', $fingerprint)
            ->whereNull('revoked_at')
            ->first();

        if ($key === null || $key->user === null) {
            return self::FAILURE;
        }

        [, $service, $namespace, $slug] = $matches;
        $repository = Repository::query()
            ->where('state', 'ready')
            ->where('slug', $slug)
            ->whereHas('owner', fn ($query) => $query->where('username', $namespace))
            ->first();

        if ($repository === null) {
            return self::FAILURE;
        }

        $requiredOperation = $service === 'git-receive-pack'
            ? RepositoryAuthorizationService::WRITE
            : RepositoryAuthorizationService::READ;

        if (! $authorization->allows($key->user, $repository, $requiredOperation)) {
            activity('git')
                ->causedBy($key->user)
                ->performedOn($repository)
                ->withProperties(['transport' => 'ssh', 'operation' => $requiredOperation, 'result' => 'denied'])
                ->log('git.transport');

            return self::FAILURE;
        }

        $key->forceFill(['last_used_at' => now()])->save();

        activity('git')
            ->causedBy($key->user)
            ->performedOn($repository)
            ->withProperties(['transport' => 'ssh', 'operation' => $requiredOperation, 'result' => 'allowed'])
            ->log('git.transport');

        $subcommand = $service === 'git-receive-pack' ? 'receive-pack' : 'upload-pack';
        $repositoryPath = $gitService->repositoryPath($repository->storage_uuid);

        $arguments = $subcommand === 'upload-pack'
            ? [$subcommand, '--strict', $repositoryPath]
            : [$subcommand, $repositoryPath];

        pcntl_exec('/usr/bin/git', $arguments, [
            'HOME' => '/var/lib/codeforge-git',
            'PATH' => '/usr/bin:/bin',
            'LANG' => 'C.UTF-8',
            'GIT_CONFIG_NOSYSTEM' => '1',
            'GIT_TERMINAL_PROMPT' => '0',
        ]);

        $this->components->error('Git transport execution failed.');

        return self::FAILURE;
    }
}
