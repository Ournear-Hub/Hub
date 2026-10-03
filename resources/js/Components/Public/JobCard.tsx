import type { MouseEvent } from 'react';
import { Link } from '@inertiajs/react';
import JobActions from '@/Components/Public/JobActions';
import type { PublicJobCard } from '@/types/jobs';

type JobCardProps = {
    job: PublicJobCard;
    selected?: boolean;
    onSelect?: (slug: string) => void;
    compact?: boolean;
    /** Compact jobs-board list row (desktop split + mobile list). */
    list?: boolean;
};

export default function JobCard({ job, selected = false, onSelect, compact = false, list = false }: JobCardProps) {
    const dense = list || compact;
    const companyInitials =
        job.company_name
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map((part) => part[0])
            .join('')
            .toUpperCase() || 'CO';

    const select = () => {
        if (onSelect) {
            onSelect(job.slug);
            return;
        }
        window.location.assign(`/jobs/${job.slug}`);
    };

    const stop = (event: MouseEvent) => {
        event.stopPropagation();
    };

    const metaParts = [job.experience, job.employment_type, job.salary].filter(Boolean);

    return (
        <article
            role="button"
            tabIndex={0}
            aria-current={selected ? 'true' : undefined}
            onClick={select}
            onKeyDown={(event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    select();
                }
            }}
            className={`min-w-0 max-w-full cursor-pointer overflow-hidden rounded-lg border bg-white text-left transition-colors ${
                dense ? 'p-3' : 'p-5'
            } ${
                selected
                    ? 'border-brand bg-brand/[0.04] shadow-[inset_3px_0_0_0_var(--color-accent,#F15A24)]'
                    : 'border-line hover:border-brand/40 hover:bg-surface/40'
            }`}
        >
            <div className="flex min-w-0 items-start gap-2.5">
                {!list && (job.company_logo_url ? (
                    job.company_url ? (
                        <a href={job.company_url} className="shrink-0" onClick={stop}>
                            <img
                                src={job.company_logo_url}
                                alt=""
                                className={`rounded-md border border-line object-cover ${dense ? 'h-10 w-10' : 'h-12 w-12'}`}
                            />
                        </a>
                    ) : (
                        <img
                            src={job.company_logo_url}
                            alt=""
                            className={`shrink-0 rounded-md border border-line object-cover ${dense ? 'h-10 w-10' : 'h-12 w-12'}`}
                        />
                    )
                ) : (
                    <div
                        aria-hidden
                        className={`flex shrink-0 items-center justify-center rounded-md bg-brand/10 text-xs font-semibold text-brand ${dense ? 'h-10 w-10' : 'h-12 w-12'}`}
                    >
                        {companyInitials}
                    </div>
                ))}

                <div className="min-w-0 flex-1">
                    {job.urgent && !list ? (
                        <p className="text-[10px] font-semibold uppercase tracking-wide text-accent">Urgent</p>
                    ) : null}
                    {list ? (
                        <Link
                            href={`/jobs/${job.slug}`}
                            className={`break-words font-semibold leading-snug text-ink hover:text-brand ${list ? 'text-sm' : dense ? 'text-base' : 'text-lg'}`}
                            onClick={(event) => {
                                event.stopPropagation();
                                if (window.matchMedia('(min-width: 1024px)').matches && onSelect) {
                                    event.preventDefault();
                                    onSelect(job.slug);
                                }
                            }}
                        >
                            {job.title}
                        </Link>
                    ) : (
                        <h3
                            className={`break-words font-semibold leading-snug text-ink ${dense ? 'text-base' : 'text-lg'}`}
                        >
                            {job.title}
                        </h3>
                    )}
                    {job.company_url ? (
                        <a
                            href={job.company_url}
                            className="mt-0.5 block truncate text-xs font-medium text-brand hover:underline sm:text-sm"
                            onClick={stop}
                        >
                            {job.company_name}
                        </a>
                    ) : (
                        <p className="mt-0.5 truncate text-xs font-medium text-brand sm:text-sm">{job.company_name}</p>
                    )}
                    <p className="mt-0.5 truncate text-xs text-muted">{job.location}</p>
                    {list && metaParts.length > 0 ? (
                        <p className="mt-1 line-clamp-2 text-[11px] leading-relaxed text-muted">{metaParts.join(' · ')}</p>
                    ) : null}
                </div>
            </div>

            {!list ? (
                <>
                    <dl className={`mt-3 grid gap-x-4 gap-y-1.5 text-xs text-muted ${dense ? 'grid-cols-1' : 'sm:grid-cols-2 lg:grid-cols-1'}`}>
                        {job.employment_type ? (
                            <div>
                                <dt className="inline font-medium text-ink">Type: </dt>
                                <dd className="inline">{job.employment_type}</dd>
                            </div>
                        ) : null}
                        {job.salary ? (
                            <div>
                                <dt className="inline font-medium text-ink">Salary / CTC: </dt>
                                <dd className="inline">{job.salary}</dd>
                            </div>
                        ) : null}
                        {job.experience ? (
                            <div>
                                <dt className="inline font-medium text-ink">Experience: </dt>
                                <dd className="inline">{job.experience}</dd>
                            </div>
                        ) : null}
                        {!dense && job.joining_date ? (
                            <div>
                                <dt className="inline font-medium text-ink">Joining: </dt>
                                <dd className="inline">{job.joining_date}</dd>
                            </div>
                        ) : null}
                        {!dense && job.site ? (
                            <div>
                                <dt className="inline font-medium text-ink">Site: </dt>
                                <dd className="inline">{job.site}</dd>
                            </div>
                        ) : null}
                        {!dense && job.accommodation ? (
                            <div className="sm:col-span-2 lg:col-span-1">
                                <dt className="inline font-medium text-ink">Accommodation: </dt>
                                <dd className="inline">{job.accommodation}</dd>
                            </div>
                        ) : null}
                        <div>
                            <dt className="inline font-medium text-ink">Posted: </dt>
                            <dd className="inline">{job.posted_label ?? '—'}</dd>
                        </div>
                    </dl>
                    <p className="mt-2 text-xs text-muted">{job.closing_label ?? (job.is_closed ? 'Closed' : 'Open')}</p>
                </>
            ) : (
                <p className="mt-1.5 text-[11px] text-muted">
                    Posted {job.posted_label ?? '—'}
                    {job.closing_label ? ` · ${job.closing_label}` : ''}
                </p>
            )}

            <div className={`${list ? 'mt-2 border-t border-line/80 pt-2' : 'mt-4 border-t border-line pt-4'}`} onClick={stop}>
                <JobActions job={job} variant={list ? 'list' : 'card'} />
            </div>
        </article>
    );
}
