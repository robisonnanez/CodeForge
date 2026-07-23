<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class GitService
{
    public function __construct(
        private readonly ?string $repositoriesRoot = null,
    ) {}

    public function createBareRepository(string $storageUuid, string $defaultBranch = 'main'): bool
    {
        $resolvedPath = $this->repositoryPath($storageUuid, false);

        if (file_exists($resolvedPath) || is_link($resolvedPath)) {
            throw new RuntimeException('The repository storage location already exists.');
        }

        $branch = $this->validateBranch($defaultBranch);
        $this->run(['git', 'init', '--bare', '--initial-branch='.$branch, '--', $resolvedPath]);

        return true;
    }

    public function getBranches(string $storageUuid): array
    {
        $resolvedPath = $this->repositoryPath($storageUuid);
        $output = $this->runOrNull(['git', "--git-dir={$resolvedPath}", 'for-each-ref', '--format=%(refname:short)', 'refs/heads']);

        return $output === null ? [] : array_values(array_filter(explode("\n", trim($output))));
    }

    public function getCommits(string $storageUuid, int $limit = 20, int $skip = 0): array
    {
        $resolvedPath = $this->repositoryPath($storageUuid);
        $output = $this->runOrNull([
            'git',
            "--git-dir={$resolvedPath}",
            'log',
            '--max-count='.max(1, min($limit, 100)),
            '--skip='.max(0, $skip),
            '--pretty=format:%H|%h|%an|%ae|%ad|%s',
            '--date=iso-strict',
            '--',
        ]);

        if ($output === null || trim($output) === '') {
            return [];
        }

        return collect(explode("\n", trim($output)))
            ->map(function (string $line): array {
                [$hash, $shortHash, $authorName, $authorEmail, $date, $subject] = array_pad(explode('|', $line, 6), 6, '');

                return [
                    'hash' => $hash,
                    'short_hash' => $shortHash,
                    'author_name' => $authorName,
                    'author_email' => $authorEmail,
                    'committed_at' => $date,
                    'message' => $subject,
                ];
            })
            ->all();
    }

    public function getFiles(string $storageUuid, string $branch = 'main'): array
    {
        $resolvedPath = $this->repositoryPath($storageUuid);
        $output = $this->runOrNull([
            'git',
            "--git-dir={$resolvedPath}",
            'ls-tree',
            '-r',
            '--name-only',
            $this->validateBranch($branch),
            '--',
        ]);

        return $output === null ? [] : array_values(array_filter(explode("\n", trim($output))));
    }

    public function getFileContent(string $storageUuid, string $file, string $branch = 'main'): string
    {
        $resolvedPath = $this->repositoryPath($storageUuid);

        return $this->run([
            'git',
            "--git-dir={$resolvedPath}",
            'show',
            $this->validateBranch($branch).':'.$this->sanitizeFilePath($file),
        ]);
    }

    public function moveToTrash(string $storageUuid): string
    {
        $resolvedPath = $this->repositoryPath($storageUuid);
        $trashRoot = $this->getTrashRoot();
        $trashPath = $trashRoot.DIRECTORY_SEPARATOR.$storageUuid.'.git';

        File::ensureDirectoryExists($trashRoot, 0750);

        if (is_link($trashPath) || file_exists($trashPath)) {
            throw new RuntimeException('The repository already exists in trash.');
        }

        if (! rename($resolvedPath, $trashPath)) {
            throw new RuntimeException('The repository could not be moved to trash.');
        }

        return $trashPath;
    }

    public function repositoryPath(string $storageUuid, bool $mustExist = true): string
    {
        $root = $this->getRepositoriesRoot();
        $uuid = $this->validateUuid($storageUuid);
        $path = $root.DIRECTORY_SEPARATOR.$uuid.'.git';

        $rootRealPath = realpath($root);

        if ($rootRealPath === false || is_link($root)) {
            throw new RuntimeException('The repositories root is unavailable or unsafe.');
        }

        if ($mustExist) {
            $pathRealPath = realpath($path);

            if ($pathRealPath === false || is_link($path) || dirname($pathRealPath) !== $rootRealPath) {
                throw new RuntimeException('The repository storage path is unavailable or unsafe.');
            }

            return $pathRealPath;
        }

        if (dirname($path) !== $rootRealPath) {
            throw new RuntimeException('The repository storage path is outside the allowed root.');
        }

        return $path;
    }

    public function buildSshUrl(string $ownerSlug, string $repositorySlug): string
    {
        $owner = $this->sanitizeSlug($ownerSlug);
        $repository = $this->sanitizeSlug($repositorySlug);
        $user = config('codeforge.ssh_user');
        $host = config('codeforge.ssh_host');
        $port = (int) config('codeforge.ssh_port', 2230);

        return sprintf(
            'ssh://%s@%s:%d%s/%s/%s.git',
            $user,
            $host,
            $port,
            '',
            $owner,
            $repository,
        );
    }

    public function buildHttpUrl(string $ownerSlug, string $repositorySlug): ?string
    {
        if (! config('codeforge.http_clone_enabled', false)) {
            return null;
        }

        $owner = $this->sanitizeSlug($ownerSlug);
        $repository = $this->sanitizeSlug($repositorySlug);
        $appUrl = rtrim((string) config('app.url'), '/');

        if ($appUrl === '') {
            return null;
        }

        return sprintf('%s/git/%s/%s.git', $appUrl, $owner, $repository);
    }

    public function buildOwnerSlugForUser(User $user): string
    {
        return $this->sanitizeSlug($user->username);
    }

    private function getRepositoriesRoot(): string
    {
        $root = $this->repositoriesRoot ?? config('codeforge.repositories_root');

        if (! is_string($root) || $root === '') {
            throw new RuntimeException('The CodeForge repositories root is not configured.');
        }

        $root = rtrim($root, DIRECTORY_SEPARATOR);
        File::ensureDirectoryExists($root, 0750);

        return $root;
    }

    private function getTrashRoot(): string
    {
        $root = config('codeforge.repositories_trash_root');

        if (! is_string($root) || $root === '') {
            throw new RuntimeException('The CodeForge repository trash root is not configured.');
        }

        return rtrim($root, DIRECTORY_SEPARATOR);
    }

    private function sanitizeSlug(string $value): string
    {
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value)) {
            throw new RuntimeException('Invalid slug.');
        }

        return $value;
    }

    private function validateBranch(string $branch): string
    {
        if ($branch === '' || strlen($branch) > 255 || str_starts_with($branch, '-')) {
            throw new RuntimeException('Invalid branch name.');
        }

        $this->run(['git', 'check-ref-format', '--branch', $branch]);

        return $branch;
    }

    private function validateUuid(string $uuid): string
    {
        $uuid = strtolower($uuid);

        if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid)) {
            throw new RuntimeException('Invalid repository storage identifier.');
        }

        return $uuid;
    }

    private function sanitizeFilePath(string $filePath): string
    {
        $normalized = ltrim(str_replace('\\', '/', $filePath), '/');

        if ($normalized === '' || str_contains($normalized, '..') || ! preg_match('/^[A-Za-z0-9._\\/-]+$/', $normalized)) {
            throw new RuntimeException('Invalid file path.');
        }

        return $normalized;
    }

    private function run(array $command): string
    {
        $process = new Process($command);
        $process->setTimeout((float) config('codeforge.git_timeout_seconds', 30));
        $process->setIdleTimeout((float) config('codeforge.git_idle_timeout_seconds', 10));
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return trim($process->getOutput());
    }

    private function runOrNull(array $command): ?string
    {
        try {
            return $this->run($command);
        } catch (ProcessFailedException) {
            return null;
        }
    }
}
