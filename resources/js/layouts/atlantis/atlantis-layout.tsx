import { Link, usePage } from '@inertiajs/react';
import { Avatar } from 'primereact/avatar';
import { Badge } from 'primereact/badge';
import { Button } from 'primereact/button';
import {  useMemo, useState } from 'react';
import type {PropsWithChildren} from 'react';

type MenuItem = {
    label: string;
    href?: string;
    icon?: string;
    children?: MenuItem[];
};

const menuItems: MenuItem[] = [
    { label: 'Dashboard', href: '/dashboard', icon: 'pi pi-home' },
    {
        label: 'UI Kit',
        icon: 'pi pi-palette',
        children: [
            { label: 'Input', href: '/dashboard' },
            {
                label: 'Submenu 2',
                children: [
                    { label: 'Calendar', href: '/apps/calendar' },
                    { label: 'Chat', href: '/apps/chat' },
                    { label: 'Mail', href: '/apps/mail' },
                    { label: 'Task List', href: '/apps/task-list' },
                ],
            },
        ],
    },
    {
        label: 'Apps',
        icon: 'pi pi-th-large',
        children: [
            { label: 'Calendar', href: '/apps/calendar' },
            { label: 'Chat', href: '/apps/chat' },
            { label: 'Mail', href: '/apps/mail' },
            { label: 'Task List', href: '/apps/task-list' },
        ],
    },
];

function MenuNode({ item, currentPath }: { item: MenuItem; currentPath: string }) {
    const hasChildren = !!item.children?.length;
    const [open, setOpen] = useState(() => {
        if (!hasChildren) {
            return false;
        }

        return item.children!.some((child) => {
            if (child.href === currentPath) {
                return true;
            }

            return child.children?.some((nested) => nested.href === currentPath) ?? false;
        });
    });

    const isActive = item.href === currentPath;

    return (
        <li>
            {hasChildren ? (
                <button
                    type="button"
                    className="atlantis-menu-item atlantis-menu-parent"
                    onClick={() => setOpen((value) => !value)}
                >
                    <span className="atlantis-menu-left">
                        {item.icon && <i className={item.icon} />}
                        <span>{item.label}</span>
                    </span>
                    <i className={`pi ${open ? 'pi-chevron-down' : 'pi-chevron-right'}`} />
                </button>
            ) : (
                <Link href={item.href!} className={`atlantis-menu-item ${isActive ? 'atlantis-active' : ''}`}>
                    <span className="atlantis-menu-left">
                        {item.icon && <i className={item.icon} />}
                        <span>{item.label}</span>
                    </span>
                </Link>
            )}

            {hasChildren && open ? (
                <ul className="atlantis-submenu">
                    {item.children!.map((child) => (
                        <MenuNode key={`${item.label}-${child.label}`} item={child} currentPath={currentPath} />
                    ))}
                </ul>
            ) : null}
        </li>
    );
}

export default function AtlantisLayout({ children }: PropsWithChildren) {
    const page = usePage<{ auth: { user?: { name?: string } } }>();
    const [mobileSidebarOpen, setMobileSidebarOpen] = useState(false);

    const currentPath = useMemo(() => {
        const url = page.url.split('?')[0];

        return url.length > 1 ? url.replace(/\/$/, '') : url;
    }, [page.url]);

    const username = page.props.auth.user?.name ?? 'Usuario';

    return (
        <div className="atlantis-theme min-h-screen bg-slate-100">
            <header className="atlantis-topbar">
                <div className="atlantis-topbar-left">
                    <Button
                        type="button"
                        className="atlantis-toggle p-button-text"
                        icon="pi pi-bars"
                        onClick={() => setMobileSidebarOpen((value) => !value)}
                    />
                    <Link href="/dashboard" className="atlantis-brand">
                        Atlantis
                    </Link>
                </div>
                <div className="atlantis-topbar-right">
                    <Badge value="4" severity="warning" />
                    <Avatar label={username.slice(0, 1).toUpperCase()} shape="circle" />
                </div>
            </header>

            <div className="atlantis-shell">
                <aside className={`atlantis-sidebar ${mobileSidebarOpen ? 'atlantis-open' : ''}`}>
                    <div className="atlantis-sidebar-header">
                        <h3>Navigation</h3>
                    </div>
                    <nav>
                        <ul className="atlantis-menu">
                            {menuItems.map((item) => (
                                <MenuNode key={item.label} item={item} currentPath={currentPath} />
                            ))}
                        </ul>
                    </nav>
                </aside>

                <main className="atlantis-content" onClick={() => setMobileSidebarOpen(false)}>
                    {children}
                </main>
            </div>
        </div>
    );
}
