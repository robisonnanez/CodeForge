import { Link, router, usePage } from '@inertiajs/react';
import { Avatar } from 'primereact/avatar';
import { Badge } from 'primereact/badge';
import { Button } from 'primereact/button';
import { Menu } from 'primereact/menu';
import type { MenuItem as PrimeMenuItem } from 'primereact/menuitem';
import { useMemo, useRef, useState } from 'react';
import type { PropsWithChildren } from 'react';
import { logout } from '@/routes';
import profile from '@/routes/profile';
import security from '@/routes/security';

type NavItem = { id: number; label: string; href: string; icon?: string | null; children?: NavItem[] };

function MenuNode({ item }: { item: NavItem }) {
  const hasChildren = !!item.children?.length;
  const [open, setOpen] = useState(false);

  return (
    <li className="atlantis-menu-node">
      {hasChildren ? (
        <button type="button" className="atlantis-menu-item atlantis-menu-parent" onClick={() => setOpen((value) => !value)}>
          <span className="atlantis-menu-left">
            {item.icon && <i className={item.icon} />}
            <span>{item.label}</span>
          </span>
          <i className={`pi ${open ? 'pi-chevron-down' : 'pi-chevron-right'} atlantis-menu-caret`} />
        </button>
      ) : (
        <Link href={item.href} className="atlantis-menu-item">
          <span className="atlantis-menu-left">
            {item.icon && <i className={item.icon} />}
            <span>{item.label}</span>
          </span>
        </Link>
      )}
      {hasChildren && open && <ul className="atlantis-submenu">{item.children!.map((child) => <MenuNode key={child.id} item={child} />)}</ul>}
    </li>
  );
}

export default function AtlantisLayout({ children }: PropsWithChildren) {
  const page = usePage<{ auth: { user?: { name?: string } }; navigation?: NavItem[] }>();
  const [mobileOpen, setMobileOpen] = useState(false);
  const userMenuRef = useRef<Menu>(null);
  const username = page.props.auth.user?.name ?? 'Usuario';
  const navigation = page.props.navigation ?? [];

  const breadcrumbs = useMemo(() => {
    const path = page.url.split('?')[0].replace(/\/$/, '');

    if (path.startsWith('/admin/roles-permissions')) {
      return ['Admin', 'Roles y Permisos'];
    }

    if (path.startsWith('/admin/users-permissions')) {
      return ['Admin', 'Permisos por Usuario'];
    }

    if (path.startsWith('/settings/security')) {
      return ['Configuracion', 'Seguridad', 'Passkeys'];
    }

    if (path.startsWith('/apps/chat')) {
      return ['Apps', 'Chat'];
    }

    if (path.startsWith('/apps/mail/inbox')) {
      return ['Apps', 'Mail', 'Inbox'];
    }

    if (path.startsWith('/apps/mail/compose')) {
      return ['Apps', 'Mail', 'Compose'];
    }

    if (path.startsWith('/apps/mail/detail')) {
      return ['Apps', 'Mail', 'Detail'];
    }

    return ['Dashboard'];
  }, [page.url]);

  const avatarMenuItems: PrimeMenuItem[] = [
    { label: 'Perfil', icon: 'pi pi-user', command: () => router.visit(profile.edit.url()) },
    { label: 'Configuracion', icon: 'pi pi-cog', command: () => router.visit(security.edit.url()) },
    { label: 'Logout', icon: 'pi pi-sign-out', command: () => router.post(logout.url()) },
  ];

  return (
    <div className="atlantis-theme min-h-screen">
      <div className="atlantis-shell">
        <aside className={`atlantis-sidebar ${mobileOpen ? 'atlantis-open' : ''}`}>
          <div className="atlantis-sidebar-brand">
            <i className="pi pi-prime" />
            <Link href="/dashboard" className="atlantis-brand">
              Atlantis
            </Link>
          </div>
          <nav>
            <ul className="atlantis-menu">{navigation.map((item) => <MenuNode key={item.id} item={item} />)}</ul>
          </nav>
        </aside>

        <div className="atlantis-main">
          <header className="atlantis-topbar">
            <div className="atlantis-topbar-left">
              <Button type="button" className="atlantis-toggle p-button-text" icon="pi pi-bars" onClick={() => setMobileOpen((value) => !value)} />
              <div className="atlantis-breadcrumbs">
                {breadcrumbs.map((crumb, index) => (
                  <span key={`${crumb}-${index}`} className="atlantis-breadcrumb-item">
                    {index > 0 && <span className="atlantis-breadcrumb-sep">/</span>}
                    {crumb}
                  </span>
                ))}
              </div>
            </div>
            <div className="atlantis-topbar-right">
              <i className="pi pi-search" />
              <i className="pi pi-bell" />
              <i className="pi pi-comment" />
              <i className="pi pi-cog" />
              <Badge value="4" severity="warning" />
              <button type="button" className="atlantis-avatar-button" onClick={(event) => userMenuRef.current?.toggle(event)}>
                <Avatar label={username.slice(0, 1).toUpperCase()} shape="circle" />
              </button>
              <Menu model={avatarMenuItems} popup ref={userMenuRef} />
            </div>
          </header>
          <main className="atlantis-content" onClick={() => setMobileOpen(false)}>
            {children}
          </main>
        </div>
      </div>
      {mobileOpen && <button type="button" className="atlantis-overlay" onClick={() => setMobileOpen(false)} aria-label="Close navigation" />}
    </div>
  );
}
