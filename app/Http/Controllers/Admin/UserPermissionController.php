<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Modulo;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserPermissionController extends Controller
{
    public function index(PermissionSyncService $syncService): Response
    {
        $syncService->syncFromMenu();

        return Inertia::render('admin/users-permissions', [
            'users' => User::query()->with(['roles:id,name', 'permissions:id,name'])->orderBy('name')->get(['id', 'name', 'email'])->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->map(fn ($role) => ['id' => $role->id, 'name' => $role->name])->values(),
                    'permissions' => $user->permissions->map(fn ($permission) => ['id' => $permission->id, 'name' => $permission->name])->values(),
                    'role_permissions' => $user->getPermissionsViaRoles()->pluck('name')->values(),
                ];
            }),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'modules' => Modulo::query()
                ->where('activo', true)
                ->with(['menus' => function ($query): void {
                    $query->where('cesdo', true)
                        ->whereNotNull('permission_name')
                        ->where('permission_name', '!=', '')
                        ->orderBy('orden');
                }])
                ->orderBy('orden')
                ->get(),
        ]);
    }

    public function syncUserRole(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['nullable', 'string', 'exists:roles,name'],
        ]);

        $role = $data['role'] ?? null;
        $user->syncRoles($role ? [$role] : []);

        return back()->with('success', 'Rol de usuario actualizado.');
    }

    public function syncUserPermissions(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $user->syncPermissions($data['permissions'] ?? []);

        return back()->with('success', 'Permisos directos de usuario actualizados.');
    }
}
