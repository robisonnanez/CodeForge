import { Head, router, usePage } from "@inertiajs/react";
import { useMemo, useState } from "react";

type UserEntry = {
    id: number;
    name: string;
    email: string;
    roles: Array<{ id: number; name: string }>;
    permissions: Array<{ id: number; name: string }>;
    role_permissions: string[];
};
type RoleEntry = { id: number; name: string };
type MenuEntry = { id: number; nombre: string; permission_name?: string | null };
type ModuleEntry = { idModulos: number; nmodulo: string; menus: MenuEntry[] };

const modulePermissionName = (moduleId: number) => `module.${moduleId}.access`;

export default function UsersPermissionsPage() {
    const page = usePage<{ users: UserEntry[]; roles: RoleEntry[]; modules: ModuleEntry[] }>();
    const [userId, setUserId] = useState<number>(page.props.users[0]?.id ?? 0);
    const [overrides, setOverrides] = useState<Record<number, Set<string>>>({});

    const user = useMemo(() => {
        return page.props.users.find((entry) => entry.id === userId) ?? null;
    }, [page.props.users, userId]);

    const selectedDirect = useMemo(() => {
        if (!user) {
            return new Set<string>();
        }

        return overrides[user.id] ?? new Set(user.permissions.map((permission) => permission.name));
    }, [user, overrides]);

    const rolePermissionNames = new Set(user?.role_permissions ?? []);

    const persistDirectPermissions = (next: Set<string>) => {
        if (!user) {
            return;
        }

        setOverrides((previous) => ({
            ...previous,
            [user.id]: new Set(next),
        }));

        router.post(`/admin/users-permissions/${user.id}/permissions`, {
            permissions: Array.from(next),
        });
    };

    const toggleModule = (module: ModuleEntry, enabled: boolean) => {
        const next = new Set(selectedDirect);
        const modulePermission = modulePermissionName(module.idModulos);

        if (enabled) {
            next.add(modulePermission);
        } else {
            next.delete(modulePermission);

            module.menus
                .map((menu) => menu.permission_name)
                .filter((permission): permission is string => !!permission)
                .forEach((permission) => next.delete(permission));
        }

        persistDirectPermissions(next);
    };

    if (!user) {
        return null;
    }

    return (
        <>
            <Head title="Permisos por Usuario" />
            <div className="space-y-4">
                <section className="atlantis-card atlantis-dark-card p-4">
                    <h2 className="mb-2 text-xl font-semibold text-white">Permisos por usuario</h2>
                    <div className="grid gap-3 md:grid-cols-2">
                        <select className="p-inputtext p-component" value={user.id} onChange={(event) => setUserId(Number(event.target.value))}>
                            {page.props.users.map((entry) => (
                                <option key={entry.id} value={entry.id}>
                                    {entry.name} ({entry.email})
                                </option>
                            ))}
                        </select>
                        <select
                            className="p-inputtext p-component"
                            value={user.roles[0]?.name ?? ""}
                            onChange={(event) => router.post(`/admin/users-permissions/${user.id}/role`, { role: event.target.value || null })}
                        >
                            <option value="">Sin rol</option>
                            {page.props.roles.map((entry) => (
                                <option key={entry.id} value={entry.name}>
                                    {entry.name}
                                </option>
                            ))}
                        </select>
                    </div>
                </section>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {page.props.modules.map((module) => {
                        const modulePermission = modulePermissionName(module.idModulos);
                        const moduleEnabled = selectedDirect.has(modulePermission) || rolePermissionNames.has(modulePermission);

                        return (
                            <section key={module.idModulos} className="atlantis-card atlantis-perm-card p-3">
                                <div className="mb-2 flex items-center justify-between gap-2">
                                    <p className="text-sm font-semibold uppercase tracking-wide text-slate-300">{module.nmodulo}</p>
                                    <label className="flex items-center gap-2 text-[11px] text-slate-300">
                                        <input
                                            type="checkbox"
                                            checked={moduleEnabled}
                                            onChange={(event) => toggleModule(module, event.target.checked)}
                                        />
                                        Modulo activo
                                    </label>
                                </div>
                                <div className="space-y-2">
                                    {module.menus.map((menu) => {
                                        const permissionName = menu.permission_name ?? "";
                                        const isDirect = permissionName ? selectedDirect.has(permissionName) : false;
                                        const isInherited = permissionName ? rolePermissionNames.has(permissionName) && !isDirect : false;

                                        return (
                                            <label key={menu.id} className="atlantis-switch-row">
                                                <input
                                                    type="checkbox"
                                                    className="atlantis-switch"
                                                    checked={isDirect}
                                                    disabled={!permissionName || !moduleEnabled}
                                                    onChange={(event) => {
                                                        if (!permissionName) {
                                                            return;
                                                        }

                                                        const next = new Set(selectedDirect);

                                                        if (event.target.checked) {
                                                            next.add(permissionName);
                                                            next.add(modulePermission);
                                                        } else {
                                                            next.delete(permissionName);
                                                        }

                                                        persistDirectPermissions(next);
                                                    }}
                                                />
                                                <span className="atlantis-switch-label">{menu.nombre}</span>
                                                {isInherited && <small className="text-[11px] text-slate-400">Rol</small>}
                                                {isDirect && <small className="text-[11px] text-emerald-400">Directo</small>}
                                            </label>
                                        );
                                    })}
                                </div>
                            </section>
                        );
                    })}
                </div>
            </div>
        </>
    );
}