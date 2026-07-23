import { Head, router, usePage } from '@inertiajs/react';
import { Dropdown } from 'primereact/dropdown';
import { useMemo, useState } from 'react';

type MenuEntry = {
    id: number;
    nombre: string;
    permission_name?: string | null;
};
type ModuleEntry = { idModulos: number; nmodulo: string; menus: MenuEntry[] };
type RoleEntry = {
    id: number;
    name: string;
    permissions: Array<{ id: number; name: string }>;
};

const modulePermissionName = (moduleId: number) => `module.${moduleId}.access`;

export default function RolesPermissionsPage() {
    const page = usePage<{ roles: RoleEntry[]; modules: ModuleEntry[] }>();
    const [roleName, setRoleName] = useState('');
    const [roleId, setRoleId] = useState<number>(page.props.roles[0]?.id ?? 0);
    const [roleOverrides, setRoleOverrides] = useState<
        Record<number, Set<string>>
    >({});

    const role = useMemo(() => {
        return page.props.roles.find((entry) => entry.id === roleId) ?? null;
    }, [page.props.roles, roleId]);

    const rolePermissionNames = useMemo(() => {
        return new Set(
            role?.permissions.map((permission) => permission.name) ?? [],
        );
    }, [role]);

    const selectedPermissions = useMemo(() => {
        if (!role) {
            return new Set<string>();
        }

        return roleOverrides[role.id] ?? rolePermissionNames;
    }, [role, roleOverrides, rolePermissionNames]);
    const isSuperAdminRole = role?.name === 'super-admin';

    const persistPermissions = (next: Set<string>) => {
        if (!role) {
            return;
        }

        setRoleOverrides((previous) => ({
            ...previous,
            [role.id]: new Set(next),
        }));

        router.post(`/admin/roles-permissions/${role.id}/permissions`, {
            permissions: Array.from(next),
        });
    };

    const togglePermission = (permissionName: string, enabled: boolean) => {
        const next = new Set(selectedPermissions);

        if (enabled) {
            next.add(permissionName);
        } else {
            next.delete(permissionName);
        }

        persistPermissions(next);
    };

    const toggleModule = (module: ModuleEntry, enabled: boolean) => {
        const next = new Set(selectedPermissions);
        const modulePermission = modulePermissionName(module.idModulos);

        if (enabled) {
            next.add(modulePermission);

            module.menus
                .map((menu) => menu.permission_name)
                .filter((permission): permission is string => !!permission)
                .forEach((permission) => next.add(permission));
        } else {
            next.delete(modulePermission);

            module.menus
                .map((menu) => menu.permission_name)
                .filter((permission): permission is string => !!permission)
                .forEach((permission) => next.delete(permission));
        }

        persistPermissions(next);
    };

    return (
        <>
            <Head title="Roles y Permisos" />
            <div className="space-y-4">
                <section className="atlantis-card atlantis-dark-card p-4">
                    <h2 className="mb-2 text-xl font-semibold text-white">
                        Roles y permisos
                    </h2>
                    <div className="grid gap-3 md:grid-cols-[1fr,220px,140px]">
                        <input
                            className="p-inputtext p-component"
                            value={roleName}
                            placeholder="Nombre del rol"
                            onChange={(event) =>
                                setRoleName(event.target.value)
                            }
                        />
                        <Dropdown
                            value={roleId}
                            options={page.props.roles.map((entry) => ({
                                label: entry.name,
                                value: entry.id,
                            }))}
                            onChange={(event) => setRoleId(Number(event.value))}
                            placeholder="Selecciona rol"
                            className="w-full"
                        />
                        <button
                            type="button"
                            className="atlantis-pink-btn"
                            onClick={() => {
                                const trimmedRoleName = roleName.trim();

                                if (!trimmedRoleName) {
                                    return;
                                }

                                router.post(
                                    '/admin/roles-permissions/roles',
                                    { name: trimmedRoleName },
                                    {
                                        onSuccess: (page) => {
                                            setRoleName('');

                                            const createdRole = (
                                                page.props.roles as
                                                    RoleEntry[] | undefined
                                            )?.find(
                                                (entry) =>
                                                    entry.name ===
                                                    trimmedRoleName,
                                            );

                                            if (createdRole) {
                                                setRoleId(createdRole.id);
                                            }
                                        },
                                    },
                                );
                            }}
                        >
                            Crear rol
                        </button>
                    </div>
                    {isSuperAdminRole ? (
                        <p className="mt-3 text-sm text-amber-300">
                            El rol <strong>super-admin</strong> siempre conserva
                            acceso total. Usa otro rol para probar visibilidad
                            real del menu.
                        </p>
                    ) : null}
                </section>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {page.props.modules.map((module) => {
                        const modulePermission = modulePermissionName(
                            module.idModulos,
                        );
                        const moduleEnabled =
                            selectedPermissions.has(modulePermission);

                        return (
                            <section
                                key={module.idModulos}
                                className="atlantis-card atlantis-perm-card p-3"
                            >
                                <div className="mb-2 flex items-center justify-between gap-2">
                                    <p className="text-sm font-semibold tracking-wide text-slate-300 uppercase">
                                        {module.nmodulo}
                                    </p>
                                    <label className="flex items-center gap-2 text-[11px] text-slate-300">
                                        <input
                                            type="checkbox"
                                            checked={moduleEnabled}
                                            disabled={isSuperAdminRole}
                                            onChange={(event) =>
                                                toggleModule(
                                                    module,
                                                    event.target.checked,
                                                )
                                            }
                                        />
                                        Acceso al modulo
                                    </label>
                                </div>
                                <div className="space-y-2">
                                    {module.menus.map((menu) => {
                                        const permissionName =
                                            menu.permission_name ?? '';
                                        const checked = permissionName
                                            ? selectedPermissions.has(
                                                  permissionName,
                                              )
                                            : false;

                                        return (
                                            <label
                                                key={menu.id}
                                                className="atlantis-switch-row"
                                            >
                                                <input
                                                    type="checkbox"
                                                    className="atlantis-switch"
                                                    checked={checked}
                                                    disabled={
                                                        !permissionName ||
                                                        !role ||
                                                        !moduleEnabled ||
                                                        isSuperAdminRole
                                                    }
                                                    onChange={(event) => {
                                                        if (!permissionName) {
                                                            return;
                                                        }

                                                        if (!moduleEnabled) {
                                                            const next =
                                                                new Set(
                                                                    selectedPermissions,
                                                                );
                                                            next.add(
                                                                modulePermission,
                                                            );
                                                            next.add(
                                                                permissionName,
                                                            );
                                                            persistPermissions(
                                                                next,
                                                            );

                                                            return;
                                                        }

                                                        togglePermission(
                                                            permissionName,
                                                            event.target
                                                                .checked,
                                                        );
                                                    }}
                                                />
                                                <span className="atlantis-switch-label">
                                                    {menu.nombre}
                                                </span>
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
