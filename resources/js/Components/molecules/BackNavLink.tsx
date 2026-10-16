import { Link } from '@inertiajs/react';
import type { MouseEventHandler } from 'react';
import OurNearIcon from '@/icons/OurNearIcon';

export type BackNavLinkProps = {
    href: string;
    /** Accessible name, e.g. "Back to applications" */
    label: string;
    className?: string;
    onClick?: MouseEventHandler;
};

function backNavClassName(className: string): string {
    return `inline-flex min-h-11 items-center gap-1.5 rounded-md px-1 text-sm font-medium text-brand hover:text-brand/85 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand/35 ${className}`.trim();
}

/**
 * Canonical parent/list navigation (FE-504). Icon + visible label; not a substitute for page title.
 */
export default function BackNavLink({ href, label, className = '', onClick }: BackNavLinkProps) {
    return (
        <Link href={href} onClick={onClick} className={backNavClassName(className)}>
            <OurNearIcon name="arrow-left" size="sm" decorative />
            <span>{label}</span>
        </Link>
    );
}

export type BackNavButtonProps = {
    label: string;
    onClick: () => void;
    className?: string;
};

/** In-page back (e.g. profile module hub) where href navigation is not used. */
export function BackNavButton({ label, onClick, className = '' }: BackNavButtonProps) {
    return (
        <button type="button" onClick={onClick} className={backNavClassName(className)}>
            <OurNearIcon name="arrow-left" size="sm" decorative />
            <span>{label}</span>
        </button>
    );
}
