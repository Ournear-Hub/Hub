import { Link, router } from '@inertiajs/react';
import { useState, type MouseEvent } from 'react';
import SaveJobButton from '@/Components/Public/SaveJobButton';
import type { PublicJobCard } from '@/types/jobs';

type JobActionsProps = {
    job: PublicJobCard;
    variant?: 'card' | 'detail';
};

export default function JobActions({ job, variant = 'card' }: JobActionsProps) {
    const [pending, setPending] = useState(false);
    const isDetail = variant === 'detail';
    const buttonClass = isDetail
        ? 'inline-flex items-center justify-center rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white disabled:bg-disabled'
        : 'rounded-md bg-accent px-3 py-1.5 text-sm font-semibold text-white disabled:bg-disabled';
    const closedClass = isDetail
        ? 'inline-flex items-center justify-center rounded-md border border-line px-4 py-2.5 text-sm font-semibold text-muted'
        : 'rounded-md border border-line px-3 py-1.5 text-sm font-semibold text-muted';

    const apply = (event: MouseEvent) => {
        event.stopPropagation();
        if (pending) {
            return;
        }
        setPending(true);
        router.post(`/jobs/${job.slug}/apply`, {}, {
            preserveScroll: true,
            onFinish: () => setPending(false),
        });
    };

    let applyControl = null;
    if (job.apply_state === 'hidden') {
        applyControl = null;
    } else if (job.apply_state === 'closed') {
        applyControl = <span className={closedClass}>Closed</span>;
    } else if (job.apply_state === 'applied') {
        applyControl = <span className={closedClass}>Applied</span>;
    } else if (job.apply_state === 'upload_resume') {
        applyControl = (
            <Link href={`/jobs/${job.slug}/apply`} className={buttonClass} onClick={(event) => event.stopPropagation()}>
                Upload Resume to Apply
            </Link>
        );
    } else if (job.apply_state === 'guest') {
        applyControl = (
            <Link
                href={`/login?intended=/jobs/${job.slug}`}
                className={buttonClass}
                onClick={(event) => event.stopPropagation()}
            >
                Apply Now
            </Link>
        );
    } else {
        applyControl = (
            <button type="button" className={buttonClass} onClick={apply} disabled={pending}>
                {pending ? 'Applying…' : 'Apply Now'}
            </button>
        );
    }

    return (
        <div className={`flex flex-wrap items-center gap-3 ${isDetail ? '' : 'justify-between'}`}>
            <SaveJobButton
                slug={job.slug}
                isSaved={job.is_saved === true}
                intended={`/jobs/${job.slug}`}
                variant={isDetail ? 'button' : 'link'}
            />
            {applyControl}
        </div>
    );
}
