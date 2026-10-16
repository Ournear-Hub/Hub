import {
    applicantResumeOverviewActions,
    applicantResumeOverviewEmpty,
    applicantResumeOverviewFootnote,
    applicantResumeOverviewSummary,
    applicantResumeOverviewSummaryLabel,
    applicantResumeOverviewSummaryList,
    applicantResumeOverviewSummaryTitle,
    applicantResumeOverviewTitle,
    cardDensity,
} from '@/design-system/classNames';
import type { ReactNode } from 'react';

export type ApplicantResumeOverviewSummary = {
    designation?: string | null;
    experience?: string | null;
    qualification?: string | null;
    location?: string | null;
    currentEmployer?: string | null;
    skillsLine?: string | null;
    educationLine?: string | null;
};

export type ApplicantResumeOverviewProps = {
    /** Resume section heading (default: Resume). */
    title?: string;
    /** Optional professional summary — only supplied fields are rendered. */
    summary?: ApplicantResumeOverviewSummary;
    /** Shown when `actions` is not provided. */
    emptyMessage: string;
    /** View/download links or buttons supplied by the feature layer. */
    actions?: ReactNode;
    footnote?: ReactNode;
    className?: string;
    'aria-label'?: string;
};

function hasSummary(summary?: ApplicantResumeOverviewSummary): summary is ApplicantResumeOverviewSummary {
    if (!summary) {
        return false;
    }

    return Boolean(
        summary.designation?.trim() ||
            summary.experience?.trim() ||
            summary.qualification?.trim() ||
            summary.location?.trim() ||
            summary.currentEmployer?.trim() ||
            summary.skillsLine?.trim() ||
            summary.educationLine?.trim(),
    );
}

function summaryRows(summary: ApplicantResumeOverviewSummary): Array<{ label: string; value: string }> {
    const rows: Array<{ label: string; value: string }> = [];
    if (summary.designation?.trim()) {
        rows.push({ label: 'Designation', value: summary.designation.trim() });
    }
    if (summary.experience?.trim()) {
        rows.push({ label: 'Experience', value: summary.experience.trim() });
    }
    if (summary.currentEmployer?.trim()) {
        rows.push({ label: 'Current employer', value: summary.currentEmployer.trim() });
    }
    if (summary.qualification?.trim()) {
        rows.push({ label: 'Qualification', value: summary.qualification.trim() });
    }
    if (summary.location?.trim()) {
        rows.push({ label: 'Location', value: summary.location.trim() });
    }
    if (summary.skillsLine?.trim()) {
        rows.push({ label: 'Skills', value: summary.skillsLine.trim() });
    }
    if (summary.educationLine?.trim()) {
        rows.push({ label: 'Education', value: summary.educationLine.trim() });
    }
    return rows;
}

export default function ApplicantResumeOverview({
    title = 'Resume',
    summary,
    emptyMessage,
    actions,
    footnote,
    className = '',
    'aria-label': ariaLabel = 'Applicant resume overview',
}: ApplicantResumeOverviewProps) {
    const rootClass = `${cardDensity.compact} ${className}`.trim();
    const showSummary = hasSummary(summary);
    const rows = showSummary ? summaryRows(summary) : [];

    return (
        <section className={rootClass} aria-label={ariaLabel}>
            {showSummary ? (
                <div className={applicantResumeOverviewSummary}>
                    <h3 className={applicantResumeOverviewSummaryTitle}>Professional summary</h3>
                    <dl className={applicantResumeOverviewSummaryList}>
                        {rows.map((row) => (
                            <div key={row.label}>
                                <dt className={applicantResumeOverviewSummaryLabel}>{row.label}</dt>
                                <dd className="font-medium text-ink">{row.value}</dd>
                            </div>
                        ))}
                    </dl>
                </div>
            ) : null}

            <h2 className={applicantResumeOverviewTitle}>{title}</h2>
            {actions ? (
                <div className={applicantResumeOverviewActions}>{actions}</div>
            ) : (
                <p className={applicantResumeOverviewEmpty}>{emptyMessage}</p>
            )}
            {footnote ? <div className={applicantResumeOverviewFootnote}>{footnote}</div> : null}
        </section>
    );
}
