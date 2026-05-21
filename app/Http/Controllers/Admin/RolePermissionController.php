<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Modulo;
use App\Services\PermissionSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function index(PermissionSyncService $syncService): Response
    {
        $syncService->syncFromMenu();

        $roles = Role::query()->with('permissions:id,name')->orderBy('name')->get(['id', 'name']);
        $modules = Modulo::query()
            ->where('activo', true)
            ->with(['menus' => function ($query): void {
                $query->where('cesdo', true)
                    ->whereNotNull('permission_name')
                    ->where('permission_name', '!=', '')
                    ->orderBy('orden');
            }])
            ->orderBy('orden')
            ->get();

        return Inertia::render('admin/roles-permissions', [
            'roles' => $roles,
            'modules' => $modules,
        ]);
    }

    public function storeRole(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:roles,name'],
        ]);

        Role::create(['name' => $data['name'], 'guard_name' => 'web']);

        return back()->with('success', 'Rol creado correctamente.');
    }

    public function syncRolePermissions(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->syncPermissions($data['permissions'] ?? []);

        return back()->with('success', 'Permisos de rol actualizados.');
    }
}