import { useState, type MouseEvent } from 'react';
import IconButton from '@/Components/UI/IconButton';
import { CloseIcon } from '@/Components/UI/icons';

type ProfileShareMenuProps = {
    profileUrl: string;
    profileName: string;
    triggerClassName?: string;
};

export default function ProfileShareMenu({ profileUrl, profileName, triggerClassName }: ProfileShareMenuProps) {
    const [open, setOpen] = useState(false);
    const absoluteUrl = typeof window !== 'undefined' ? `${window.location.origin}${profileUrl}` : profileUrl;

    const copyLink = async (event: MouseEvent) => {
        event.preventDefault();
        try {
            await navigator.clipboard.writeText(absoluteUrl);
        } catch {
            window.prompt('Copy profile link', absoluteUrl);
        }
    };

    const shareWhatsApp = (event: MouseEvent) => {
        event.preventDefault();
        const text = encodeURIComponent(`${profileName} — ${absoluteUrl}`);
        window.open(`https://wa.me/?text=${text}`, '_blank', 'noopener,noreferrer');
    };

    const shareEmail = (event: MouseEvent) => {
        event.preventDefault();
        const subject = encodeURIComponent(`${profileName} on OurNear Hire`);
        const body = encodeURIComponent(`View this profile: ${absoluteUrl}`);
        window.location.href = `mailto:?subject=${subject}&body=${body}`;
    };

    const nativeShare = async (event: MouseEvent) => {
        event.preventDefault();
        if (typeof navigator.share === 'function') {
            try {
                await navigator.share({ title: profileName, url: absoluteUrl, text: profileName });
            } catch {
                // User cancelled.
            }
        }
    };

    const actionClass =
        'inline-flex min-h-11 w-full items-center justify-center rounded-md border border-line px-4 py-2 text-sm font-semibold text-ink hover:bg-surface';

    return (
        <>
            <button
                type="button"
                className={
                    triggerClassName ??
                    'inline-flex min-h-11 items-center rounded-md border border-line px-4 py-2 text-sm font-semibold text-brand hover:bg-surface'
                }
                onClick={() => setOpen(true)}
            >
                Share profile
            </button>
            {open ? (
                <div className="fixed inset-0 z-50 flex items-end justify-center sm:items-center" role="presentation">
                    <button type="button" className="absolute inset-0 bg-ink/40" aria-label="Close share menu" onClick={() => setOpen(false)} />
                    <div
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="profile-share-title"
                        className="relative z-10 w-full max-w-md rounded-t-2xl border border-line bg-white p-5 shadow-lg sm:rounded-2xl"
                    >
                        <div className="flex items-center justify-between gap-3">
                            <h2 id="profile-share-title" className="text-lg font-semibold text-ink">
                                Share profile
                            </h2>
                            <IconButton label="Close" onClick={() => setOpen(false)}>
                                <CloseIcon />
                            </IconButton>
                        </div>
                        <p className="mt-2 text-sm text-muted">
                            Share your public profile link. Private documents and account settings are never included.
                        </p>
                        <p className="mt-2 break-all text-sm font-medium text-ink">{absoluteUrl}</p>
                        <div className="mt-4 flex flex-col gap-2">
                            <button type="button" className={actionClass} onClick={copyLink}>
                                Copy link
                            </button>
                            <button type="button" className={actionClass} onClick={shareWhatsApp}>
                                WhatsApp
                            </button>
                            <button type="button" className={actionClass} onClick={shareEmail}>
                                Email
                            </button>
                            {typeof navigator !== 'undefined' && typeof navigator.share === 'function' ? (
                                <button type="button" className={actionClass} onClick={nativeShare}>
                                    Share via device
                                </button>
                            ) : null}
                        </div>
                    </div>
                </div>
            ) : null}
        </>
    );
}
