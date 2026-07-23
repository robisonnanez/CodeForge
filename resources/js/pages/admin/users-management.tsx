import { Head, router, usePage } from '@inertiajs/react';
import { Button } from 'primereact/button';
import { Column } from 'primereact/column';
import { DataTable } from 'primereact/datatable';
import { Dialog } from 'primereact/dialog';
import { Dropdown } from 'primereact/dropdown';
import { InputText } from 'primereact/inputtext';
import { Toast } from 'primereact/toast';
import { useRef, useState } from 'react';

type UserRow = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    roles: Array<{ id: number; name: string }>;
};

type RoleRow = {
    id: number;
    name: string;
};

type UserForm = {
    id?: number;
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    role: string;
};

const emptyForm: UserForm = {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: '',
};

export default function UsersManagementPage() {
    const page = usePage<{
        users: UserRow[];
        roles: RoleRow[];
        authUserId: number;
    }>();
    const toast = useRef<Toast>(null);
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editingUser, setEditingUser] = useState<UserForm>(emptyForm);

    const showToast = (severity: 'success' | 'warn', detail: string) => {
        toast.current?.show({
            severity,
            summary: severity === 'success' ? 'OK' : 'Validacion',
            detail,
            life: 2500,
        });
    };

    const openCreate = () => {
        setEditingUser(emptyForm);
        setDialogOpen(true);
    };

    const openEdit = (user: UserRow) => {
        setEditingUser({
            id: user.id,
            name: user.name,
            email: user.email,
            password: '',
            password_confirmation: '',
            role: user.roles[0]?.name ?? '',
        });
        setDialogOpen(true);
    };

    const submit = () => {
        if (!editingUser.name.trim() || !editingUser.email.trim()) {
            showToast('warn', 'Completa nombre y correo.');

            return;
        }

        if (!editingUser.id && !editingUser.password) {
            showToast(
                'warn',
                'La contrasena es obligatoria para un usuario nuevo.',
            );

            return;
        }

        const payload = {
            name: editingUser.name.trim(),
            email: editingUser.email.trim(),
            password: editingUser.password || undefined,
            password_confirmation:
                editingUser.password_confirmation || undefined,
            role: editingUser.role || null,
        };

        if (editingUser.id) {
            router.put(`/admin/users/${editingUser.id}`, payload, {
                onSuccess: () => showToast('success', 'Usuario actualizado'),
            });
        } else {
            router.post('/admin/users', payload, {
                onSuccess: () => showToast('success', 'Usuario creado'),
            });
        }

        setDialogOpen(false);
        setEditingUser(emptyForm);
    };

    return (
        <>
            <Head title="Usuarios" />
            <Toast ref={toast} position="top-right" />
            <section className="atlantis-card atlantis-dark-card p-4">
                <div className="mb-3 flex items-center justify-between">
                    <h2 className="text-xl font-semibold text-white">
                        CRUD Usuarios
                    </h2>
                    <Button
                        label="Nuevo Usuario"
                        icon="pi pi-plus"
                        className="atlantis-pink-btn"
                        onClick={openCreate}
                    />
                </div>

                <DataTable
                    value={page.props.users}
                    dataKey="id"
                    stripedRows
                    size="small"
                    className="p-datatable-sm"
                >
                    <Column field="name" header="Nombre" />
                    <Column field="email" header="Correo" />
                    <Column
                        header="Rol"
                        body={(row: UserRow) => row.roles[0]?.name ?? 'Sin rol'}
                    />
                    <Column
                        header="Verificado"
                        body={(row: UserRow) =>
                            row.email_verified_at ? 'Si' : 'No'
                        }
                    />
                    <Column
                        header="Creado"
                        body={(row: UserRow) =>
                            new Date(row.created_at).toLocaleString()
                        }
                    />
                    <Column
                        header="Acciones"
                        body={(row: UserRow) => (
                            <div className="flex flex-wrap gap-2">
                                <Button
                                    icon="pi pi-user-edit"
                                    text
                                    onClick={() => openEdit(row)}
                                />
                                <Button
                                    icon="pi pi-sign-in"
                                    text
                                    rounded
                                    tooltip="Entrar como este usuario"
                                    tooltipOptions={{ position: 'top' }}
                                    aria-label="Entrar como este usuario"
                                    onClick={() =>
                                        router.post(
                                            `/admin/users-permissions/${row.id}/impersonate`,
                                        )
                                    }
                                />
                                <Button
                                    icon="pi pi-trash"
                                    text
                                    severity="danger"
                                    disabled={row.id === page.props.authUserId}
                                    onClick={() => {
                                        router.delete(
                                            `/admin/users/${row.id}`,
                                            {
                                                onSuccess: () =>
                                                    showToast(
                                                        'success',
                                                        'Usuario eliminado',
                                                    ),
                                            },
                                        );
                                    }}
                                />
                            </div>
                        )}
                    />
                </DataTable>
            </section>

            <Dialog
                header={editingUser.id ? 'Editar Usuario' : 'Nuevo Usuario'}
                visible={dialogOpen}
                onHide={() => setDialogOpen(false)}
                className="w-full max-w-2xl"
            >
                <div className="grid gap-3 md:grid-cols-2">
                    <InputText
                        value={editingUser.name}
                        placeholder="Nombre"
                        onChange={(event) =>
                            setEditingUser((prev) => ({
                                ...prev,
                                name: event.target.value,
                            }))
                        }
                    />
                    <InputText
                        value={editingUser.email}
                        placeholder="Correo"
                        onChange={(event) =>
                            setEditingUser((prev) => ({
                                ...prev,
                                email: event.target.value,
                            }))
                        }
                    />
                    <Dropdown
                        value={editingUser.role}
                        options={[
                            { label: 'Sin rol', value: '' },
                            ...page.props.roles.map((role) => ({
                                label: role.name,
                                value: role.name,
                            })),
                        ]}
                        onChange={(event) =>
                            setEditingUser((prev) => ({
                                ...prev,
                                role: event.value,
                            }))
                        }
                        placeholder="Selecciona rol"
                        className="w-full"
                    />
                    <div />
                    <InputText
                        type="password"
                        value={editingUser.password}
                        placeholder={
                            editingUser.id
                                ? 'Nueva contrasena opcional'
                                : 'Contrasena'
                        }
                        onChange={(event) =>
                            setEditingUser((prev) => ({
                                ...prev,
                                password: event.target.value,
                            }))
                        }
                    />
                    <InputText
                        type="password"
                        value={editingUser.password_confirmation}
                        placeholder="Confirmar contrasena"
                        onChange={(event) =>
                            setEditingUser((prev) => ({
                                ...prev,
                                password_confirmation: event.target.value,
                            }))
                        }
                    />
                </div>

                <div className="mt-4 flex justify-end gap-2">
                    <Button
                        label="Cancelar"
                        text
                        onClick={() => setDialogOpen(false)}
                    />
                    <Button label="Guardar" onClick={submit} />
                </div>
            </Dialog>
        </>
    );
}
