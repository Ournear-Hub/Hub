import { describe, expect, it } from 'vitest';
import { adminSectionHasActive, isAdminNavActive } from '@/Components/Admin/adminNavigation';
import type { AdminNavItem } from '@/types/layout';

describe('adminNavigation active routes', () => {
    it('highlights dashboard only on /admin root', () => {
        expect(isAdminNavActive('/admin', '/admin')).toBe(true);
        expect(isAdminNavActive('/admin/jobs', '/admin')).toBe(false);
    });

    it('highlights nested hiring and design-system paths', () => {
        expect(isAdminNavActive('/admin/hiring/pipeline', '/admin/hiring')).toBe(true);
        expect(isAdminNavActive('/admin/design-system/atoms/button', '/admin/design-system')).toBe(true);
    });

    it('respects user segment query params', () => {
        expect(isAdminNavActive('/admin/users?segment=candidates', '/admin/users?segment=candidates')).toBe(true);
        expect(isAdminNavActive('/admin/users?segment=candidates', '/admin/users?segment=employers')).toBe(false);
        expect(isAdminNavActive('/admin/users', '/admin/users?segment=candidates')).toBe(false);
    });

    it('detects active section for nested user links', () => {
        const items: AdminNavItem[] = [
            {
                label: 'Users',
                href: '/admin/users',
                children: [{ label: 'Activity', href: '/admin/users/activity' }],
            },
        ];

        expect(adminSectionHasActive('/admin/users/activity', items)).toBe(true);
    });
});
