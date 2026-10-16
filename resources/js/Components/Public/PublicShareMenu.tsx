import { router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import IconButton from '@/Components/atoms/IconButton';
import DropdownMenu, {
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/molecules/DropdownMenu';
import {
    buildEmailPayload,
    buildNativeText,
    buildWhatsappText,
    copyShareUrl,
    mailtoShareUrl,
    resolveShareUrl,
    whatsappShareUrl,
    type PublicShareContext,
} from '@/utils/publicShare';

type Props = {
    context: PublicShareContext;
    label?: string;
    /** When set, records job share events via existing backend endpoint. */
    jobSlug?: string | null;
    align?: 'start' | 'end';
};

export default function PublicShareMenu({ context, label = 'Share', jobSlug, align = 'end' }: Props) {
    const [feedback, setFeedback] = useState<string | null>(null);

    const shareUrl = useMemo(() => resolveShareUrl(context), [context]);

    const recordShare = (channel: 'whatsapp' | 'email' | 'copy' | 'native') => {
        if (!jobSlug) {
            return;
        }
        router.post(
            `/jobs/${jobSlug}/share`,
            { channel },
            { preserveScroll: true, preserveState: true },
        );
    };

    const flashFeedback = (message: string) => {
        setFeedback(message);
        window.setTimeout(() => setFeedback(null), 2500);
    };

    const onWhatsapp = () => {
        const text = buildWhatsappText(context, shareUrl);
        window.open(whatsappShareUrl(text), '_blank', 'noopener,noreferrer');
        recordShare('whatsapp');
    };

    const onEmail = () => {
        const { subject, body } = buildEmailPayload(context, shareUrl);
        window.location.href = mailtoShareUrl(subject, body);
        recordShare('email');
    };

    const onCopy = () => {
        void (async () => {
            const copied = await copyShareUrl(shareUrl);
            if (copied) {
                flashFeedback('Link copied');
            } else {
                window.prompt('Copy link', shareUrl);
                flashFeedback('Copy the link manually');
            }
            recordShare('copy');
        })();
    };

    const onNative = () => {
        void (async () => {
            if (typeof navigator.share !== 'function') {
                onCopy();
                return;
            }
            try {
                await navigator.share({
                    title: context.title,
                    text: buildNativeText(context, shareUrl),
                    url: shareUrl,
                });
                recordShare('native');
            } catch {
                // User dismissed native share.
            }
        })();
    };

    const nativeAvailable = typeof navigator !== 'undefined' && typeof navigator.share === 'function';

    return (
        <div className="relative inline-flex flex-col items-end" onClick={(event) => event.stopPropagation()}>
            <DropdownMenu>
                <DropdownMenuTrigger>
                    <IconButton surface="public" variant="ghost" icon="share" label={label} />
                </DropdownMenuTrigger>
                <DropdownMenuContent align={align}>
                    <DropdownMenuItem onSelect={onWhatsapp}>WhatsApp</DropdownMenuItem>
                    <DropdownMenuItem onSelect={onEmail}>Email</DropdownMenuItem>
                    {nativeAvailable ? <DropdownMenuItem onSelect={onNative}>Share on device</DropdownMenuItem> : null}
                    <DropdownMenuItem onSelect={onCopy}>Copy link</DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
            {feedback ? (
                <span className="sr-only" role="status" aria-live="polite">
                    {feedback}
                </span>
            ) : null}
        </div>
    );
}
