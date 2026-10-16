import { SpinnerIcon } from '@/Components/UI/icons';
import { iconDefaults } from '@/foundation/tokens';
import { getRegistryEntry, resolveRegistryIconName } from '@/icons/registry';
import { resolveIconPixelSize } from '@/icons/sizes';
import { tablerComponents } from '@/icons/tablerComponents';
import type { IconSize } from '@/icons/types';

/**
 * Canonical OurNear icon component (PR-1, contract PR-4M).
 *
 * - Resolve `name` via `registry.ts` (aliases supported).
 * - Tabler SVGs only through `tablerComponents.ts` — never import Tabler in features.
 * - **Default `decorative={true}`** → `aria-hidden` (companion text or control label carries meaning).
 * - Meaningful standalone icon: `decorative={false}` + `aria-label`.
 * - `size` maps to pixel scale in `sizes.ts`; entry `defaultSize` when omitted.
 * - `stroke` overrides registry stroke; `className` merges onto the SVG (color, motion, layout).
 * - Unknown / misconfigured icons → dev warning + empty `data-missing-icon` placeholder (decorative).
 */
export type OurNearIconProps = {
    /** Registry name or alias */
    name: string;
    size?: IconSize;
    stroke?: number;
    className?: string;
    /** When true (default), icon is hidden from assistive technologies */
    decorative?: boolean;
    /** Required when decorative is false */
    'aria-label'?: string;
};

function warn(message: string): void {
    if (import.meta.env.DEV) {
        console.warn(`[OurNearIcon] ${message}`);
    }
}

function MissingIconPlaceholder({ name, className }: { name: string; className: string }) {
    return (
        <span
            className={className}
            data-missing-icon={name}
            aria-hidden="true"
            role="presentation"
        />
    );
}

export default function OurNearIcon({
    name,
    size,
    stroke,
    className = '',
    decorative = true,
    'aria-label': ariaLabel,
}: OurNearIconProps) {
    const canonical = resolveRegistryIconName(name);
    const entry = getRegistryEntry(name);

    if (!canonical || !entry) {
        warn(`Unknown icon "${name}". Add it to resources/js/icons/registry.ts or use a registered alias.`);
        return <MissingIconPlaceholder name={name} className={className} />;
    }

    if (!decorative && !ariaLabel) {
        warn(`Icon "${canonical}" needs aria-label when decorative={false}.`);
    }

    const pixelSize = resolveIconPixelSize(size, entry.defaultSize);
    const strokeWidth = stroke ?? entry.stroke ?? iconDefaults.stroke;
    const a11yProps = decorative
        ? { 'aria-hidden': true as const }
        : { 'aria-label': ariaLabel ?? canonical, role: 'img' as const };

    if (entry.source === 'inline' && entry.inlineKey === 'spinner') {
        const spinnerClass = className.includes('animate-spin') ? className : `${className} animate-spin`.trim();
        const spinner = <SpinnerIcon className={spinnerClass || 'h-4 w-4 animate-spin'} />;
        if (decorative) {
            return spinner;
        }
        return (
            <span className="inline-flex" aria-label={ariaLabel ?? canonical} role="img">
                {spinner}
            </span>
        );
    }

    const tablerName = entry.tablerName;
    if (!tablerName) {
        warn(`Registry entry "${canonical}" is missing tablerName.`);
        return <MissingIconPlaceholder name={canonical} className={className} />;
    }

    const TablerIcon = tablerComponents[tablerName];
    if (!TablerIcon) {
        warn(`Tabler component "${tablerName}" is not registered in tablerComponents.ts.`);
        return <MissingIconPlaceholder name={canonical} className={className} />;
    }

    return (
        <TablerIcon
            size={pixelSize}
            stroke={strokeWidth}
            className={className}
            {...a11yProps}
        />
    );
}
