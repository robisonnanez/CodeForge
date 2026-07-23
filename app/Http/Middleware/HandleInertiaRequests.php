<?php

namespace App\Http\Middleware;

use App\Models\Menu;
use App\Models\User;
use App\Services\PermissionSyncService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();
        $impersonator = null;
        $navigation = [];
        $welcomeMessage = null;

        if ($request->session()->has('impersonator_id')) {
            $impersonatorId = (int) $request->session()->get('impersonator_id');
            $impersonator = $impersonatorId > 0
                ? User::query()->find($impersonatorId)
                : null;
        }

        if ($user) {
            $welcomedUserId = (int) $request->session()->get('welcomed_user_id', 0);

            if ($welcomedUserId !== (int) $user->id) {
                $welcomeMessage = "Bienvenido, {$user->name}.";
                $request->session()->put('welcomed_user_id', (int) $user->id);
            }
        }

        if ($user) {
            $rows = Menu::query()
                ->where('cesdo', true)
                ->when(
                    ! config('codeforge.demo_features_enabled'),
                    fn ($query) => $query->where('idModulos', '!=', 2)
                )
                ->whereHas('modulo', fn ($query) => $query->where('activo', true))
                ->orderBy('idModulos')
                ->orderByRaw('COALESCE(orden, 9999) asc')
                ->get();

            $directPermissions = $user->getDirectPermissions()->pluck('name')->flip();

            $moduleDirectOverrides = $rows
                ->groupBy('idModulos')
                ->map(function ($menus, $moduleId) use ($directPermissions) {
                    $modulePermission = PermissionSyncService::modulePermissionName((int) $moduleId);

                    if ($directPermissions->has($modulePermission)) {
                        return true;
                    }

                    return $menus
                        ->pluck('permission_name')
                        ->filter()
                        ->contains(fn ($permission) => $directPermissions->has($permission));
                });

            $allowed = $rows->filter(function (Menu $item) use ($user, $directPermissions, $moduleDirectOverrides) {
                $moduleId = (int) $item->idModulos;
                $modulePermission = PermissionSyncService::modulePermissionName($moduleId);
                $hasDirectOverride = (bool) $moduleDirectOverrides->get($moduleId, false);

                if ($hasDirectOverride) {
                    if (! $directPermissions->has($modulePermission)) {
                        return false;
                    }

                    if (! $item->permission_name) {
                        return true;
                    }

                    return $directPermissions->has($item->permission_name);
                }

                if (! $user->can($modulePermission)) {
                    return false;
                }

                if (! $item->permission_name) {
                    return true;
                }

                return $user->can($item->permission_name);
            });

            $tree = $allowed
                ->whereNull('id_menu')
                ->map(function (Menu $item) use ($allowed) {
                    $children = $allowed
                        ->where('id_menu', $item->id)
                        ->sortBy(fn (Menu $menu) => $menu->orden ?? 9999)
                        ->values()
                        ->map(fn (Menu $menu) => [
                            'id' => $menu->id,
                            'label' => $menu->nombre,
                            'href' => $menu->url,
                            'icon' => $menu->icono,
                        ])
                        ->all();

                    return [
                        'id' => $item->id,
                        'label' => $item->nombre,
                        'href' => $item->url,
                        'icon' => $item->icono,
                        'children' => $children,
                    ];
                })
                ->values()
                ->all();

            $navigation = collect($tree)->map(function (array $item) {
                if ($item['href'] === '/apps/mail' || $item['href'] === 'apps/mail') {
                    $item['children'] = [
                        ['id' => ($item['id'] * 100) + 1, 'label' => 'Inbox', 'href' => '/apps/mail/inbox', 'icon' => 'pi pi-inbox'],
                        ['id' => ($item['id'] * 100) + 2, 'label' => 'Compose', 'href' => '/apps/mail/compose', 'icon' => 'pi pi-pencil'],
                        ['id' => ($item['id'] * 100) + 3, 'label' => 'Detail', 'href' => '/apps/mail/detail/1000', 'icon' => 'pi pi-comment'],
                    ];
                }

                return $item;
            })->values()->all();

            if ($user->hasRole('super-admin')) {
                $hasNavigationCrud = collect($navigation)->contains(function (array $item) {
                    if (($item['href'] ?? null) === '/admin/navigation-management') {
                        return true;
                    }

                    return collect($item['children'] ?? [])->contains(fn (array $child) => ($child['href'] ?? null) === '/admin/navigation-management');
                });

                if (! $hasNavigationCrud) {
                    $adminIndex = collect($navigation)->search(fn (array $item) => strtolower((string) ($item['label'] ?? '')) === 'admin');
                    $entry = [
                        'id' => 990001,
                        'label' => 'Navegacion',
                        'href' => '/admin/navigation-management',
                        'icon' => 'pi pi-sitemap',
                    ];

                    if ($adminIndex !== false) {
                        $children = $navigation[$adminIndex]['children'] ?? [];
                        $children[] = $entry;
                        $navigation[$adminIndex]['children'] = array_values($children);
                    } else {
                        $navigation[] = [
                            'id' => 990000,
                            'label' => 'Admin',
                            'href' => '/admin',
                            'icon' => 'pi pi-shield',
                            'children' => [$entry],
                        ];
                    }
                }
            }
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
                'impersonator' => $impersonator ? [
                    'id' => $impersonator->id,
                    'name' => $impersonator->name,
                    'email' => $impersonator->email,
                ] : null,
                'isImpersonating' => $impersonator !== null,
                'welcomeMessage' => $welcomeMessage,
            ],
            'navigation' => $navigation,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
