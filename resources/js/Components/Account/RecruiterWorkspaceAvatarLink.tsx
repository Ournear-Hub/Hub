import { focusRing } from '@/design-system/classNames';
import { RECRUITER_WORKSPACE_HREF } from '@/lib/recruiterAccountPaths';
import type { HeaderEmployerIdentity } from '@/types/page';

type Props = {
    identity: HeaderEmployerIdentity;
    /** h/w classes, e.g. h-9 w-9 */
    sizeClass?: string;
    className?: string;
};

export default function RecruiterWorkspaceAvatarLink({
    identity,
    sizeClass = 'h-9 w-9',
    className = '',
}: Props) {
    const label = identity.name?.trim() || 'Company workspace';
    const initial = label.charAt(0).toUpperCase() || 'C';
    const trimmedHref = identity.href?.trim() ?? '';
    const href =
        trimmedHref !== '' && trimmedHref !== '/account/profile' ? trimmedHref : RECRUITER_WORKSPACE_HREF;

    return (
        <a
            href={href}
            className={`inline-flex shrink-0 items-center justify-center ${focusRing} ${className}`}
            aria-label={`Open recruiter workspace for ${label}`}
            title={label}
        >
            {identity.logo_url ? (
                <img
                    src={identity.logo_url}
                    alt=""
                    className={`${sizeClass} rounded-md border border-line object-cover`}
                />
            ) : (
                <span
                    aria-hidden="true"
                    className={`${sizeClass} inline-flex items-center justify-center rounded-md border border-line bg-brand/10 text-xs font-semibold text-brand`}
                >
                    {initial}
                </span>
            )}
        </a>
    );
}
