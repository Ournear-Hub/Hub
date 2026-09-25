import type { MouseEvent } from 'react';
import JobActions from '@/Components/Public/JobActions';
import type { PublicJobCard } from '@/types/jobs';

type JobCardProps = {
    job: PublicJobCard;
    selected?: boolean;
    onSelect?: (slug: string) => void;
};

export default function JobCard({ job, selected = false, onSelect }: JobCardProps) {
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

    return (
        <article
            role="button"
            tabIndex={0}
            onClick={select}
            onKeyDown={(event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    select();
                }
            }}
            className={`cursor-pointer rounded-xl border bg-white p-5 text-left transition-shadow hover:shadow-sm ${
                selected ? 'border-brand ring-1 ring-brand/20' : 'border-line'
            }`}
        >
            <div className="flex min-w-0 items-start gap-3">
                {job.company_logo_url ? (
                    <img
                        src={job.company_logo_url}
                        alt={`${job.company_name} logo`}
                        className="h-12 w-12 shrink-0 rounded-lg border border-line object-cover"
                    />
                ) : (
                    <div
                        aria-label={`${job.company_name} logo placeholder`}
                        className="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-brand/10 text-sm font-semibold text-brand"
                    >
                        {companyInitials}
                    </div>
                )}
                <div className="min-w-0 flex-1">
                    <h3 className="text-lg font-semibold text-ink">{job.title}</h3>
                    <p className="mt-1 truncate text-sm font-medium text-brand">{job.company_name}</p>
                    <p className="mt-1 text-sm text-muted">{job.location}</p>
                </div>
            </div>
            <dl className="mt-4 grid gap-x-4 gap-y-2 text-xs text-muted sm:grid-cols-2 lg:grid-cols-1">
                {job.employment_type ? <div><dt className="inline font-medium text-ink">Type: </dt><dd className="inline">{job.employment_type}</dd></div> : null}
                {job.salary ? <div><dt className="inline font-medium text-ink">Salary / CTC: </dt><dd className="inline">{job.salary}</dd></div> : null}
                {job.experience ? <div><dt className="inline font-medium text-ink">Experience: </dt><dd className="inline">{job.experience}</dd></div> : null}
                <div><dt className="inline font-medium text-ink">Posted: </dt><dd className="inline">{job.posted_label ?? '—'}</dd></div>
            </dl>
            <p className="mt-2 text-xs text-muted">{job.closing_label ?? (job.is_closed ? 'Closed' : 'Open')}</p>
            <div className="mt-4 border-t border-line pt-4" onClick={stop}>
                <JobActions job={job} />
            </div>
        </article>
    );
}
