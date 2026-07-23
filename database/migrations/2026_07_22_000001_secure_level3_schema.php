<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('username', 64)->nullable()->after('id');
            });

            DB::table('users')
                ->select(['id', 'name', 'email'])
                ->orderBy('id')
                ->each(function (object $user): void {
                    $base = Str::slug((string) ($user->name ?: Str::before($user->email, '@')));
                    $base = $base !== '' ? Str::limit($base, 52, '') : 'user';

                    DB::table('users')
                        ->where('id', $user->id)
                        ->whereNull('username')
                        ->update(['username' => "{$base}-{$user->id}"]);
                });

            Schema::table('users', function (Blueprint $table): void {
                $table->string('username', 64)->nullable(false)->change();
                $table->unique('username');
            });
        }

        if (Schema::hasColumn('users', 'password_text')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('password_text');
            });
        }

        if (Schema::hasTable('activity_log')) {
            DB::table('activity_log')
                ->whereRaw('CAST(properties AS TEXT) LIKE ?', ['%password_text%'])
                ->delete();
        }

        if (! Schema::hasColumn('repositories', 'storage_uuid')) {
            Schema::table('repositories', function (Blueprint $table): void {
                $table->uuid('storage_uuid')->nullable();
                $table->string('state', 20)->default('provisioning');
                $table->softDeletes();
            });

            DB::table('repositories')
                ->select('id')
                ->orderBy('id')
                ->each(fn (object $repository) => DB::table('repositories')
                    ->where('id', $repository->id)
                    ->update(['storage_uuid' => (string) Str::uuid(), 'state' => 'ready']));

            Schema::table('repositories', function (Blueprint $table): void {
                $table->uuid('storage_uuid')->nullable(false)->change();
                $table->unique('storage_uuid');
                $table->dropColumn(['disk_path', 'ssh_url']);
            });
        }

        if (! Schema::hasColumn('user_ssh_keys', 'fingerprint')) {
            Schema::table('user_ssh_keys', function (Blueprint $table): void {
                $table->string('fingerprint', 100)->nullable();
                $table->string('algorithm', 40)->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('revoked_at')->nullable()->index();
            });

            DB::table('user_ssh_keys')
                ->select(['id', 'public_key'])
                ->orderBy('id')
                ->each(function (object $sshKey): void {
                    [$algorithm, $encodedKey] = array_pad(
                        preg_split('/\s+/', trim((string) $sshKey->public_key), 3) ?: [],
                        3,
                        ''
                    );
                    $decodedKey = base64_decode($encodedKey, true);
                    $fingerprint = $decodedKey === false
                        ? 'invalid:'.$sshKey->id
                        : 'SHA256:'.rtrim(base64_encode(hash('sha256', $decodedKey, true)), '=');

                    DB::table('user_ssh_keys')->where('id', $sshKey->id)->update([
                        'algorithm' => $algorithm !== '' ? $algorithm : 'unknown',
                        'fingerprint' => $fingerprint,
                    ]);
                });

            Schema::table('user_ssh_keys', function (Blueprint $table): void {
                $table->string('fingerprint', 100)->nullable(false)->change();
                $table->string('algorithm', 40)->nullable(false)->change();
                $table->unique('fingerprint');
            });
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE repositories ADD CONSTRAINT repositories_visibility_check CHECK (visibility IN ('public', 'private'))");
            DB::statement("ALTER TABLE repositories ADD CONSTRAINT repositories_state_check CHECK (state IN ('provisioning', 'ready', 'deleting', 'trashed', 'error'))");
            DB::statement("ALTER TABLE repository_members ADD CONSTRAINT repository_members_role_check CHECK (role IN ('read', 'write', 'admin'))");
        }
    }

    public function down(): void
    {
        throw new RuntimeException('The security migration cannot be rolled back safely.');
    }
};
