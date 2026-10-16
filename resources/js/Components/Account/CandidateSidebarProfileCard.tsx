import Avatar from '@/Components/atoms/Avatar';
import { typography } from '@/design-system/classNames';
import OurNearIcon from '@/icons/OurNearIcon';
import type { SharedPageProps } from '@/types/page';
import type { ReactNode } from 'react';

export type CandidateSidebarProfile = NonNullable<SharedPageProps['candidateSidebarProfile']>;

type Props = {
    profile: CandidateSidebarProfile;
};

function normalized(value: string | null | undefined): string | null {
    const trimmed = value?.trim();

    return trimmed ? trimmed : null;
}

function IdentityRow({
    icon,
    children,
    prominent = false,
}: {
    icon?: 'briefcase' | 'building';
    children: ReactNode;
    prominent?: boolean;
}) {
    return (
        <div className="flex items-start justify-center gap-2 text-left">
            {icon ? (
                <OurNearIcon name={icon} size="sm" className="mt-0.5 shrink-0 text-muted" decorative />
            ) : (
                <span className="w-4 shrink-0" aria-hidden="true" />
            )}
            <div
                className={`min-w-0 flex-1 break-words text-sm ${prominent ? 'font-semibold text-ink' : `text-muted ${typography.secondary}`}`}
            >
                {children}
            </div>
        </div>
    );
}

export default function CandidateSidebarProfileCard({ profile }: Props) {
    const username = normalized(profile.username);
    const designation = normalized(profile.designation);
    const headline = normalized(profile.professional_headline);
    const organization = normalized(profile.organization);
    const showHeadline = headline !== null && headline !== designation;

    return (
        <div className="bg-white px-3 pb-4 pt-4 text-center sm:px-4">
            <div className="mx-auto flex w-full max-w-[9.5rem] justify-center sm:max-w-[10rem] lg:max-w-[11rem]">
                <Avatar
                    name={profile.name}
                    src={profile.photo_url ?? undefined}
                    size="profileSidebar"
                    shape="circle"
                    className="ring-2 ring-line ring-offset-2 ring-offset-white"
                />
            </div>
            <div className="mt-2.5 min-w-0 space-y-1 px-0.5">
                <h2 className={`${typography.cardTitle} font-semibold text-brand`}>{profile.name}</h2>
                {username ? <p className={`${typography.caption} text-muted`}>^{username}</p> : null}
                {designation ? (
                    <IdentityRow icon="briefcase">
                        <span className={`${typography.body} font-medium text-ink`}>{designation}</span>
                    </IdentityRow>
                ) : null}
                {showHeadline ? (
                    <IdentityRow icon={designation ? undefined : 'briefcase'}>
                        <span className="line-clamp-3">{headline}</span>
                    </IdentityRow>
                ) : null}
                {organization ? (
                    <IdentityRow icon="building" prominent>
                        <span className="line-clamp-2">{organization}</span>
                    </IdentityRow>
                ) : null}
            </div>
        </div>
    );
}
