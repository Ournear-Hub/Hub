import type { ReactNode } from 'react';
import { focusRing } from '@/design-system/classNames';

/** Circular image / fallback used in public header and recruiter workspace header identity menus. */
export function HeaderWorkspaceIdentityAvatar({
    imageUrl,
    fallback,
    alt = '',
    variant = 'personal',
}: {
    imageUrl: string | null | undefined;
    fallback: string;
    alt?: string;
    variant?: 'personal' | 'company';
}) {
    const shell = 'inline-flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full border border-line';

    if (imageUrl) {
        if (variant === 'company') {
            return (
                <span className={`${shell} bg-white p-0.5`}>
                    <img src={imageUrl} alt={alt} className="h-full w-full object-contain object-center" />
                </span>
            );
        }

        return <img src={imageUrl} alt={alt} className={`${shell} object-cover object-center`} />;
    }

    const fallbackClass =
        variant === 'company'
            ? `${shell} bg-brand/10 text-xs font-semibold text-brand`
            : `${shell} bg-brand text-xs font-semibold text-white`;

    return (
        <span aria-hidden="true" className={fallbackClass}>
            {fallback}
        </span>
    );
}

type TriggerProps = {
    ariaLabel: string;
    ariaExpanded: boolean;
    onClick: () => void;
    avatar: ReactNode;
    primaryLabel?: string;
    secondaryLabel?: string;
    /** Icon-only circular trigger (recruiter workspace header). */
    iconOnly?: boolean;
};

export function headerWorkspaceIdentityTriggerClass(iconOnly: boolean): string {
    if (iconOnly) {
        return `inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-[#214449]/25 bg-white hover:bg-surface ${focusRing}`;
    }

    return `flex min-h-10 max-w-[11rem] items-center gap-2 rounded-full border border-[#214449]/25 bg-white py-0.5 pl-0.5 pr-2.5 text-left hover:bg-surface sm:max-w-[12rem] lg:pl-1 lg:pr-3 ${focusRing}`;
}

export function HeaderWorkspaceIdentityTrigger({
    ariaLabel,
    ariaExpanded,
    onClick,
    avatar,
    primaryLabel,
    secondaryLabel,
    iconOnly = false,
}: TriggerProps) {
    return (
        <button
            type="button"
            className={headerWorkspaceIdentityTriggerClass(iconOnly)}
            aria-haspopup="menu"
            aria-expanded={ariaExpanded}
            aria-label={ariaLabel}
            onClick={onClick}
        >
            {avatar}
            {!iconOnly && primaryLabel ? (
                <span className="hidden min-w-0 truncate lg:block">
                    <span className="block truncate text-sm font-medium text-ink">{primaryLabel}</span>
                    {secondaryLabel ? (
                        <span className="block truncate text-[11px] font-medium text-muted">{secondaryLabel}</span>
                    ) : null}
                </span>
            ) : null}
            {!iconOnly && primaryLabel ? (
                <span aria-hidden="true" className="hidden text-xs text-muted lg:inline">
                    ▾
                </span>
            ) : null}
        </button>
    );
}
