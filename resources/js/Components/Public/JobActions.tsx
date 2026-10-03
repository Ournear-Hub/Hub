import { Link, router } from '@inertiajs/react';
import type { MouseEvent } from 'react';
import SaveJobButton from '@/Components/Public/SaveJobButton';
import type { PublicJobCard, PublicJobDetail } from '@/types/jobs';

type JobActionsProps = {
    job: PublicJobCard | PublicJobDetail;
    variant?: 'card' | 'detail' | 'list';
};

export default function JobActions({ job, variant = 'card' }: JobActionsProps) {
    const isDetail = variant === 'detail';
    const isList = variant === 'list';
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
    const intendedApply = encodeURIComponent(applyHref);

    let applyControl = null;
    if (job.apply_state === 'hidden') {
        applyControl = null;
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
            <Link
                href={`/login?intended=${intendedApply}`}
                className={applyClass}
                onClick={(event) => event.stopPropagation()}
            >
                Apply
            </Link>
        );
    } else {
        applyControl = (
            <Link href={applyHref} className={applyClass} onClick={(event) => event.stopPropagation()}>
                Apply
            </Link>
        );
    }

    const sharePath = job.referral_code
        ? `/jobs/${job.slug}?ref=${encodeURIComponent(job.referral_code)}`
        : `/jobs/${job.slug}`;
    const shareUrl = typeof window !== 'undefined' ? window.location.origin + sharePath : sharePath;

    const recordShare = (channel: 'copy' | 'native') => {
        router.post(`/jobs/${job.slug}/share`, { channel }, { preserveScroll: true, preserveState: true });
    };

    const copyLink = async (event: MouseEvent) => {
        event.stopPropagation();
        try {
            await navigator.clipboard.writeText(shareUrl);
        } catch {
            window.prompt('Copy job link', shareUrl);
        }
        recordShare('copy');
    };

    const nativeShare = async (event: MouseEvent) => {
        event.stopPropagation();
        if (typeof navigator.share === 'function') {
            try {
                await navigator.share({ title: job.title, url: shareUrl, text: job.title });
                recordShare('native');
            } catch {
                // User cancelled native share.
            }
        }
    };

    if (isList) {
        return (
            <div className="flex flex-wrap items-center gap-2 sm:justify-between">
                {applyControl}
                <SaveJobButton
                    slug={job.slug}
                    isSaved={job.is_saved === true}
                    intended={`/jobs/${job.slug}`}
                    variant="link"
                />
            </div>
        );
    }

    return (
        <div className={isDetail ? 'space-y-3' : 'flex flex-wrap items-center justify-between gap-2'}>
            <div className={`flex flex-wrap items-center gap-2 ${isDetail ? 'w-full sm:w-auto' : ''}`}>
                {applyControl}
                <SaveJobButton
                    slug={job.slug}
                    isSaved={job.is_saved === true}
                    intended={`/jobs/${job.slug}`}
                    variant={isDetail ? 'button' : 'link'}
                />
            </div>
            {!isList ? (
                <div
                    className={`flex flex-wrap items-center gap-3 text-xs font-medium text-muted ${isDetail ? 'w-full border-t border-line pt-2 sm:text-sm' : ''}`}
                >
                    <button type="button" className="text-brand hover:underline" onClick={copyLink}>
                        Copy link
                    </button>
                    {typeof navigator !== 'undefined' && typeof navigator.share === 'function' ? (
                        <button type="button" className="text-brand hover:underline" onClick={nativeShare}>
                            Share
                        </button>
                    ) : null}
                </div>
            ) : null}
        </div>
    );
}
