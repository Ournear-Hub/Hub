import { Link } from '@inertiajs/react';
import { useEffect, useId, useRef } from 'react';
import IconButton from '@/Components/UI/IconButton';
import { CloseIcon } from '@/Components/UI/icons';
import { isNavActive } from '@/content/navigation';
import type { NavItem } from '@/types/layout';

type Props = {
    open: boolean;
    onClose: () => void;
    url: string;
    primaryItems: NavItem[];
    panelId?: string;
    guestActions?: React.ReactNode;
    accountSection?: React.ReactNode;
};

export default function PublicMobileMenu({
    open,
    onClose,
    url,
    primaryItems,
    panelId: panelIdProp,
    guestActions,
    accountSection,
}: Props) {
    const generatedId = useId();
    const panelId = panelIdProp ?? generatedId;
    const panelRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!open) {
            return;
        }

        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        panelRef.current?.querySelector<HTMLElement>('button,a,[tabindex]')?.focus();

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                onClose();
            }
        };

        window.addEventListener('keydown', onKeyDown);

        return () => {
            document.body.style.overflow = previousOverflow;
            window.removeEventListener('keydown', onKeyDown);
        };
    }, [open, onClose]);

    if (!open) {
        return null;
    }

    return (
        <div className="fixed inset-0 z-50 lg:hidden" role="presentation">
            <button
                type="button"
                className="absolute inset-0 bg-ink/40"
                aria-label="Close navigation menu"
                onClick={onClose}
            />
            <div
                ref={panelRef}
                id={panelId}
                role="dialog"
                aria-modal="true"
                aria-label="Site navigation"
                className="absolute right-0 top-0 flex h-full w-[min(100%,20rem)] flex-col border-l border-line bg-white shadow-lg"
            >
                <div className="flex items-center justify-between border-b border-line px-4 py-3">
                    <p className="text-sm font-semibold text-ink">Menu</p>
                    <IconButton label="Close menu" onClick={onClose}>
                        <CloseIcon />
                    </IconButton>
                </div>
                <nav className="flex-1 overflow-y-auto px-3 py-3" aria-label="Primary">
                    <ul className="space-y-1">
                        {primaryItems.map((item) => {
                            const active = isNavActive(url, item.href);
                            return (
                                <li key={item.href}>
                                    <Link
                                        href={item.href}
                                        className={`block min-h-11 rounded-md px-3 py-2.5 text-sm font-medium ${active ? 'bg-brand/10 text-brand' : 'text-ink hover:bg-surface'}`}
                                        onClick={onClose}
                                    >
                                        {item.label}
                                    </Link>
                                </li>
                            );
                        })}
                    </ul>
                    {guestActions ? (
                        <>
                            <div className="my-3 border-t border-line" />
                            <div className="space-y-1">{guestActions}</div>
                        </>
                    ) : null}
                    {accountSection ? (
                        <>
                            <div className="my-3 border-t border-line" />
                            {accountSection}
                        </>
                    ) : null}
                </nav>
            </div>
        </div>
    );
}
