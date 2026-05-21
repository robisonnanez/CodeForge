<?php

namespace App\Http\Middleware;

use App\Models\Menu;
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
        $navigation = [];

        if ($user) {
            $rows = Menu::query()
                ->where('cesdo', true)
                ->whereHas('modulo', fn ($query) => $query->where('activo', true))
                ->orderBy('idModulos')
                ->orderByRaw('COALESCE(orden, 9999) asc')
                ->get();

            $allowed = $rows->filter(function (Menu $item) use ($user) {
                $modulePermission = PermissionSyncService::modulePermissionName((int) $item->idModulos);

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
            'auth' => ['user' => $user],
            'navigation' => $navigation,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}