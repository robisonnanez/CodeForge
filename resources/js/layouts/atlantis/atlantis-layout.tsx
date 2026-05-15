import { Link, router, usePage } from '@inertiajs/react';
import { Avatar } from 'primereact/avatar';
import { Badge } from 'primereact/badge';
import { Button } from 'primereact/button';
import { Menu } from 'primereact/menu';
import type {MenuItem as PrimeMenuItem} from 'primereact/menuitem';
import {  useMemo, useRef, useState } from 'react';
import type {PropsWithChildren} from 'react';
import { logout } from '@/routes';
import profile from '@/routes/profile';
import security from '@/routes/security';

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
                    { label: 'Mail', href: '/apps/mail/inbox' },
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
            { label: 'Mail', href: '/apps/mail/inbox' },
            { label: 'Task List', href: '/apps/task-list' },
        ],
    },
];

function MenuNode({ item, currentPath, sidebarCollapsed }: { item: MenuItem; currentPath: string; sidebarCollapsed: boolean }) {
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
                        {!sidebarCollapsed && <span>{item.label}</span>}
                    </span>
                    {!sidebarCollapsed && <i className={`pi ${open ? 'pi-chevron-down' : 'pi-chevron-right'}`} />}
                </button>
            ) : (
                <Link href={item.href!} className={`atlantis-menu-item ${isActive ? 'atlantis-active' : ''}`}>
                    <span className="atlantis-menu-left">
                        {item.icon && <i className={item.icon} />}
                        {!sidebarCollapsed && <span>{item.label}</span>}
                    </span>
                </Link>
            )}

            {hasChildren && open && !sidebarCollapsed ? (
                <ul className="atlantis-submenu">
                    {item.children!.map((child) => (
                        <MenuNode key={`${item.label}-${child.label}`} item={child} currentPath={currentPath} sidebarCollapsed={sidebarCollapsed} />
                    ))}
                </ul>
            ) : null}
        </li>
    );
}

export default function AtlantisLayout({ children }: PropsWithChildren) {
    const page = usePage<{ auth: { user?: { name?: string; email?: string } } }>();
    const [mobileSidebarOpen, setMobileSidebarOpen] = useState(false);
    const [desktopCollapsed, setDesktopCollapsed] = useState(() => {
        if (typeof window === 'undefined') {
            return false;
        }

        return window.localStorage.getItem('atlantis-sidebar-collapsed') === '1';
    });
    const userMenuRef = useRef<Menu>(null);

    const currentPath = useMemo(() => {
        const url = page.url.split('?')[0];

        return url.length > 1 ? url.replace(/\/$/, '') : url;
    }, [page.url]);

    const username = page.props.auth.user?.name ?? 'Usuario';

    const avatarMenuItems: PrimeMenuItem[] = [
        { label: 'Perfil', icon: 'pi pi-user', command: () => router.visit(profile.edit.url()) },
        { label: 'Configuracion', icon: 'pi pi-cog', command: () => router.visit(security.edit.url()) },
        { label: 'Logout', icon: 'pi pi-sign-out', command: () => router.post(logout.url()) },
    ];

    return (
        <div className="atlantis-theme min-h-screen">
            <header className="atlantis-topbar">
                <div className="atlantis-topbar-left">
                    <Button
                        type="button"
                        className="atlantis-toggle p-button-text"
                        icon="pi pi-bars"
                        onClick={() => {
                            if (window.innerWidth < 768) {
                                setMobileSidebarOpen((value) => !value);

                                return;
                            }

                            setDesktopCollapsed((value) => {
                                const next = !value;
                                window.localStorage.setItem('atlantis-sidebar-collapsed', next ? '1' : '0');

                                return next;
                            });
                        }}
                    />
                    <Link href="/dashboard" className="atlantis-brand">
                        Atlantis
                    </Link>
                </div>
                <div className="atlantis-topbar-right">
                    <i className="pi pi-search" />
                    <i className="pi pi-bell" />
                    <Badge value="4" severity="warning" />
                    <button type="button" className="atlantis-avatar-button" onClick={(event) => userMenuRef.current?.toggle(event)}>
                        <Avatar label={username.slice(0, 1).toUpperCase()} shape="circle" />
                    </button>
                    <Menu model={avatarMenuItems} popup ref={userMenuRef} />
                </div>
            </header>

            <div className={`atlantis-shell ${desktopCollapsed ? 'atlantis-shell-collapsed' : ''}`}>
                <aside className={`atlantis-sidebar ${mobileSidebarOpen ? 'atlantis-open' : ''}`}>
                    <div className="atlantis-sidebar-header">{!desktopCollapsed && <h3>Navigation</h3>}</div>
                    <nav>
                        <ul className="atlantis-menu">
                            {menuItems.map((item) => (
                                <MenuNode key={item.label} item={item} currentPath={currentPath} sidebarCollapsed={desktopCollapsed} />
                            ))}
                        </ul>
                    </nav>
                </aside>

                {mobileSidebarOpen && (
                    <button type="button" className="atlantis-overlay" onClick={() => setMobileSidebarOpen(false)} aria-label="Close navigation" />
                )}

                <main className="atlantis-content" onClick={() => setMobileSidebarOpen(false)}>
                    {children}
                </main>
            </div>
        </div>
    );
}
