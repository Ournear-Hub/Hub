import type { ReactNode } from 'react';

type VerificationFlags = {
    email?: boolean;
    mobile?: boolean;
    whatsapp?: boolean;
};

type ProfileIdentityHeaderProps = {
    name: string;
    username?: string | null;
    photoUrl?: string | null;
    businessId?: string | null;
    secondaryId?: string | null;
    secondaryIdLabel?: string;
    subtitle?: string | null;
    location?: string | null;
    verifications?: VerificationFlags;
    mobileRequired?: boolean;
    badge?: string | null;
    shape?: 'circle' | 'rounded';
    heading?: 'h1' | 'h2';
    profileHref?: string | null;
    actions?: ReactNode;
    variant?: 'banner' | 'compact';
};

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('') || '—';
}

function VerificationChip({
    label,
    verified,
    required,
}: {
    label: string;
    verified: boolean;
    required?: boolean;
}) {
    if (verified) {
        return (
            <span className="rounded-full bg-brand/10 px-2.5 py-1 text-xs font-semibold text-brand">
                {label} verified
            </span>
        );
    }

    if (required) {
        return (
            <span className="rounded-full bg-accent/15 px-2.5 py-1 text-xs font-semibold text-accent">
                {label} verification required
            </span>
        );
    }

    return (
        <span className="rounded-full bg-surface px-2.5 py-1 text-xs font-semibold text-muted">
            {label} not verified · optional
        </span>
    );
}

export default function ProfileIdentityHeader({
    name,
    username,
    photoUrl,
    businessId,
    secondaryId,
    secondaryIdLabel = 'ID',
    subtitle,
    location,
    verifications,
    mobileRequired = false,
    badge,
    shape = 'circle',
    heading = 'h2',
    profileHref,
    actions,
    variant = 'banner',
}: ProfileIdentityHeaderProps) {
    const photoClass = shape === 'circle' ? 'rounded-full' : 'rounded-xl';
    const Heading = heading;
    const compact = variant === 'compact';
    const photoSize = compact ? 'h-16 w-16' : 'h-24 w-24';
    const showVerifications =
        !compact &&
        verifications &&
        (verifications.email !== undefined ||
            verifications.mobile !== undefined ||
            verifications.whatsapp !== undefined);

    const photoNode = photoUrl ? (
        profileHref ? (
            <a href={profileHref} className="shrink-0">
                <img src={photoUrl} alt={`${name} logo`} className={`${photoSize} border border-line object-cover ${photoClass}`} />
            </a>
        ) : (
            <img src={photoUrl} alt={`${name} logo`} className={`${photoSize} shrink-0 border border-line object-cover ${photoClass}`} />
        )
    ) : profileHref ? (
        <a
            href={profileHref}
            className={`flex ${photoSize} shrink-0 items-center justify-center border border-line bg-surface text-lg font-semibold text-brand ${photoClass}`}
            aria-label={`${name} profile`}
        >
            {initials(name)}
        </a>
    ) : (
        <div
            className={`flex ${photoSize} shrink-0 items-center justify-center border border-line bg-surface text-lg font-semibold text-brand ${photoClass}`}
            aria-label="No logo"
        >
            {initials(name)}
        </div>
    );

    if (compact) {
        return (
            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div className="flex min-w-0 gap-4">
                    {photoNode}
                    <div className="min-w-0">
                        <div className="flex flex-wrap items-center gap-2">
                            <Heading className="break-words text-xl font-semibold text-ink sm:text-2xl">{name}</Heading>
                            {badge ? (
                                <span className="rounded-full bg-accent/15 px-2.5 py-1 text-xs font-semibold text-accent">{badge}</span>
                            ) : null}
                        </div>
                        {subtitle ? <p className="mt-1 text-sm font-medium text-ink">{subtitle}</p> : null}
                        {location ? <p className="mt-1 text-sm text-muted">{location}</p> : null}
                        {secondaryId ? (
                            <p className="mt-1 text-xs text-muted">
                                {secondaryIdLabel} {secondaryId}
                            </p>
                        ) : null}
                    </div>
                </div>
                {actions ? <div className="flex flex-wrap gap-2">{actions}</div> : null}
            </div>
        );
    }

    return (
        <div className="-mx-5 -mt-5 overflow-hidden rounded-t-xl sm:-mx-6 sm:-mt-6">
            <div className="h-24 bg-brand sm:h-28" aria-hidden="true" />
            <div className="flex flex-col gap-4 px-5 pb-1 pt-0 sm:flex-row sm:items-end sm:justify-between sm:px-6">
                <div className="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-end">
                    {photoUrl ? (
                        profileHref ? (
                            <a href={profileHref} className="shrink-0">
                                <img
                                    src={photoUrl}
                                    alt={`${name} photo`}
                                    className={`-mt-10 h-24 w-24 border-4 border-white object-cover ${photoClass}`}
                                />
                            </a>
                        ) : (
                        <img
                            src={photoUrl}
                            alt={`${name} photo`}
                            className={`-mt-10 h-24 w-24 shrink-0 border-4 border-white object-cover ${photoClass}`}
                        />
                        )
                    ) : (
                        profileHref ? (
                            <a
                                href={profileHref}
                                className={`-mt-10 flex h-24 w-24 shrink-0 items-center justify-center border-4 border-white bg-brand/10 text-xl font-semibold text-brand ${photoClass}`}
                                aria-label={`${name} profile`}
                            >
                                {initials(name)}
                            </a>
                        ) : (
                        <div
                            className={`-mt-10 flex h-24 w-24 shrink-0 items-center justify-center border-4 border-white bg-brand/10 text-xl font-semibold text-brand ${photoClass}`}
                            aria-label="No photo"
                        >
                            {initials(name)}
                        </div>
                        )
                    )}
                    <div className="min-w-0 pb-2">
                        <div className="flex flex-wrap items-center gap-2">
                            <Heading className="break-words text-2xl font-semibold text-ink">
                                {profileHref ? (
                                    <a href={profileHref} className="hover:text-brand">{name}</a>
                                ) : (
                                    name
                                )}
                            </Heading>
                            {badge ? (
                                <span className="rounded-full bg-accent/15 px-2.5 py-1 text-xs font-semibold text-accent">
                                    {badge}
                                </span>
                            ) : null}
                        </div>
                        {subtitle ? <p className="mt-1 text-sm font-medium text-ink">{subtitle}</p> : null}
                        {location ? <p className="mt-1 text-sm text-muted">{location}</p> : null}
                        {businessId ? <p className="mt-2 text-sm font-medium text-ink">OurNear ID {businessId}</p> : null}
                        {secondaryId ? (
                            <p className="mt-1 text-sm text-muted">
                                {secondaryIdLabel} {secondaryId}
                            </p>
                        ) : null}
                        {showVerifications ? (
                            <div className="mt-3 flex flex-wrap gap-2">
                                {verifications?.email !== undefined ? (
                                    <VerificationChip
                                        label="Email"
                                        verified={verifications.email}
                                        required={!verifications.email}
                                    />
                                ) : null}
                                {verifications?.mobile !== undefined ? (
                                    <VerificationChip
                                        label="Mobile"
                                        verified={verifications.mobile}
                                        required={mobileRequired}
                                    />
                                ) : null}
                                {verifications?.whatsapp !== undefined ? (
                                    <VerificationChip label="WhatsApp" verified={verifications.whatsapp} />
                                ) : null}
                            </div>
                        ) : null}
                    </div>
                </div>
                {actions ? <div className="flex flex-wrap gap-2 pb-2">{actions}</div> : null}
            </div>
        </div>
    );
}
