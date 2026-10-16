import StatusBadge from '@/Components/Account/StatusBadge';
import {
    applicantSummaryCardActions,
    applicantSummaryCardAvatarImage,
    applicantSummaryCardBody,
    applicantSummaryCardContext,
    applicantSummaryCardContextValue,
    applicantSummaryCardDesignation,
    applicantSummaryCardInitials,
    applicantSummaryCardLayout,
    applicantSummaryCardMeta,
    applicantSummaryCardNameRow,
    applicantSummaryCardNotices,
    applicantSummaryCardReference,
    applicantSummaryCardRoot,
    applicantSummaryCardTime,
    applicantSummaryCardTitle,
} from '@/design-system/classNames';
import type { ReactNode } from 'react';

export type ApplicantSummaryCardProps = {
    name: string;
    statusLabel: string;
    photoUrl?: string | null;
    /** Shown when `photoUrl` is absent (caller-supplied, typically initials). */
    initials: string;
    designation?: string | null;
    /** e.g. location · experience · qualification */
    metaLine?: string | null;
    /** Prefix before role title, e.g. "Applied for" or "Submitted for". */
    contextPrefix: string;
    contextTitle: string;
    timeLine?: string | null;
    /** e.g. Application ID line */
    referenceLine?: string | null;
    notices?: ReactNode;
    actions?: ReactNode;
    className?: string;
};

export default function ApplicantSummaryCard({
    name,
    statusLabel,
    photoUrl,
    initials,
    designation,
    metaLine,
    contextPrefix,
    contextTitle,
    timeLine,
    referenceLine,
    notices,
    actions,
    className = '',
}: ApplicantSummaryCardProps) {
    const rootClass = `${applicantSummaryCardRoot} ${className}`.trim();

    return (
        <article className={rootClass}>
            <div className={applicantSummaryCardLayout}>
                {photoUrl ? (
                    <img src={photoUrl} alt="" className={applicantSummaryCardAvatarImage} />
                ) : (
                    <div className={applicantSummaryCardInitials} aria-hidden="true">
                        {initials}
                    </div>
                )}
                <div className={applicantSummaryCardBody}>
                    <div className={applicantSummaryCardNameRow}>
                        <h2 className={applicantSummaryCardTitle}>{name}</h2>
                        <StatusBadge status={statusLabel} />
                    </div>
                    {designation ? <p className={applicantSummaryCardDesignation}>{designation}</p> : null}
                    {metaLine ? <p className={applicantSummaryCardMeta}>{metaLine}</p> : null}
                    <p className={applicantSummaryCardContext}>
                        {contextPrefix}: <span className={applicantSummaryCardContextValue}>{contextTitle}</span>
                    </p>
                    {timeLine ? <p className={applicantSummaryCardTime}>{timeLine}</p> : null}
                    {referenceLine ? <p className={applicantSummaryCardReference}>{referenceLine}</p> : null}
                    {notices ? <div className={applicantSummaryCardNotices}>{notices}</div> : null}
                    {actions ? <div className={applicantSummaryCardActions}>{actions}</div> : null}
                </div>
            </div>
        </article>
    );
}
