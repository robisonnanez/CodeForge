<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\Modulo;
use Illuminate\Support\Collection;
use App\Models\Permission;

class PermissionSyncService
{
    public static function modulePermissionName(int $moduleId): string
    {
        return "module.{$moduleId}.access";
    }

    public function syncFromMenu(): int
    {
        $menuPermissionNames = Menu::query()
            ->whereNotNull('permission_name')
            ->where('permission_name', '!=', '')
            ->pluck('permission_name');

        $modulePermissionNames = Modulo::query()
            ->pluck('idModulos')
            ->map(fn (int $id) => self::modulePermissionName($id));

        $permissionNames = $menuPermissionNames
            ->merge($modulePermissionNames)
            ->unique()
            ->values();

        $created = 0;

        foreach ($permissionNames as $name) {
            $permission = Permission::findOrCreate($name, 'web');

            if ($permission->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }
}
