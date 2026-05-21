<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Modulo;
use App\Services\PermissionSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NavigationAdminController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/navigation-management', [
            'modules' => Modulo::query()->orderBy('orden')->get(),
            'menus' => Menu::query()->orderBy('idModulos')->orderBy('orden')->get(),
            'parentMenus' => Menu::query()->whereNull('id_menu')->orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function storeModule(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'idModulos' => ['required', 'integer', 'unique:modulos,idModulos'],
            'nmodulo' => ['required', 'string', 'max:60'],
            'orden' => ['nullable', 'integer', 'between:0,255'],
            'icono' => ['nullable', 'string', 'max:60'],
            'color' => ['nullable', 'string', 'max:60'],
            'detalle' => ['nullable', 'string', 'max:60'],
            'activo' => ['nullable', 'boolean'],
        ]);

        Modulo::create([
            ...$data,
            'activo' => (bool) ($data['activo'] ?? true),
        ]);

        return back()->with('success', 'Modulo creado correctamente.');
    }

    public function updateModule(Request $request, Modulo $modulo): RedirectResponse
    {
        $data = $request->validate([
            'nmodulo' => ['required', 'string', 'max:60'],
            'orden' => ['nullable', 'integer', 'between:0,255'],
            'icono' => ['nullable', 'string', 'max:60'],
            'color' => ['nullable', 'string', 'max:60'],
            'detalle' => ['nullable', 'string', 'max:60'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $modulo->update([
            ...$data,
            'activo' => (bool) ($data['activo'] ?? true),
        ]);

        return back()->with('success', 'Modulo actualizado.');
    }

    public function destroyModule(Modulo $modulo): RedirectResponse
    {
        if ($modulo->menus()->exists()) {
            return back()->with('error', 'No se puede eliminar el modulo porque tiene menus asociados.');
        }

        $modulo->delete();

        return back()->with('success', 'Modulo eliminado.');
    }

    public function storeMenu(Request $request, PermissionSyncService $syncService): RedirectResponse
    {
        $data = $request->validate([
            'idModulos' => ['required', 'integer', 'exists:modulos,idModulos'],
            'nombre' => ['required', 'string', 'max:120'],
            'url' => ['required', 'string', 'max:255'],
            'icono' => ['nullable', 'string', 'max:60'],
            'id_menu' => ['nullable', 'integer', 'exists:menu,id'],
            'main' => ['nullable', 'boolean'],
            'orden' => ['nullable', 'integer', 'between:0,32767'],
            'cesdo' => ['nullable', 'boolean'],
            'permission_name' => ['nullable', 'string', 'max:120'],
        ]);

        Menu::create([
            ...$data,
            'main' => (bool) ($data['main'] ?? false),
            'cesdo' => (bool) ($data['cesdo'] ?? true),
        ]);

        $syncService->syncFromMenu();

        return back()->with('success', 'Menu creado correctamente.');
    }

    public function updateMenu(Request $request, Menu $menu, PermissionSyncService $syncService): RedirectResponse
    {
        $data = $request->validate([
            'idModulos' => ['required', 'integer', 'exists:modulos,idModulos'],
            'nombre' => ['required', 'string', 'max:120'],
            'url' => ['required', 'string', 'max:255'],
            'icono' => ['nullable', 'string', 'max:60'],
            'id_menu' => ['nullable', 'integer', 'exists:menu,id'],
            'main' => ['nullable', 'boolean'],
            'orden' => ['nullable', 'integer', 'between:0,32767'],
            'cesdo' => ['nullable', 'boolean'],
            'permission_name' => ['nullable', 'string', 'max:120'],
        ]);

        $menu->update([
            ...$data,
            'main' => (bool) ($data['main'] ?? false),
            'cesdo' => (bool) ($data['cesdo'] ?? true),
        ]);

        $syncService->syncFromMenu();

        return back()->with('success', 'Menu actualizado.');
    }

    public function destroyMenu(Menu $menu): RedirectResponse
    {
        if ($menu->children()->exists()) {
            return back()->with('error', 'No se puede eliminar el menu porque tiene submenus.');
        }

        $menu->delete();

        return back()->with('success', 'Menu eliminado.');
    }
}