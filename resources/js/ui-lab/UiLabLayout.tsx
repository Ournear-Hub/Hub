import { Head, Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import { useMemo, useState } from 'react';
import type { UiLabSectionId } from '@/ui-lab/metadata';
import { uiLabNav } from '@/ui-lab/metadata';

type Props = PropsWithChildren<{
    section: UiLabSectionId;
    title?: string;
}>;

function navIsActive(itemId: string, section: UiLabSectionId): boolean {
    if (itemId === section) {
        return true;
    }
    if (
        itemId === 'atoms' &&
        (section === 'atoms-button' ||
            section === 'atoms-icon-button' ||
            section === 'atoms-input' ||
            section === 'atoms-textarea' ||
            section === 'atoms-select' ||
            section === 'atoms-checkbox' ||
            section === 'atoms-radio' ||
            section === 'atoms-switch' ||
            section === 'atoms-skeleton' ||
            section === 'atoms-spinner' ||
            section === 'atoms-progress' ||
            section === 'atoms-divider' ||
            section === 'atoms-link' ||
            section === 'atoms-visually-hidden')
    ) {
        return true;
    }
    if (
        itemId === 'molecules' &&
        (section === 'molecules-form-field' ||
            section === 'molecules-search-field' ||
            section === 'molecules-password-field' ||
            section === 'molecules-file-field' ||
            section === 'molecules-date-field' ||
            section === 'molecules-time-field' ||
            section === 'molecules-date-time-field' ||
            section === 'molecules-number-field' ||
            section === 'molecules-email-field' ||
            section === 'molecules-url-field' ||
            section === 'molecules-field-group' ||
            section === 'molecules-input-group' ||
            section === 'molecules-field-set' ||
            section === 'molecules-combobox' ||
            section === 'molecules-multi-select' ||
            section === 'molecules-segmented-control' ||
            section === 'molecules-accordion' ||
            section === 'molecules-tooltip' ||
            section === 'molecules-popover' ||
            section === 'molecules-dropdown-menu' ||
            section === 'molecules-alert' ||
            section === 'molecules-toast' ||
            section === 'molecules-empty-state')
    ) {
        return true;
    }
    return false;
}

export default function UiLabLayout({ section, title = 'OurNear UI Lab', children }: Props) {
    const [mobileOpen, setMobileOpen] = useState(false);

    const pageTitle = useMemo(() => `${title} — Internal`, [title]);

    return (
        <div className="min-h-screen bg-canvas text-ink">
            <Head title={pageTitle}>
                <meta head-key="robots" name="robots" content="noindex, nofollow" />
            </Head>

            <header className="border-b border-line bg-white">
                <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-wide text-brand">OurNear UI Lab</p>
                        <h1 className="text-xl font-semibold text-ink sm:text-2xl">Corporate Design System</h1>
                    </div>
                    <div className="flex items-center gap-3">
                        <p className="hidden text-xs text-muted sm:block">Internal · authenticated · not indexed</p>
                        <button
                            type="button"
                            className="inline-flex min-h-10 items-center rounded-md border border-line bg-white px-3 text-sm font-semibold text-ink lg:hidden"
                            onClick={() => setMobileOpen((open) => !open)}
                            aria-expanded={mobileOpen}
                        >
                            Menu
                        </button>
                    </div>
                </div>
            </header>

            <div className="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:flex-row lg:items-start">
                <nav
                    className={`lg:w-56 lg:shrink-0 ${mobileOpen ? 'block' : 'hidden lg:block'}`}
                    aria-label="UI Lab sections"
                >
                    <ul className="space-y-1 rounded-xl border border-line bg-white p-2 shadow-sm">
                        {uiLabNav.map((item) => {
                            if (item.comingSoon) {
                                return (
                                    <li key={item.id}>
                                        <span className="flex items-center justify-between rounded-md px-3 py-2 text-sm text-muted">
                                            {item.label}
                                            <span className="text-xs uppercase">Soon</span>
                                        </span>
                                    </li>
                                );
                            }

                            if (item.children?.length) {
                                return (
                                    <li key={item.id}>
                                        <p
                                            className={`px-3 py-2 text-sm font-semibold ${navIsActive(item.id, section) ? 'text-brand' : 'text-ink'}`}
                                        >
                                            {item.label}
                                        </p>
                                        <ul className="mb-1 ml-2 space-y-0.5 border-l border-line pl-2">
                                            {item.children.map((child) =>
                                                child.href ? (
                                                    <li key={child.id}>
                                                        <Link
                                                            href={child.href}
                                                            className={`block rounded-md px-3 py-2 text-sm ${
                                                                section === child.id
                                                                    ? 'bg-brand/10 font-semibold text-brand'
                                                                    : 'text-ink hover:bg-surface'
                                                            }`}
                                                            onClick={() => setMobileOpen(false)}
                                                        >
                                                            {child.label}
                                                        </Link>
                                                    </li>
                                                ) : null,
                                            )}
                                        </ul>
                                    </li>
                                );
                            }

                            return item.href ? (
                                <li key={item.id}>
                                    <Link
                                        href={item.href}
                                        className={`block rounded-md px-3 py-2 text-sm ${
                                            section === item.id
                                                ? 'bg-brand/10 font-semibold text-brand'
                                                : 'text-ink hover:bg-surface'
                                        }`}
                                        onClick={() => setMobileOpen(false)}
                                    >
                                        {item.label}
                                    </Link>
                                </li>
                            ) : null;
                        })}
                    </ul>
                </nav>

                <main className="min-w-0 flex-1">{children}</main>
            </div>
        </div>
    );
}
