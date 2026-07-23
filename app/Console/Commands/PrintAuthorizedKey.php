<?php

namespace App\Console\Commands;

use App\Models\UserSshKey;
use Illuminate\Console\Command;

class PrintAuthorizedKey extends Command
{
    protected $signature = 'codeforge:ssh-authorized-key {fingerprint}';

    protected $description = 'Print a restricted authorized_keys entry for a CodeForge fingerprint';

    public function handle(): int
    {
        $fingerprint = (string) $this->argument('fingerprint');

        if (! preg_match('/^SHA256:[A-Za-z0-9+\/_-]{20,90}$/', $fingerprint)) {
            return self::FAILURE;
        }

        $key = UserSshKey::query()
            ->where('fingerprint', $fingerprint)
            ->whereNull('revoked_at')
            ->first();

        if ($key === null) {
            return self::FAILURE;
        }

        $this->line(sprintf(
            'restrict,command="/usr/local/bin/codeforge-git-shell %s" %s',
            $fingerprint,
            trim($key->public_key)
        ));

        return self::SUCCESS;
    }
}
