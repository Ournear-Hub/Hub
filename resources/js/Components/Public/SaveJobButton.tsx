import { useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import type { SharedPageProps } from '@/types/page';

type SaveJobButtonProps = {
    slug: string;
    isSaved: boolean;
    intended?: string;
    variant?: 'link' | 'button';
};

export default function SaveJobButton({ slug, isSaved, intended, variant = 'link' }: SaveJobButtonProps) {
    const role = usePage<SharedPageProps>().props.auth.user?.role;
    const [pending, setPending] = useState(false);

    if (role === 'employer' || role === 'admin') {
        return null;
    }

    const label = isSaved ? 'Saved' : 'Save';
    const linkClass =
        variant === 'button'
            ? 'inline-flex items-center justify-center rounded-md border border-line bg-white px-4 py-2.5 text-sm font-semibold text-ink hover:bg-surface disabled:opacity-60'
            : 'inline-flex min-h-11 items-center px-1 text-sm font-semibold text-ink hover:text-brand disabled:opacity-60';

    if (!role) {
        return (
            <Link href={`/login?intended=${encodeURIComponent(intended || `/jobs/${slug}`)}`} className={linkClass}>
                Save
            </Link>
        );
    }

    const toggle = () => {
        if (pending) {
            return;
        }

        setPending(true);
        const options = {
            preserveScroll: true,
            onFinish: () => setPending(false),
        };

        if (isSaved) {
            router.delete(`/account/saved-jobs/${slug}`, options);
            return;
        }

        router.post(`/account/saved-jobs/${slug}`, {}, options);
    };

    return (
        <button type="button" className={linkClass} onClick={toggle} disabled={pending} aria-pressed={isSaved}>
            {pending ? 'Saving…' : label}
        </button>
    );
}
