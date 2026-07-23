<?php

namespace App\Console\Commands;

use App\Models\Repository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ListenForGitHooks extends Command
{
    protected $signature = 'codeforge:git-hooks:listen';

    protected $description = 'Ingest post-receive events from the protected Unix socket';

    private bool $running = true;

    public function handle(): int
    {
        $socketPath = (string) config('codeforge.hook_socket_path');
        $directory = dirname($socketPath);

        if (! is_dir($directory) && ! mkdir($directory, 0770, true) && ! is_dir($directory)) {
            $this->components->error('Hook socket directory could not be created.');

            return self::FAILURE;
        }

        if (file_exists($socketPath)) {
            unlink($socketPath);
        }

        $server = stream_socket_server("unix://{$socketPath}", $errorNumber, $errorMessage);

        if ($server === false) {
            $this->components->error("Hook socket failed: {$errorNumber} {$errorMessage}");

            return self::FAILURE;
        }

        chmod($socketPath, 0660);
        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, fn () => $this->running = false);
        pcntl_signal(SIGINT, fn () => $this->running = false);

        while ($this->running) {
            $connection = @stream_socket_accept($server, 1);

            if ($connection === false) {
                continue;
            }

            stream_set_timeout($connection, 2);
            $payload = stream_get_contents($connection, 65_537);
            fclose($connection);

            if (is_string($payload) && strlen($payload) <= 65_536) {
                $this->ingest($payload);
            }
        }

        fclose($server);
        @unlink($socketPath);

        return self::SUCCESS;
    }

    private function ingest(string $payload): void
    {
        $event = json_decode($payload, true);

        if (
            ! is_array($event)
            || ! isset($event['storage_uuid'], $event['updates'])
            || ! is_string($event['storage_uuid'])
            || ! is_array($event['updates'])
        ) {
            return;
        }

        $repository = Repository::query()
            ->where('storage_uuid', $event['storage_uuid'])
            ->where('state', 'ready')
            ->first();

        if ($repository === null) {
            return;
        }

        foreach ($event['updates'] as $update) {
            if (
                ! is_array($update)
                || ! preg_match('/^[0-9a-f]{40,64}$/', (string) ($update['old_sha'] ?? ''))
                || ! preg_match('/^[0-9a-f]{40,64}$/', (string) ($update['new_sha'] ?? ''))
                || ! preg_match('#^refs/(heads|tags)/[A-Za-z0-9._/-]+$#', (string) ($update['ref'] ?? ''))
            ) {
                continue;
            }

            $deduplicationKey = hash('sha256', implode('|', [
                $repository->storage_uuid,
                $update['old_sha'],
                $update['new_sha'],
                $update['ref'],
            ]));
            $eventUuid = substr($deduplicationKey, 0, 8).'-'.substr($deduplicationKey, 8, 4).'-4'.substr($deduplicationKey, 13, 3).'-a'.substr($deduplicationKey, 17, 3).'-'.substr($deduplicationKey, 20, 12);

            DB::transaction(function () use ($repository, $update, $eventUuid): void {
                $inserted = DB::table('git_events')->insertOrIgnore([
                    'event_uuid' => $eventUuid,
                    'repository_id' => $repository->id,
                    'ref' => $update['ref'],
                    'old_sha' => $update['old_sha'],
                    'new_sha' => $update['new_sha'],
                    'received_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($inserted === 1) {
                    DB::table('outbox_events')->insert([
                        'event_uuid' => $eventUuid,
                        'event_type' => 'git.ref.updated',
                        'version' => 1,
                        'payload' => json_encode([
                            'repository_id' => $repository->id,
                            'ref' => $update['ref'],
                            'old_sha' => $update['old_sha'],
                            'new_sha' => $update['new_sha'],
                        ], JSON_THROW_ON_ERROR),
                        'available_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
        }
    }
}
