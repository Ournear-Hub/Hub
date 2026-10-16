/**
 * Dev-only registry integrity checks (import manually or from tests later).
 * Not loaded by the application bundle unless imported.
 */
import { getRegistryEntry, iconRegistry, resolveRegistryIconName } from '@/icons/registry';
import { tablerComponents } from '@/icons/tablerComponents';

export function runIconRegistrySelfCheck(): { ok: boolean; errors: string[] } {
    const errors: string[] = [];

    for (const entry of iconRegistry) {
        if (entry.status !== 'active') {
            continue;
        }
        if (entry.source === 'tabler' && (!entry.tablerName || !tablerComponents[entry.tablerName])) {
            errors.push(`Missing Tabler mapping for "${entry.name}" (${entry.tablerName ?? 'no tablerName'})`);
        }
        if (entry.source === 'inline' && entry.inlineKey !== 'spinner') {
            errors.push(`Unsupported inline key for "${entry.name}"`);
        }
        for (const alias of entry.aliases ?? []) {
            const resolved = resolveRegistryIconName(alias);
            if (resolved !== entry.name) {
                errors.push(`Alias "${alias}" should resolve to "${entry.name}", got "${resolved}"`);
            }
        }
    }

    if (resolveRegistryIconName('not-a-real-icon-name') !== null) {
        errors.push('Invalid name should not resolve');
    }
    if (!getRegistryEntry('find')) {
        errors.push('Alias "find" should resolve to search');
    }

    return { ok: errors.length === 0, errors };
}
