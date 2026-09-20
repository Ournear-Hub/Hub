import { Link } from '@inertiajs/react';
import type { PublicJobCard } from '@/types/jobs';

type JobCardProps = {
    job: PublicJobCard;
};

export default function JobCard({ job }: JobCardProps) {
    return (
        <article className="flex h-full flex-col rounded-xl border border-line bg-white p-5">
            {job.featured && (
                <p className="text-xs font-medium uppercase tracking-wide text-accent">Featured</p>
            )}
            <h3 className="mt-1 text-lg font-semibold text-ink">{job.title}</h3>
            <p className="mt-1 text-sm font-medium text-brand">{job.project}</p>
            <p className="mt-2 text-sm text-muted">
                {job.location} · {job.employment_type}
            </p>
            <dl className="mt-3 grid gap-1 text-sm text-muted">
                <div>
                    <span className="font-medium text-ink">Industry: </span>
                    {job.industry}
                </div>
                <div>
                    <span className="font-medium text-ink">Experience: </span>
                    {job.experience}
                </div>
                {job.salary && (
                    <div>
                        <span className="font-medium text-ink">Salary: </span>
                        {job.salary}
                    </div>
                )}
                <div>
                    <span className="font-medium text-ink">Posted: </span>
                    {job.posted_date ?? '—'}
                </div>
                <div>
                    <span className="font-medium text-ink">Closes: </span>
                    {job.closing_date ?? 'Open'}
                </div>
            </dl>
            <Link href={`/jobs/${job.slug}`} className="mt-auto pt-4 text-sm font-semibold text-brand hover:text-accent">
                View role
            </Link>
        </article>
    );
}
