<?php

namespace App\Console\Commands;

use App\Services\PermissionSyncService;
use Illuminate\Console\Command;

class SyncPermissionsFromMenu extends Command
{
    protected $signature = 'permissions:sync-from-menu';

    protected $description = 'Sync permission names from menu.permission_name into spatie permissions table';

    public function handle(PermissionSyncService $service): int
    {
        $created = $service->syncFromMenu();
        $this->info("Permissions synced. Newly created: {$created}");

        return self::SUCCESS;
    }
}
