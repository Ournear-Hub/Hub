import { Link, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import AccountNavItemIcon from '@/Components/Account/AccountNavItemIcon';
import {
    HeaderWorkspaceIdentityAvatar,
    HeaderWorkspaceIdentityTrigger,
} from '@/Components/Account/HeaderWorkspaceIdentityControl';
import { focusRing } from '@/design-system/classNames';
import type { AuthUser, SharedPageProps } from '@/types/page';

const headerMenuLinkClass = `flex items-center gap-2.5 rounded-md px-3 py-2 text-sm text-ink hover:bg-surface ${focusRing}`;

function personalDisplayName(user: AuthUser): string {
    const name = user.name?.trim();
    if (name) {
        return name;
    }

    const username = user.username?.trim();
    if (username) {
        return username;
    }

    return 'Account';
}

function initials(user: AuthUser): string {
    const source = user.name.trim() || user.username || 'User';

    return source
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0))
        .join('')
        .toUpperCase();
}

type Props = {
    user: AuthUser;
    className?: string;
    iconOnly?: boolean;
};

export default function PersonalAccountMenu({ user, className = '', iconOnly = false }: Props) {
    const { props } = usePage<SharedPageProps>();
    const accountLinks = props.headerAccountNav ?? [];
    const [open, setOpen] = useState(false);
    const ref = useRef<HTMLDivElement>(null);
    const showPersonalLabel = Boolean(user.employer_identity);
    const displayName = personalDisplayName(user);

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
                ariaLabel={`Open personal account menu for ${displayName}`}
                ariaExpanded={open}
                onClick={() => setOpen((value) => !value)}
                iconOnly={iconOnly}
                primaryLabel={displayName}
                secondaryLabel={showPersonalLabel ? 'Personal' : undefined}
                avatar={
                    <HeaderWorkspaceIdentityAvatar
                        imageUrl={user.photo_url}
                        fallback={initials(user)}
                        variant="personal"
                    />
                }
            />
            {open ? (
                <div
                    role="menu"
                    aria-label="Personal account menu"
                    className="absolute right-0 top-full z-50 mt-2 w-64 overflow-hidden rounded-xl border border-line bg-white shadow-md"
                >
                    <div className="flex items-center gap-3 border-b border-line px-4 py-3">
                        <HeaderWorkspaceIdentityAvatar
                            imageUrl={user.photo_url}
                            fallback={initials(user)}
                            variant="personal"
                        />
                        <div className="min-w-0">
                            <p className="truncate text-sm font-semibold text-ink">{displayName}</p>
                            <p className="text-xs text-muted">Personal account</p>
                        </div>
                    </div>
                    <nav className="max-h-[min(26rem,calc(100vh-8rem))] overflow-y-auto p-2" aria-label="Personal account menu links">
                        {accountLinks.map((item) => (
                            <Link
                                key={`${item.href}-${item.label}`}
                                href={item.href}
                                role="menuitem"
                                className={headerMenuLinkClass}
                                onClick={() => setOpen(false)}
                            >
                                <AccountNavItemIcon icon={item.icon} variant="sheet" />
                                <span className="min-w-0 truncate">{item.label}</span>
                            </Link>
                        ))}
                    </nav>
                    <div className="border-t border-line p-2">
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            role="menuitem"
                            className={`block w-full rounded-md px-3 py-2 text-left text-sm font-medium text-danger hover:bg-danger/5 ${focusRing}`}
                            onClick={() => setOpen(false)}
                        >
                            Log out
                        </Link>
                    </div>
                </div>
            ) : null}
        </div>
    );
}
