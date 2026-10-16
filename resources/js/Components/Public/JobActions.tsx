import { Link } from '@inertiajs/react';
import SaveJobButton from '@/Components/Public/SaveJobButton';
import PublicShareMenu from '@/Components/Public/PublicShareMenu';
import type { PublicJobCard, PublicJobDetail } from '@/types/jobs';

type JobActionsProps = {
    job: PublicJobCard | PublicJobDetail;
    variant?: 'card' | 'detail' | 'list';
    layout?: 'default' | 'icon';
};

export default function JobActions({ job, variant = 'card', layout = 'default' }: JobActionsProps) {
    const isDetail = variant === 'detail';
    const isList = variant === 'list';
    const iconOnly = layout === 'icon';

    const applyClass = isDetail
        ? 'inline-flex min-h-11 min-w-[8.5rem] items-center justify-center rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white disabled:bg-disabled'
        : isList
          ? 'inline-flex min-h-11 items-center justify-center rounded-md bg-accent px-3 py-2 text-sm font-semibold text-white disabled:bg-disabled'
          : 'rounded-md bg-accent px-3 py-1.5 text-sm font-semibold text-white disabled:bg-disabled';
    const closedClass = isDetail
        ? 'inline-flex min-h-11 items-center justify-center rounded-md border border-line px-4 py-2.5 text-sm font-semibold text-muted'
        : isList
          ? 'inline-flex min-h-11 items-center justify-center rounded-md border border-line px-3 py-2 text-sm font-semibold text-muted'
          : 'rounded-md border border-line px-3 py-1.5 text-sm font-semibold text-muted';

    const applyHref = `/jobs/${job.slug}/apply`;

    let applyControl = null;
    if (job.apply_state === 'hidden') {
        applyControl = null;
    } else if (job.apply_state === 'own_company') {
        applyControl = (
            <span className={`${closedClass} max-w-xs text-left leading-snug`}>
                You cannot apply for a job posted by your own company.
            </span>
        );
    } else if (job.apply_state === 'closed') {
        applyControl = <span className={closedClass}>Closed</span>;
    } else if (job.apply_state === 'applied') {
        applyControl = <span className={closedClass}>Applied</span>;
    } else if (job.apply_state === 'upload_resume') {
        applyControl = (
            <Link href={applyHref} className={applyClass} onClick={(event) => event.stopPropagation()}>
                Continue to apply
            </Link>
        );
    } else if (job.apply_state === 'guest') {
        applyControl = (
            <Link href={applyHref} className={applyClass} onClick={(event) => event.stopPropagation()}>
                Apply Now
            </Link>
        );
    } else {
        applyControl = (
            <Link href={applyHref} className={applyClass} onClick={(event) => event.stopPropagation()}>
                Apply Now
            </Link>
        );
    }

    const canSave = job.apply_state !== 'own_company';

    const shareUrl =
        job.share_url ??
        (typeof window !== 'undefined' ? `${window.location.origin}/jobs/${job.slug}` : `/jobs/${job.slug}`);

    const shareMenu = (
        <PublicShareMenu
            label="Share job"
            jobSlug={job.slug}
            context={{
                type: 'job',
                title: job.title,
                url: shareUrl,
                company: job.company_name,
                location: job.location,
                referralCode: job.referral_code ?? null,
            }}
        />
    );

    if (iconOnly) {
        return (
            <div className="flex items-center gap-0.5" onClick={(event) => event.stopPropagation()}>
                {canSave ? (
                    <SaveJobButton slug={job.slug} isSaved={job.is_saved === true} intended={`/jobs/${job.slug}`} layout="icon" />
                ) : null}
                {shareMenu}
            </div>
        );
    }

    if (isList) {
        return (
            <div className="flex flex-wrap items-center gap-2 sm:justify-between">
                {applyControl}
                {canSave ? (
                    <SaveJobButton
                        slug={job.slug}
                        isSaved={job.is_saved === true}
                        intended={`/jobs/${job.slug}`}
                        variant="link"
                    />
                ) : null}
            </div>
        );
    }

    if (isDetail) {
        return (
            <div className="flex flex-wrap items-center gap-2">
                {applyControl}
                {canSave ? (
                    <SaveJobButton
                        slug={job.slug}
                        isSaved={job.is_saved === true}
                        intended={`/jobs/${job.slug}`}
                        layout="icon"
                    />
                ) : null}
                {shareMenu}
            </div>
        );
    }

    return (
        <div className="flex flex-wrap items-center justify-between gap-2">
            <div className="flex flex-wrap items-center gap-2">
                {applyControl}
                {canSave ? (
                    <SaveJobButton
                        slug={job.slug}
                        isSaved={job.is_saved === true}
                        intended={`/jobs/${job.slug}`}
                        variant="link"
                        layout="text"
                    />
                ) : null}
            </div>
            <div className="flex items-center gap-1" onClick={(event) => event.stopPropagation()}>
                {canSave ? (
                    <SaveJobButton slug={job.slug} isSaved={job.is_saved === true} intended={`/jobs/${job.slug}`} layout="icon" />
                ) : null}
                {shareMenu}
            </div>
        </div>
    );
}
