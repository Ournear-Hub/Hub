import { Link, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import AccountNavItemIcon from '@/Components/Account/AccountNavItemIcon';
import {
    HeaderWorkspaceIdentityAvatar,
    HeaderWorkspaceIdentityTrigger,
} from '@/Components/Account/HeaderWorkspaceIdentityControl';
import { focusRing } from '@/design-system/classNames';
import type { HeaderEmployerIdentity, SharedPageProps } from '@/types/page';

const menuLinkClass = `flex items-center gap-2.5 rounded-md px-3 py-2 text-sm text-ink hover:bg-surface ${focusRing}`;

type Props = {
    identity: HeaderEmployerIdentity;
    className?: string;
    iconOnly?: boolean;
};

export default function CompanyWorkspaceMenu({ identity, className = '', iconOnly = false }: Props) {
    const { props } = usePage<SharedPageProps>();
    const links = props.companyHeaderNav ?? [];
    const [open, setOpen] = useState(false);
    const ref = useRef<HTMLDivElement>(null);
    const label = identity.name?.trim() || 'Company workspace';
    const initial = label.charAt(0).toUpperCase() || 'C';

    useEffect(() => {
        if (!open) {
            return;
        }
        const closeOutside = (event: MouseEvent) => {
            if (ref.current && !ref.current.contains(event.target as Node)) {
                setOpen(false);
            }
        };
        const closeOnEscape = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        };
        document.addEventListener('mousedown', closeOutside);
        document.addEventListener('keydown', closeOnEscape);

        return () => {
            document.removeEventListener('mousedown', closeOutside);
            document.removeEventListener('keydown', closeOnEscape);
        };
    }, [open]);

    return (
        <div className={`relative ${className}`} ref={ref}>
            <HeaderWorkspaceIdentityTrigger
                ariaLabel={`Open company menu for ${label}`}
                ariaExpanded={open}
                onClick={() => setOpen((value) => !value)}
                iconOnly={iconOnly}
                primaryLabel={label}
                secondaryLabel="Company"
                avatar={
                    <HeaderWorkspaceIdentityAvatar
                        imageUrl={identity.logo_url}
                        fallback={initial}
                        variant="company"
                    />
                }
            />
            {open ? (
                <div
                    role="menu"
                    aria-label="Company workspace menu"
                    className="absolute right-0 top-full z-50 mt-2 w-64 overflow-hidden rounded-xl border border-line bg-white shadow-md"
                >
                    <div className="border-b border-line px-4 py-3">
                        <p className="truncate text-sm font-semibold text-ink">{label}</p>
                        <p className="text-xs text-muted">Company workspace</p>
                    </div>
                    <nav className="max-h-[min(26rem,calc(100vh-8rem))] overflow-y-auto p-2" aria-label="Company menu links">
                        {links.map((item) => (
                            <Link
                                key={`${item.href}-${item.label}`}
                                href={item.href}
                                role="menuitem"
                                className={menuLinkClass}
                                onClick={() => setOpen(false)}
                            >
                                <AccountNavItemIcon icon={item.icon} variant="sheet" />
                                <span className="min-w-0 truncate">{item.label}</span>
                            </Link>
                        ))}
                    </nav>
                </div>
            ) : null}
        </div>
    );
}
