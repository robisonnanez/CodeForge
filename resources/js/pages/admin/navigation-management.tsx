import { Head, router, usePage } from "@inertiajs/react";
import { Button } from "primereact/button";
import { Column } from "primereact/column";
import { DataTable } from "primereact/datatable";
import { Dialog } from "primereact/dialog";
import { InputSwitch } from "primereact/inputswitch";
import { InputText } from "primereact/inputtext";
import { Toast } from "primereact/toast";
import { useMemo, useRef, useState } from "react";

type ModuleRow = {
    idModulos: number;
    nmodulo: string;
    orden?: number | null;
    icono?: string | null;
    color?: string | null;
    detalle?: string | null;
    activo: boolean;
};

type MenuRow = {
    id: number;
    idModulos: number;
    nombre: string;
    url: string;
    icono?: string | null;
    id_menu?: number | null;
    main: boolean;
    orden?: number | null;
    cesdo: boolean;
    permission_name?: string | null;
};

type ParentMenu = { id: number; nombre: string };

const emptyModule: ModuleRow = {
    idModulos: 0,
    nmodulo: "",
    orden: null,
    icono: "",
    color: "",
    detalle: "",
    activo: true,
};

const emptyMenu: Omit<MenuRow, "id"> = {
    idModulos: 0,
    nombre: "",
    url: "",
    icono: "",
    id_menu: null,
    main: false,
    orden: null,
    cesdo: true,
    permission_name: "",
};

export default function NavigationManagementPage() {
    const toast = useRef<Toast>(null);
    const page = usePage<{ modules: ModuleRow[]; menus: MenuRow[]; parentMenus: ParentMenu[] }>();
    const [moduleDialog, setModuleDialog] = useState(false);
    const [menuDialog, setMenuDialog] = useState(false);
    const [editingModule, setEditingModule] = useState<ModuleRow>(emptyModule);
    const [editingMenu, setEditingMenu] = useState<Omit<MenuRow, "id"> & { id?: number }>(emptyMenu);

    const moduleMap = useMemo(() => {
        return new Map(page.props.modules.map((mod) => [mod.idModulos, mod.nmodulo]));
    }, [page.props.modules]);

    const showToast = (severity: "success" | "warn", detail: string) => {
        toast.current?.show({
            severity,
            summary: severity === "success" ? "OK" : "Validacion",
            detail,
            life: 2500,
        });
    };

    const submitModule = () => {
        if (editingModule.idModulos === 0 || !editingModule.nmodulo.trim()) {
            showToast("warn", "Completa ID y nombre del modulo");

            return;
        }

        const payload = { ...editingModule, nmodulo: editingModule.nmodulo.trim() };

        if (page.props.modules.some((module) => module.idModulos === editingModule.idModulos)) {
            router.put(`/admin/navigation/modules/${editingModule.idModulos}`, payload, {
                onSuccess: () => showToast("success", "Modulo actualizado"),
            });
        } else {
            router.post("/admin/navigation/modules", payload, {
                onSuccess: () => showToast("success", "Modulo creado"),
            });
        }

        setModuleDialog(false);
        setEditingModule(emptyModule);
    };

    const submitMenu = () => {
        if (!editingMenu.idModulos || !editingMenu.nombre.trim() || !editingMenu.url.trim()) {
            showToast("warn", "Completa modulo, nombre y URL");

            return;
        }

        const payload = {
            ...editingMenu,
            nombre: editingMenu.nombre.trim(),
            url: editingMenu.url.trim(),
        };

        if (editingMenu.id) {
            router.put(`/admin/navigation/menus/${editingMenu.id}`, payload, {
                onSuccess: () => showToast("success", "Menu actualizado"),
            });
        } else {
            router.post("/admin/navigation/menus", payload, {
                onSuccess: () => showToast("success", "Menu creado"),
            });
        }

        setMenuDialog(false);
        setEditingMenu(emptyMenu);
    };

    return (
        <>
            <Head title="Gestion de Navegacion" />
            <Toast ref={toast} position="top-right" />
            <div className="space-y-4">
                <section className="atlantis-card atlantis-dark-card p-4">
                    <div className="mb-3 flex items-center justify-between">
                        <h2 className="text-xl font-semibold text-white">CRUD Modulos</h2>
                        <Button
                            label="Nuevo Modulo"
                            icon="pi pi-plus"
                            className="atlantis-pink-btn"
                            onClick={() => {
                                setEditingModule(emptyModule);
                                setModuleDialog(true);
                            }}
                        />
                    </div>
                    <DataTable value={page.props.modules} dataKey="idModulos" stripedRows size="small" className="p-datatable-sm">
                        <Column field="idModulos" header="ID" />
                        <Column field="nmodulo" header="Modulo" />
                        <Column field="orden" header="Orden" />
                        <Column field="icono" header="Icono" />
                        <Column field="color" header="Color" />
                        <Column field="detalle" header="Detalle" />
                        <Column
                            header="Activo"
                            body={(row: ModuleRow) => (row.activo ? "Si" : "No")}
                        />
                        <Column
                            header="Acciones"
                            body={(row: ModuleRow) => (
                                <div className="flex gap-2">
                                    <Button
                                        icon="pi pi-pencil"
                                        text
                                        onClick={() => {
                                            setEditingModule(row);
                                            setModuleDialog(true);
                                        }}
                                    />
                                    <Button
                                        icon="pi pi-trash"
                                        text
                                        severity="danger"
                                        onClick={() => {
                                            router.delete(`/admin/navigation/modules/${row.idModulos}`, {
                                                onSuccess: () => showToast("success", "Modulo eliminado"),
                                            });
                                        }}
                                    />
                                </div>
                            )}
                        />
                    </DataTable>
                </section>

                <section className="atlantis-card atlantis-dark-card p-4">
                    <div className="mb-3 flex items-center justify-between">
                        <h2 className="text-xl font-semibold text-white">CRUD Menu</h2>
                        <Button
                            label="Nuevo Menu"
                            icon="pi pi-plus"
                            className="atlantis-pink-btn"
                            onClick={() => {
                                setEditingMenu(emptyMenu);
                                setMenuDialog(true);
                            }}
                        />
                    </div>
                    <DataTable value={page.props.menus} dataKey="id" stripedRows size="small" className="p-datatable-sm">
                        <Column field="id" header="ID" />
                        <Column header="Modulo" body={(row: MenuRow) => moduleMap.get(row.idModulos) ?? row.idModulos} />
                        <Column field="nombre" header="Nombre" />
                        <Column field="url" header="URL" />
                        <Column field="permission_name" header="Permiso" />
                        <Column header="Activo" body={(row: MenuRow) => (row.cesdo ? "Si" : "No")} />
                        <Column
                            header="Acciones"
                            body={(row: MenuRow) => (
                                <div className="flex gap-2">
                                    <Button
                                        icon="pi pi-pencil"
                                        text
                                        onClick={() => {
                                            setEditingMenu(row);
                                            setMenuDialog(true);
                                        }}
                                    />
                                    <Button
                                        icon="pi pi-trash"
                                        text
                                        severity="danger"
                                        onClick={() => {
                                            router.delete(`/admin/navigation/menus/${row.id}`, {
                                                onSuccess: () => showToast("success", "Menu eliminado"),
                                            });
                                        }}
                                    />
                                </div>
                            )}
                        />
                    </DataTable>
                </section>
            </div>

            <Dialog header="Modulo" visible={moduleDialog} onHide={() => setModuleDialog(false)} className="w-full max-w-2xl">
                <div className="grid gap-3 md:grid-cols-2">
                    <InputText
                        value={`${editingModule.idModulos || ""}`}
                        placeholder="ID"
                        onChange={(event) => {
                            setEditingModule((prev) => ({ ...prev, idModulos: Number(event.target.value) || 0 }));
                        }}
                    />
                    <InputText
                        value={editingModule.nmodulo}
                        placeholder="Nombre"
                        onChange={(event) => {
                            setEditingModule((prev) => ({ ...prev, nmodulo: event.target.value }));
                        }}
                    />
                    <InputText
                        value={`${editingModule.orden ?? ""}`}
                        placeholder="Orden"
                        onChange={(event) => {
                            setEditingModule((prev) => ({ ...prev, orden: Number(event.target.value) || null }));
                        }}
                    />
                    <InputText
                        value={editingModule.icono ?? ""}
                        placeholder="Icono"
                        onChange={(event) => {
                            setEditingModule((prev) => ({ ...prev, icono: event.target.value }));
                        }}
                    />
                    <InputText
                        value={editingModule.color ?? ""}
                        placeholder="Color"
                        onChange={(event) => {
                            setEditingModule((prev) => ({ ...prev, color: event.target.value }));
                        }}
                    />
                    <InputText
                        value={editingModule.detalle ?? ""}
                        placeholder="Detalle"
                        onChange={(event) => {
                            setEditingModule((prev) => ({ ...prev, detalle: event.target.value }));
                        }}
                    />
                    <div className="col-span-full flex items-center gap-2">
                        <span>Modulo activo</span>
                        <InputSwitch
                            checked={!!editingModule.activo}
                            onChange={(event) => {
                                setEditingModule((prev) => ({ ...prev, activo: !!event.value }));
                            }}
                        />
                    </div>
                </div>
                <div className="mt-4 flex justify-end">
                    <Button label="Guardar" className="atlantis-pink-btn" onClick={submitModule} />
                </div>
            </Dialog>

            <Dialog header="Menu" visible={menuDialog} onHide={() => setMenuDialog(false)} className="w-full max-w-3xl">
                <div className="grid gap-3 md:grid-cols-2">
                    <select
                        className="p-inputtext p-component"
                        value={editingMenu.idModulos}
                        onChange={(event) => {
                            setEditingMenu((prev) => ({ ...prev, idModulos: Number(event.target.value) }));
                        }}
                    >
                        <option value={0}>Selecciona modulo</option>
                        {page.props.modules.map((module) => (
                            <option key={module.idModulos} value={module.idModulos}>
                                {module.nmodulo}
                            </option>
                        ))}
                    </select>
                    <InputText
                        value={editingMenu.nombre}
                        placeholder="Nombre"
                        onChange={(event) => {
                            setEditingMenu((prev) => ({ ...prev, nombre: event.target.value }));
                        }}
                    />
                    <InputText
                        value={editingMenu.url}
                        placeholder="URL"
                        onChange={(event) => {
                            setEditingMenu((prev) => ({ ...prev, url: event.target.value }));
                        }}
                    />
                    <InputText
                        value={editingMenu.icono ?? ""}
                        placeholder="Icono"
                        onChange={(event) => {
                            setEditingMenu((prev) => ({ ...prev, icono: event.target.value }));
                        }}
                    />
                    <select
                        className="p-inputtext p-component"
                        value={editingMenu.id_menu ?? ""}
                        onChange={(event) => {
                            setEditingMenu((prev) => ({ ...prev, id_menu: event.target.value ? Number(event.target.value) : null }));
                        }}
                    >
                        <option value="">Sin padre</option>
                        {page.props.parentMenus
                            .filter((menu) => menu.id !== editingMenu.id)
                            .map((menu) => (
                                <option key={menu.id} value={menu.id}>
                                    {menu.nombre}
                                </option>
                            ))}
                    </select>
                    <InputText
                        value={`${editingMenu.orden ?? ""}`}
                        placeholder="Orden"
                        onChange={(event) => {
                            setEditingMenu((prev) => ({ ...prev, orden: Number(event.target.value) || null }));
                        }}
                    />
                    <InputText
                        value={editingMenu.permission_name ?? ""}
                        placeholder="Permission name"
                        onChange={(event) => {
                            setEditingMenu((prev) => ({ ...prev, permission_name: event.target.value }));
                        }}
                    />
                    <div className="flex items-center gap-2">
                        <span>Main</span>
                        <InputSwitch
                            checked={!!editingMenu.main}
                            onChange={(event) => {
                                setEditingMenu((prev) => ({ ...prev, main: !!event.value }));
                            }}
                        />
                    </div>
                    <div className="flex items-center gap-2">
                        <span>Activo</span>
                        <InputSwitch
                            checked={!!editingMenu.cesdo}
                            onChange={(event) => {
                                setEditingMenu((prev) => ({ ...prev, cesdo: !!event.value }));
                            }}
                        />
                    </div>
                </div>
                <div className="mt-4 flex justify-end">
                    <Button label="Guardar" className="atlantis-pink-btn" onClick={submitMenu} />
                </div>
            </Dialog>
        </>
    );
}