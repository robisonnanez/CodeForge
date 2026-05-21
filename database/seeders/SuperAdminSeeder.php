<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Modulo;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'dashboard.view',
            'apps.calendar.view',
            'apps.chat.view',
            'apps.mail.view',
            'apps.tasklist.view',
            'admin.roles_permissions.manage',
            'admin.user_permissions.manage',
            'settings.passkeys.manage',
            'admin.navigation.manage',
        ];

        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $role = Role::findOrCreate('super-admin', 'web');
        $role->syncPermissions(Permission::all());

        $firstUser = User::query()->first();
        if ($firstUser) {
            $firstUser->assignRole($role);
            $firstUser->syncPermissions([]);
        }

        Modulo::query()->upsert([
            ['idModulos' => 1, 'nmodulo' => 'Principal', 'orden' => 1, 'icono' => 'pi pi-home', 'color' => '#ef7bc3', 'detalle' => 'Modulo principal'],
            ['idModulos' => 2, 'nmodulo' => 'Apps', 'orden' => 2, 'icono' => 'pi pi-th-large', 'color' => '#7c83ff', 'detalle' => 'Aplicaciones'],
            ['idModulos' => 3, 'nmodulo' => 'Admin', 'orden' => 3, 'icono' => 'pi pi-shield', 'color' => '#f59e0b', 'detalle' => 'Administracion'],
        ], ['idModulos'], ['nmodulo', 'orden', 'icono', 'color', 'detalle']);

        $menuRows = [
            ['idModulos' => 1, 'nombre' => 'Dashboard', 'url' => '/dashboard', 'icono' => 'pi pi-home', 'id_menu' => null, 'main' => true, 'orden' => 1, 'cesdo' => true, 'permission_name' => 'dashboard.view'],
            ['idModulos' => 2, 'nombre' => 'Apps', 'url' => '#', 'icono' => 'pi pi-th-large', 'id_menu' => null, 'main' => true, 'orden' => 1, 'cesdo' => true, 'permission_name' => null],
            ['idModulos' => 3, 'nombre' => 'Admin', 'url' => '#', 'icono' => 'pi pi-shield', 'id_menu' => null, 'main' => true, 'orden' => 1, 'cesdo' => true, 'permission_name' => 'admin.roles_permissions.manage'],
        ];

        foreach ($menuRows as $row) {
            Menu::query()->updateOrCreate(['nombre' => $row['nombre'], 'id_menu' => $row['id_menu']], $row);
        }

        $appsParent = Menu::query()->where('nombre', 'Apps')->whereNull('id_menu')->first();
        $adminParent = Menu::query()->where('nombre', 'Admin')->whereNull('id_menu')->first();

        if ($appsParent) {
            $appsChildren = [
                ['idModulos' => 2, 'nombre' => 'Calendar', 'url' => '/apps/calendar', 'icono' => 'pi pi-calendar', 'id_menu' => $appsParent->id, 'main' => false, 'orden' => 1, 'cesdo' => true, 'permission_name' => 'apps.calendar.view'],
                ['idModulos' => 2, 'nombre' => 'Chat', 'url' => '/apps/chat', 'icono' => 'pi pi-comments', 'id_menu' => $appsParent->id, 'main' => false, 'orden' => 2, 'cesdo' => true, 'permission_name' => 'apps.chat.view'],
                ['idModulos' => 2, 'nombre' => 'Mail', 'url' => '/apps/mail/inbox', 'icono' => 'pi pi-envelope', 'id_menu' => $appsParent->id, 'main' => false, 'orden' => 3, 'cesdo' => true, 'permission_name' => 'apps.mail.view'],
                ['idModulos' => 2, 'nombre' => 'Task List', 'url' => '/apps/task-list', 'icono' => 'pi pi-list', 'id_menu' => $appsParent->id, 'main' => false, 'orden' => 4, 'cesdo' => true, 'permission_name' => 'apps.tasklist.view'],
            ];

            foreach ($appsChildren as $row) {
                Menu::query()->updateOrCreate(['nombre' => $row['nombre'], 'id_menu' => $row['id_menu']], $row);
            }
        }

        if ($adminParent) {
            $adminChildren = [
                ['idModulos' => 3, 'nombre' => 'Roles y Permisos', 'url' => '/admin/roles-permissions', 'icono' => 'pi pi-lock', 'id_menu' => $adminParent->id, 'main' => false, 'orden' => 1, 'cesdo' => true, 'permission_name' => 'admin.roles_permissions.manage'],
                ['idModulos' => 3, 'nombre' => 'Permisos por Usuario', 'url' => '/admin/users-permissions', 'icono' => 'pi pi-users', 'id_menu' => $adminParent->id, 'main' => false, 'orden' => 2, 'cesdo' => true, 'permission_name' => 'admin.user_permissions.manage'],
                ['idModulos' => 3, 'nombre' => 'Passkeys', 'url' => '/settings/security', 'icono' => 'pi pi-key', 'id_menu' => $adminParent->id, 'main' => false, 'orden' => 3, 'cesdo' => true, 'permission_name' => 'settings.passkeys.manage'],
                ['idModulos' => 3, 'nombre' => 'Modulos y Menus', 'url' => '/admin/navigation-management', 'icono' => 'pi pi-sitemap', 'id_menu' => $adminParent->id, 'main' => false, 'orden' => 4, 'cesdo' => true, 'permission_name' => 'admin.navigation.manage'],
            ];

            foreach ($adminChildren as $row) {
                Menu::query()->updateOrCreate(['nombre' => $row['nombre'], 'id_menu' => $row['id_menu']], $row);
            }
        }
    }
}
