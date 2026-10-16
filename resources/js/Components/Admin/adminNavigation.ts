import type { AdminNavItem } from '@/types/layout';

export function parseUrl(url: string): { path: string; params: URLSearchParams } {
    const [path, query = ''] = url.split('?');

    return { path, params: new URLSearchParams(query) };
}

export function isAdminNavActive(url: string, href: string): boolean {
    const current = parseUrl(url);
    const target = parseUrl(href);

    if (target.path === '/admin') {
        return current.path === '/admin' || current.path === '/admin/';
    }

    if (target.path === '/admin/hiring') {
        return current.path === '/admin/hiring' || current.path.startsWith('/admin/hiring/');
    }

    if (target.path === '/admin/analytics') {
        return current.path === '/admin/analytics' || current.path.startsWith('/admin/analytics/');
    }

    if (target.path === '/admin/design-system') {
        return current.path === '/admin/design-system' || current.path.startsWith('/admin/design-system/');
    }

    if (target.path === '/admin/users' && !target.params.get('segment')) {
        if (current.path === '/admin/users/activity' || current.path.startsWith('/admin/users/')) {
            return false;
        }

        return current.path === '/admin/users' && !current.params.get('segment');
    }

    if (target.params.get('segment')) {
        return current.path === '/admin/users' && current.params.get('segment') === target.params.get('segment');
    }

    if (target.params.get('view')) {
        const currentView =
            current.params.get('view') ||
            (current.path === '/admin/finance' && current.params.get('payment') ? 'payments' : current.path === '/admin/finance' ? 'invoices' : null);

        return current.path === target.path && currentView === target.params.get('view');
    }

    if (target.params.get('tab')) {
        return current.path === target.path && current.params.get('tab') === target.params.get('tab');
    }

    if (target.path === '/admin/master-data' && !target.params.get('tab')) {
        return current.path === '/admin/master-data' && !current.params.get('tab');
    }

    return current.path === target.path || current.path.startsWith(`${target.path}/`);
}

export function adminSectionHasActive(url: string, items: AdminNavItem[]): boolean {
    return items.some((item) => {
        if (item.href && isAdminNavActive(url, item.href)) {
            return true;
        }

        if (item.children && adminSectionHasActive(url, item.children)) {
            return true;
        }

        if (item.href === '/admin/users' && (url.startsWith('/admin/users/') || url.startsWith('/admin/users?'))) {
            return true;
        }

        return false;
    });
}
