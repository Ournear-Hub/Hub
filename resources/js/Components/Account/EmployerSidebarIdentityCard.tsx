import { Link } from '@inertiajs/react';
import { useEffect, useState, type ReactNode } from 'react';
import Avatar from '@/Components/atoms/Avatar';
import OurNearIcon from '@/icons/OurNearIcon';
import type { HeaderEmployerIdentity } from '@/types/page';

type Props = {
    identity: HeaderEmployerIdentity;
};

function GlobeIcon({ className = '' }: { className?: string }) {
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
            className={`h-3.5 w-3.5 shrink-0 ${className}`}
            aria-hidden="true"
        >
            <circle cx="12" cy="12" r="10" />
            <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20" />
            <path d="M2 12h20" />
        </svg>
    );
}

function MapPinIcon({ className = '' }: { className?: string }) {
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
            className={`h-3.5 w-3.5 shrink-0 ${className}`}
            aria-hidden="true"
        >
            <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0" />
            <circle cx="12" cy="10" r="3" />
        </svg>
    );
}

function CompanyLogoCircle({ name, logoUrl, href }: { name: string; logoUrl: string | null; href: string }) {
    const [imageFailed, setImageFailed] = useState(false);

    useEffect(() => {
        setImageFailed(false);
    }, [logoUrl]);

    const showImage = Boolean(logoUrl?.trim()) && !imageFailed;

    return (
        <Link
            href={href}
            className="mx-auto flex w-full max-w-[9.5rem] justify-center rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-[#F15A24]/50 sm:max-w-[10rem] lg:max-w-[8rem] xl:max-w-[8rem]"
        >
            {showImage ? (
                <span className="relative flex aspect-square w-full overflow-hidden rounded-full border-2 border-white/25 bg-white p-2 shadow-sm ring-2 ring-white/10 ring-offset-2 ring-offset-[#214449]">
                    <img
                        src={logoUrl!}
                        alt=""
                        className="h-full w-full object-contain object-center"
                        onError={() => setImageFailed(true)}
                    />
                </span>
            ) : (
                <Avatar
                    name={name}
                    src={undefined}
                    size="profileSidebar"
                    shape="circle"
                    className="ring-2 ring-white/20 ring-offset-2 ring-offset-[#214449]"
                />
            )}
        </Link>
    );
}

function IdentityMetaRow({
    icon,
    children,
    href,
    external,
}: {
    icon: 'briefcase' | 'globe' | 'pin';
    children: ReactNode;
    href?: string;
    external?: boolean;
}) {
    const iconNode =
        icon === 'globe' ? (
            <GlobeIcon className="text-white/70" />
        ) : icon === 'pin' ? (
            <MapPinIcon className="text-white/70" />
        ) : (
            <OurNearIcon name="briefcase" size="sm" className="mt-0.5 shrink-0 text-white/70" decorative />
        );

    const className =
        'mt-1.5 flex max-w-full items-start justify-center gap-1.5 px-2 text-xs font-medium text-white/90';

    if (href) {
        return (
            <a
                href={href}
                target={external ? '_blank' : undefined}
                rel={external ? 'noopener noreferrer' : undefined}
                className={`${className} text-[#F15A24] hover:underline`}
            >
                {iconNode}
                <span className="min-w-0 truncate text-left">{children}</span>
            </a>
        );
    }

    return (
        <div className={className}>
            {iconNode}
            <span className="min-w-0 line-clamp-2 text-left leading-snug">{children}</span>
        </div>
    );
}

export default function EmployerSidebarIdentityCard({ identity }: Props) {
    const label = identity.name?.trim() || '';
    const website = identity.company_website?.trim() || null;
    const websiteLabel = website ? website.replace(/^https?:\/\//i, '').replace(/\/$/, '') : null;
    const industry = identity.industry?.trim() || null;
    const location = identity.location?.trim() || null;

    return (
        <div className="border-b border-white/15 px-4 pb-4 pt-4 text-center">
            <CompanyLogoCircle name={label || 'Company'} logoUrl={identity.logo_url} href={identity.href} />

            {label ? (
                <p className="mt-3 line-clamp-2 px-1 text-sm font-bold leading-snug text-white">{label}</p>
            ) : null}

            {industry ? <IdentityMetaRow icon="briefcase">{industry}</IdentityMetaRow> : null}

            {location ? <IdentityMetaRow icon="pin">{location}</IdentityMetaRow> : null}

            {website && websiteLabel ? (
                <IdentityMetaRow icon="globe" href={website} external>
                    {websiteLabel}
                </IdentityMetaRow>
            ) : null}
        </div>
    );
}
