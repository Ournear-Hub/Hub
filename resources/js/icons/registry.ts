import type { IconRegistryEntry } from '@/icons/types';

/**
 * Governed OurNear icon registry (PR-1, contract PR-4M).
 *
 * Governance:
 * - `name` is the canonical string passed to OurNearIcon.
 * - Add Tabler icons only with a matching import in `tablerComponents.ts`.
 * - `aliases` map alternate strings to `name` (case-insensitive resolve).
 * - `status: 'deprecated'` entries are excluded from resolve/list helpers.
 * - Do not import `@tabler/icons-react` outside `tablerComponents.ts`.
 */
export const iconRegistry: IconRegistryEntry[] = [
    { name: 'bell', source: 'tabler', tablerName: 'IconBell', category: 'communication', defaultSize: 'md', stroke: 2, status: 'active', aliases: ['notifications'] },
    { name: 'close', source: 'tabler', tablerName: 'IconX', category: 'actions', defaultSize: 'md', stroke: 2, status: 'active', aliases: ['x'] },
    { name: 'menu', source: 'tabler', tablerName: 'IconMenu2', category: 'navigation', defaultSize: 'md', stroke: 2, status: 'active' },
    { name: 'home', source: 'tabler', tablerName: 'IconHome', category: 'navigation', defaultSize: 'md', stroke: 2, status: 'active' },
    { name: 'briefcase', source: 'tabler', tablerName: 'IconBriefcase', category: 'jobs', defaultSize: 'md', stroke: 2, status: 'active' },
    { name: 'clipboard', source: 'tabler', tablerName: 'IconClipboard', category: 'recruitment', defaultSize: 'md', stroke: 2, status: 'active' },
    { name: 'user', source: 'tabler', tablerName: 'IconUserCircle', category: 'users', defaultSize: 'md', stroke: 2, status: 'active', aliases: ['user-circle'] },
    { name: 'more-horizontal', source: 'tabler', tablerName: 'IconDots', category: 'actions', defaultSize: 'md', stroke: 2, status: 'active', aliases: ['dots'] },
    { name: 'spinner', source: 'inline', inlineKey: 'spinner', category: 'system', defaultSize: 'sm', stroke: 2, status: 'active', aliases: ['loading'] },
    { name: 'search', source: 'tabler', tablerName: 'IconSearch', category: 'navigation', defaultSize: 'md', stroke: 2, status: 'active', aliases: ['find'] },
    { name: 'settings', source: 'tabler', tablerName: 'IconSettings', category: 'settings', defaultSize: 'md', stroke: 2, status: 'active' },
    { name: 'chevron-right', source: 'tabler', tablerName: 'IconChevronRight', category: 'navigation', defaultSize: 'md', stroke: 2, status: 'active' },
    { name: 'chevron-down', source: 'tabler', tablerName: 'IconChevronDown', category: 'navigation', defaultSize: 'md', stroke: 2, status: 'active' },
    { name: 'arrow-left', source: 'tabler', tablerName: 'IconArrowLeft', category: 'navigation', defaultSize: 'md', stroke: 2, status: 'active' },
    { name: 'arrow-right', source: 'tabler', tablerName: 'IconArrowRight', category: 'navigation', defaultSize: 'md', stroke: 2, status: 'active' },
    { name: 'plus', source: 'tabler', tablerName: 'IconPlus', category: 'actions', defaultSize: 'md', stroke: 2, status: 'active' },
    { name: 'edit', source: 'tabler', tablerName: 'IconEdit', category: 'actions', defaultSize: 'md', stroke: 2, status: 'active' },
    { name: 'eye', source: 'tabler', tablerName: 'IconEye', category: 'actions', defaultSize: 'md', stroke: 2, status: 'active', aliases: ['show-password'] },
    { name: 'eye-off', source: 'tabler', tablerName: 'IconEyeOff', category: 'actions', defaultSize: 'md', stroke: 2, status: 'active', aliases: ['hide-password'] },
    { name: 'delete', source: 'tabler', tablerName: 'IconTrash', category: 'actions', defaultSize: 'md', stroke: 2, status: 'active', aliases: ['trash'] },
    { name: 'download', source: 'tabler', tablerName: 'IconDownload', category: 'actions', defaultSize: 'md', stroke: 2, status: 'active' },
    { name: 'upload', source: 'tabler', tablerName: 'IconUpload', category: 'actions', defaultSize: 'md', stroke: 2, status: 'active' },
    { name: 'building', source: 'tabler', tablerName: 'IconBuilding', category: 'organizations', defaultSize: 'md', stroke: 2, status: 'active' },
    { name: 'file', source: 'tabler', tablerName: 'IconFile', category: 'documents', defaultSize: 'md', stroke: 2, status: 'active' },
    { name: 'message', source: 'tabler', tablerName: 'IconMessage', category: 'communication', defaultSize: 'md', stroke: 2, status: 'active' },
    { name: 'check', source: 'tabler', tablerName: 'IconCheck', category: 'status', defaultSize: 'md', stroke: 2, status: 'active' },
    { name: 'bookmark', source: 'tabler', tablerName: 'IconBookmark', category: 'actions', defaultSize: 'md', stroke: 2, status: 'active', aliases: ['save'] },
    { name: 'bookmark-filled', source: 'tabler', tablerName: 'IconBookmarkFilled', category: 'actions', defaultSize: 'md', stroke: 2, status: 'active', aliases: ['saved'] },
    { name: 'share', source: 'tabler', tablerName: 'IconShare2', category: 'actions', defaultSize: 'md', stroke: 2, status: 'active' },
    { name: 'link', source: 'tabler', tablerName: 'IconLink', category: 'actions', defaultSize: 'md', stroke: 2, status: 'active', aliases: ['copy-link'] },
    { name: 'verified-badge', source: 'tabler', tablerName: 'IconRosetteDiscountCheck', category: 'status', defaultSize: 'sm', stroke: 2, status: 'active', aliases: ['verified'] },
    { name: 'alert', source: 'tabler', tablerName: 'IconAlertTriangle', category: 'status', defaultSize: 'md', stroke: 2, status: 'active' },
    { name: 'info', source: 'tabler', tablerName: 'IconInfoCircle', category: 'status', defaultSize: 'md', stroke: 2, status: 'active' },
];

const byName = new Map<string, IconRegistryEntry>();
const aliasToCanonical = new Map<string, string>();

for (const entry of iconRegistry) {
    if (entry.status !== 'active') {
        continue;
    }
    byName.set(entry.name, entry);
    for (const alias of entry.aliases ?? []) {
        aliasToCanonical.set(alias, entry.name);
    }
}

export function resolveRegistryIconName(name: string): string | null {
    const normalized = name.trim().toLowerCase();
    if (byName.has(normalized)) {
        return normalized;
    }
    return aliasToCanonical.get(normalized) ?? null;
}

export function getRegistryEntry(name: string): IconRegistryEntry | null {
    const canonical = resolveRegistryIconName(name);
    if (!canonical) {
        return null;
    }
    return byName.get(canonical) ?? null;
}

export function listRegistryIconNames(): string[] {
    return [...byName.keys()].sort();
}
