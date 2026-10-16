import { useEffect, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import IconButton from '@/Components/atoms/IconButton';
import type { SharedPageProps } from '@/types/page';

type SaveJobButtonProps = {
    slug: string;
    isSaved: boolean;
    intended?: string;
    variant?: 'link' | 'button';
    layout?: 'text' | 'icon';
};

export default function SaveJobButton({
    slug,
    isSaved,
    intended,
    variant = 'link',
    layout = 'text',
}: SaveJobButtonProps) {
    const role = usePage<SharedPageProps>().props.auth.user?.role;
    const [saved, setSaved] = useState(isSaved);
    const [pending, setPending] = useState(false);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        setSaved(isSaved);
    }, [isSaved]);

    if (role === 'employer' || role === 'admin') {
        return null;
    }

    const saveLabel = 'Save job';
    const removeLabel = 'Remove saved job';
    const label = saved ? removeLabel : saveLabel;
    const linkClass =
        variant === 'button'
            ? 'inline-flex items-center justify-center rounded-md border border-line bg-white px-4 py-2.5 text-sm font-semibold text-ink hover:bg-surface disabled:opacity-60'
            : 'inline-flex min-h-11 items-center px-1 text-sm font-semibold text-ink hover:text-brand disabled:opacity-60';

    const toggleSaved = () => {
        if (pending) {
            return;
        }

        setPending(true);
        setError(null);
        const previous = saved;
        const optimistic = !saved;
        setSaved(optimistic);

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setError(null);
            },
            onError: () => {
                setSaved(previous);
                setError(saved ? 'Could not remove this saved job.' : 'Could not save this job.');
            },
            onFinish: () => setPending(false),
        };

        if (previous) {
            router.delete(`/account/saved-jobs/${slug}`, options);
        } else {
            router.post(`/account/saved-jobs/${slug}`, {}, options);
        }
    };

    if (layout === 'icon') {
        if (!role) {
            return (
                <IconButton
                    surface="public"
                    variant="ghost"
                    icon="bookmark"
                    label={saveLabel}
                    href={`/login?intended=${encodeURIComponent(intended || `/jobs/${slug}`)}`}
                />
            );
        }

        return (
            <span className="inline-flex flex-col items-center">
                <IconButton
                    surface="public"
                    variant={saved ? 'secondary' : 'ghost'}
                    icon={saved ? 'bookmark-filled' : 'bookmark'}
                    label={label}
                    disabled={pending}
                    aria-pressed={saved}
                    onClick={toggleSaved}
                />
                {error ? (
                    <span className="sr-only" role="alert">
                        {error}
                    </span>
                ) : null}
            </span>
        );
    }

    if (!role) {
        return (
            <Link href={`/login?intended=${encodeURIComponent(intended || `/jobs/${slug}`)}`} className={linkClass}>
                Save
            </Link>
        );
    }

    return (
        <span className="inline-flex flex-col">
            <button
                type="button"
                className={linkClass}
                onClick={toggleSaved}
                disabled={pending}
                aria-pressed={saved}
            >
                {pending ? 'Saving…' : saved ? 'Saved' : 'Save'}
            </button>
            {error ? (
                <span className="text-xs text-danger" role="alert">
                    {error}
                </span>
            ) : null}
        </span>
    );
}
