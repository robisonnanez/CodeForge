<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Modulo;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
            'codeforge.view',
            'codeforge.repositories.view',
            'codeforge.repositories.create',
            'codeforge.issues.view',
            'codeforge.ssh_keys.manage',
            'admin.users.manage',
            'admin.roles_permissions.manage',
            'admin.user_permissions.manage',
            'admin.navigation.manage',
        ];

        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $role = Role::findOrCreate('super-admin', 'web');
        $role->syncPermissions(Permission::all());

        $adminEmail = (string) config('codeforge.bootstrap_admin_email');
        $adminPassword = config('codeforge.bootstrap_admin_password');
        $superAdmin = User::query()->where('email', $adminEmail)->first();

        if ($superAdmin === null && is_string($adminPassword) && strlen($adminPassword) >= 16) {
            $superAdmin = User::query()->create([
                'name' => 'Super Admin',
                'username' => 'super-admin',
                'email' => $adminEmail,
                'password' => Hash::make($adminPassword),
                'email_verified_at' => now(),
            ]);
        }

        if ($superAdmin !== null) {
            $superAdmin->syncRoles([$role->name]);
            $superAdmin->syncPermissions([]);
        } elseif ($this->command !== null) {
            $this->command->warn(
                'Bootstrap administrator was not created. Set CODEFORGE_BOOTSTRAP_ADMIN_PASSWORD to a unique value of at least 16 characters.'
            );
        }

        Modulo::query()->upsert([
            ['idModulos' => 1, 'nmodulo' => 'Principal', 'orden' => 1, 'icono' => 'pi pi-home', 'color' => '#ef7bc3', 'detalle' => 'Modulo principal', 'activo' => true],
            ['idModulos' => 2, 'nmodulo' => 'Apps', 'orden' => 2, 'icono' => 'pi pi-th-large', 'color' => '#7c83ff', 'detalle' => 'Aplicaciones', 'activo' => true],
            ['idModulos' => 3, 'nmodulo' => 'Admin', 'orden' => 3, 'icono' => 'pi pi-shield', 'color' => '#f59e0b', 'detalle' => 'Administracion', 'activo' => true],
            ['idModulos' => 4, 'nmodulo' => 'CodeForge', 'orden' => 4, 'icono' => 'pi pi-github', 'color' => '#f97316', 'detalle' => 'Mini Git hosting', 'activo' => true],
        ], ['idModulos'], ['nmodulo', 'orden', 'icono', 'color', 'detalle', 'activo']);

        foreach ([1, 2, 3, 4] as $moduleId) {
            Permission::findOrCreate("module.{$moduleId}.access", 'web');
        }

        $role->syncPermissions(Permission::all());

        $menuRows = [
            ['idModulos' => 1, 'nombre' => 'Dashboard', 'url' => '/dashboard', 'icono' => 'pi pi-home', 'id_menu' => null, 'main' => true, 'orden' => 1, 'cesdo' => true, 'permission_name' => 'dashboard.view'],
            ['idModulos' => 2, 'nombre' => 'Apps', 'url' => '#', 'icono' => 'pi pi-th-large', 'id_menu' => null, 'main' => true, 'orden' => 1, 'cesdo' => true, 'permission_name' => null],
            ['idModulos' => 3, 'nombre' => 'Admin', 'url' => '#', 'icono' => 'pi pi-shield', 'id_menu' => null, 'main' => true, 'orden' => 1, 'cesdo' => true, 'permission_name' => 'admin.roles_permissions.manage'],
            ['idModulos' => 4, 'nombre' => 'CodeForge', 'url' => '/codeforge/repositories', 'icono' => 'pi pi-github', 'id_menu' => null, 'main' => true, 'orden' => 1, 'cesdo' => true, 'permission_name' => 'codeforge.repositories.view'],
        ];

        foreach ($menuRows as $row) {
            Menu::query()->updateOrCreate(['nombre' => $row['nombre'], 'id_menu' => $row['id_menu']], $row);
        }

        $appsParent = Menu::query()->where('nombre', 'Apps')->whereNull('id_menu')->first();
        $adminParent = Menu::query()->where('nombre', 'Admin')->whereNull('id_menu')->first();
        $codeForgeParent = Menu::query()->where('nombre', 'CodeForge')->whereNull('id_menu')->first();

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
                ['idModulos' => 3, 'nombre' => 'Usuarios', 'url' => '/admin/users', 'icono' => 'pi pi-user', 'id_menu' => $adminParent->id, 'main' => false, 'orden' => 2, 'cesdo' => true, 'permission_name' => 'admin.users.manage'],
                ['idModulos' => 3, 'nombre' => 'Permisos por Usuario', 'url' => '/admin/users-permissions', 'icono' => 'pi pi-users', 'id_menu' => $adminParent->id, 'main' => false, 'orden' => 3, 'cesdo' => true, 'permission_name' => 'admin.user_permissions.manage'],
                ['idModulos' => 3, 'nombre' => 'Modulos y Menus', 'url' => '/admin/navigation-management', 'icono' => 'pi pi-sitemap', 'id_menu' => $adminParent->id, 'main' => false, 'orden' => 4, 'cesdo' => true, 'permission_name' => 'admin.navigation.manage'],
            ];

            foreach ($adminChildren as $row) {
                Menu::query()->updateOrCreate(['nombre' => $row['nombre'], 'id_menu' => $row['id_menu']], $row);
            }
        }

        if ($codeForgeParent) {
            $codeForgeChildren = [
                ['idModulos' => 4, 'nombre' => 'Repositories', 'url' => '/codeforge/repositories', 'icono' => 'pi pi-folder-open', 'id_menu' => $codeForgeParent->id, 'main' => false, 'orden' => 1, 'cesdo' => true, 'permission_name' => 'codeforge.repositories.view'],
                ['idModulos' => 4, 'nombre' => 'New Repository', 'url' => '/codeforge/repositories/new', 'icono' => 'pi pi-plus-circle', 'id_menu' => $codeForgeParent->id, 'main' => false, 'orden' => 2, 'cesdo' => true, 'permission_name' => 'codeforge.repositories.create'],
                ['idModulos' => 4, 'nombre' => 'SSH Keys', 'url' => '/codeforge/ssh-keys', 'icono' => 'pi pi-key', 'id_menu' => $codeForgeParent->id, 'main' => false, 'orden' => 3, 'cesdo' => true, 'permission_name' => 'codeforge.ssh_keys.manage'],
            ];

            foreach ($codeForgeChildren as $row) {
                Menu::query()->updateOrCreate(['nombre' => $row['nombre'], 'id_menu' => $row['id_menu']], $row);
            }

            Menu::query()
                ->where('id_menu', $codeForgeParent->id)
                ->where('nombre', 'Overview')
                ->delete();
        }

        Permission::query()->where('name', 'settings.passkeys.manage')->delete();
        Menu::query()->where('permission_name', 'settings.passkeys.manage')->orWhere('nombre', 'Passkeys')->delete();
    }
}
