import type { ReactNode } from 'react';
import { Link } from '@inertiajs/react';
import ProfileIdentityHeader from '@/Components/Profile/ProfileIdentityHeader';

type ProfileTab = {
    href: string;
    label: string;
    active?: boolean;
};

type ProfileShellProps = {
    name: string;
    username?: string | null;
    photoUrl?: string | null;
    businessId?: string | null;
    secondaryId?: string | null;
    secondaryIdLabel?: string;
    subtitle?: string | null;
    location?: string | null;
    verifications?: {
        email?: boolean;
        mobile?: boolean;
        whatsapp?: boolean;
    };
    mobileRequired?: boolean;
    badge?: string | null;
    shape?: 'circle' | 'rounded';
    heading?: 'h1' | 'h2';
    profileHref?: string | null;
    actions?: ReactNode;
    tabs?: ProfileTab[];
    headerVariant?: 'banner' | 'compact';
    children: ReactNode;
};

export default function ProfileShell({
    name,
    username,
    photoUrl,
    businessId,
    secondaryId,
    secondaryIdLabel,
    subtitle,
    location,
    verifications,
    mobileRequired,
    badge,
    shape,
    heading,
    profileHref,
    actions,
    tabs,
    headerVariant = 'banner',
    children,
}: ProfileShellProps) {
    return (
        <div className="min-w-0 space-y-6">
            <section className="rounded-xl border border-line bg-white p-5 sm:p-6">
                <ProfileIdentityHeader
                    variant={headerVariant}
                    name={name}
                    username={username}
                    photoUrl={photoUrl}
                    businessId={businessId}
                    secondaryId={secondaryId}
                    secondaryIdLabel={secondaryIdLabel}
                    subtitle={subtitle}
                    location={location}
                    verifications={verifications}
                    mobileRequired={mobileRequired}
                    badge={badge}
                    shape={shape}
                    heading={heading}
                    profileHref={profileHref}
                    actions={actions}
                />
                {tabs && tabs.length > 0 ? (
                    <nav className="mt-5 flex flex-wrap gap-2 border-t border-line pt-4" aria-label="Profile sections">
                        {tabs.map((tab) => (
                            <Link
                                key={tab.href}
                                href={tab.href}
                                className={`rounded-md px-3 py-1.5 text-sm font-medium ${
                                    tab.active ? 'bg-brand text-white' : 'text-muted hover:bg-surface hover:text-ink'
                                }`}
                            >
                                {tab.label}
                            </Link>
                        ))}
                    </nav>
                ) : null}
            </section>
            {children}
        </div>
    );
}
