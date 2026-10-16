import { Link } from '@inertiajs/react';
import CompanyVerificationIndicator from '@/Components/Public/CompanyVerificationIndicator';
import JobActions from '@/Components/Public/JobActions';
import OurNearIcon from '@/icons/OurNearIcon';
import type { PublicJobCard } from '@/types/jobs';
import { publicJobsWorkspaceWithJob } from '@/utils/publicJobsUrl';

type JobCardProps = {
    job: PublicJobCard;
    compact?: boolean;
    selected?: boolean;
    onSelect?: (slug: string) => void;
    hideActions?: boolean;
    linkable?: boolean;
    /** Open title and “View job” in the split-view jobs board instead of the standalone job page. */
    openInWorkspace?: boolean;
};

function MetaRow({ icon, text }: { icon: string; text: string }) {
    return (
        <span className="inline-flex min-w-0 max-w-full items-center gap-1.5 text-xs text-muted">
            <OurNearIcon name={icon} size="sm" className="shrink-0 text-brand/70" decorative />
            <span className="line-clamp-2 break-words sm:truncate">{text}</span>
        </span>
    );
}

export default function JobCard({
    job,
    compact = false,
    selected = false,
    onSelect,
    hideActions = false,
    linkable = true,
    openInWorkspace = false,
}: JobCardProps) {
    const jobHref = openInWorkspace ? publicJobsWorkspaceWithJob(job.slug) : `/jobs/${job.slug}`;
    const useSplitSelect = Boolean(onSelect);
    const skills = job.skills ?? [];
    const priorityLabel = job.listing_priority_label ?? (job.urgent ? 'Urgent' : null);
    const showStatus = job.is_closed || job.status_label || priorityLabel;
    const companyHref = job.company_url;

    const cardSurface = selected
        ? 'border-accent bg-white shadow-md ring-2 ring-accent/20'
        : 'border-line bg-white shadow-sm hover:border-[#214449]/35 hover:shadow-md';

    const titleControl = useSplitSelect ? (
        <button
            type="button"
            onClick={() => onSelect?.(job.slug)}
            className={`line-clamp-3 w-full break-words text-left font-semibold leading-snug text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/40 ${compact ? 'text-base' : 'text-base sm:text-lg'}`}
        >
            {job.title}
        </button>
    ) : linkable ? (
        <Link
            href={jobHref}
            className={`line-clamp-3 break-words font-semibold leading-snug text-brand hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/40 ${compact ? 'text-base' : 'text-base sm:text-lg'}`}
        >
            {job.title}
        </Link>
    ) : (
        <p className={`line-clamp-3 break-words font-semibold leading-snug text-brand ${compact ? 'text-base' : 'text-base sm:text-lg'}`}>
            {job.title}
        </p>
    );

    const companyNameControl = companyHref ? (
        <Link
            href={companyHref}
            className="line-clamp-2 min-w-0 text-sm font-medium text-brand hover:underline"
            onClick={(event) => event.stopPropagation()}
        >
            {job.company_name}
        </Link>
    ) : (
        <span className="line-clamp-2 min-w-0 text-sm font-medium text-ink">{job.company_name}</span>
    );

    const padding = compact ? 'p-3 sm:p-4' : 'p-4 sm:p-5';
    const showSideActions = !hideActions && !useSplitSelect;

    return (
        <article
            className={`rounded-xl border border-l-4 border-l-[#214449] transition-all focus-within:ring-2 focus-within:ring-brand/25 ${cardSurface}`}
            aria-current={selected ? 'true' : undefined}
        >
            <div
                className={`flex min-w-0 flex-col gap-3 ${padding} ${showSideActions ? 'sm:flex-row sm:items-start sm:justify-between sm:gap-4' : ''}`}
            >
                <div className="flex min-w-0 flex-1 flex-col gap-2">
                    <div className="flex flex-wrap items-start gap-2">
                        <div className="min-w-0 flex-1 space-y-1">
                            {titleControl}
                            <div className="flex min-w-0 flex-wrap items-center gap-1.5">
                                {companyNameControl}
                                <CompanyVerificationIndicator verified={job.company_verified === true} />
                            </div>
                        </div>
                        {showStatus ? (
                            <span
                                className={`shrink-0 rounded-full px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide ${
                                    job.is_closed ? 'bg-surface text-muted' : 'bg-brand/10 text-brand'
                                }`}
                            >
                                {job.is_closed
                                    ? 'Closed'
                                    : priorityLabel ?? job.status_label ?? 'Open'}
                            </span>
                        ) : null}
                    </div>

                    <div className="flex min-w-0 flex-wrap gap-x-4 gap-y-1.5">
                        {job.location ? <MetaRow icon="building" text={job.location} /> : null}
                        {job.employment_type ? <MetaRow icon="clipboard" text={job.employment_type} /> : null}
                        {job.experience ? <MetaRow icon="briefcase" text={job.experience} /> : null}
                        {job.salary ? <MetaRow icon="file" text={job.salary} /> : null}
                    </div>

                    {!compact && skills.length > 0 ? (
                        <ul className="flex flex-wrap gap-1.5">
                            {skills.slice(0, 4).map((skill) => (
                                <li
                                    key={skill}
                                    className="rounded-full border border-line bg-surface px-2.5 py-0.5 text-[11px] font-medium text-ink"
                                >
                                    {skill}
                                </li>
                            ))}
                            {skills.length > 4 ? (
                                <li className="rounded-full px-2 py-0.5 text-[11px] text-muted">+{skills.length - 4} more</li>
                            ) : null}
                        </ul>
                    ) : null}

                    {!compact ? (
                        <p className="text-xs text-muted">
                            Posted {job.posted_label ?? '—'}
                            {job.closing_label ? ` · Closes ${job.closing_label.replace(/^Closes /i, '')}` : ''}
                        </p>
                    ) : null}

                    {!hideActions && !useSplitSelect && linkable ? (
                        <div className="flex flex-wrap items-center gap-2 pt-1 sm:hidden">
                            <Link
                                href={jobHref}
                                className="inline-flex min-h-9 items-center rounded-md bg-[#F15A24] px-3.5 text-sm font-semibold text-white hover:opacity-95"
                            >
                                View job
                            </Link>
                        </div>
                    ) : null}
                </div>

                {showSideActions ? (
                    <div className="flex shrink-0 flex-row items-center justify-between gap-2 sm:flex-col sm:items-end sm:justify-start">
                        <div className="hidden sm:flex sm:flex-col sm:items-end sm:gap-1">
                            {linkable ? (
                                <Link
                                    href={jobHref}
                                    className="inline-flex min-h-9 items-center rounded-md bg-[#F15A24] px-3.5 text-sm font-semibold text-white hover:opacity-95"
                                >
                                    View job
                                </Link>
                            ) : null}
                        </div>
                        <JobActions job={job} variant="card" layout="icon" />
                    </div>
                ) : null}
            </div>
        </article>
    );
}
