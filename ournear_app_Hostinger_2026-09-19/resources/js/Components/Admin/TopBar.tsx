import { Link, usePage } from '@inertiajs/react';
import type { SharedPageProps } from '@/types/page';

type TopBarProps = {
    onMenuClick: () => void;
};

export default function TopBar({ onMenuClick }: TopBarProps) {
    const user = usePage<SharedPageProps>().props.auth.user;

    return (
        <header className="flex h-16 shrink-0 items-center justify-between gap-3 border-b border-line bg-white px-4 sm:px-6">
            <div className="flex min-w-0 items-center gap-3">
                <button
                    type="button"
                    className="inline-flex h-10 w-10 items-center justify-center rounded-md border border-line text-ink lg:hidden"
                    onClick={onMenuClick}
                >
                    <span className="sr-only">Open sidebar</span>
                    <span className="flex flex-col gap-1.5">
                        <span className="block h-0.5 w-4 bg-current" />
                        <span className="block h-0.5 w-4 bg-current" />
                        <span className="block h-0.5 w-4 bg-current" />
                    </span>
                </button>
                <p className="truncate text-sm font-medium text-ink">Admin panel</p>
            </div>
            <div className="flex min-w-0 items-center gap-3">
                {user && (
                    <div className="hidden min-w-0 text-right sm:block">
                        <p className="truncate text-sm font-medium text-ink">{user.name}</p>
                        <p className="truncate text-xs text-muted">{user.email}</p>
                    </div>
                )}
                <Link
                    href="/admin/logout"
                    method="post"
                    as="button"
                    className="rounded-md border border-line px-3 py-1.5 text-sm font-medium text-ink hover:bg-surface"
                >
                    Logout
                </Link>
            </div>
        </header>
    );
}
