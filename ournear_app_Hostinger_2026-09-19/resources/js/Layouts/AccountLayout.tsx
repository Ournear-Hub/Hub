import { useState, type ReactNode } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AccountNav from '@/Components/Account/AccountNav';
import PublicLayout from '@/Layouts/PublicLayout';
import PageContainer from '@/Components/Public/PageContainer';
import type { SharedPageProps } from '@/types/page';

type AccountLayoutProps = {
    title: string;
    description?: string;
    action?: ReactNode;
    children: ReactNode;
};

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

export default function AccountLayout({ title, description, action, children }: AccountLayoutProps) {
    const user = usePage<SharedPageProps>().props.auth.user;
    const [open, setOpen] = useState(false);

    return (
        <PublicLayout title={title}>
            <Head title={title} />
            <section className="border-b border-line bg-white">
                <PageContainer className="flex flex-wrap items-center justify-between gap-3 py-5">
                    <div>
                        <h1 className="text-2xl font-semibold text-ink">{title}</h1>
                        {description ? <p className="mt-1 text-sm text-muted">{description}</p> : null}
                    </div>
                    <div className="flex items-center gap-3">
                        {action}
                        <Link href="/account/profile" className="text-sm font-medium text-ink hover:text-brand">
                            {user?.name ?? 'Account'}
                        </Link>
                        <button
                            type="button"
                            className="rounded-md border border-line px-3 py-1.5 text-sm font-medium text-ink lg:hidden"
                            onClick={() => setOpen((value) => !value)}
                        >
                            Menu
                        </button>
                    </div>
                </PageContainer>
            </section>
            <section className="py-8">
                <PageContainer className="grid gap-8 lg:grid-cols-[16rem_minmax(0,1fr)]">
                    <aside className={`${open ? 'block' : 'hidden'} lg:block`}>
                        <div className="mb-4 rounded-xl border border-line bg-white p-4">
                            {user?.photo_url ? (
                                <img src={user.photo_url} alt="" className="h-16 w-16 rounded-full object-cover" />
                            ) : (
                                <div className="flex h-16 w-16 items-center justify-center rounded-full bg-surface text-base font-semibold text-brand">
                                    {initials(user?.name || 'U')}
                                </div>
                            )}
                            <p className="mt-3 font-semibold text-ink">{user?.name}</p>
                            {user?.username ? <p className="text-sm text-muted">@{user.username}</p> : null}
                        </div>
                        <AccountNav />
                    </aside>
                    <div>{children}</div>
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
