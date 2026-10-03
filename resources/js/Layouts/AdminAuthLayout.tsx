import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import BrandMark from '@/Components/BrandMark';

type AdminAuthLayoutProps = PropsWithChildren<{
    title?: string;
}>;

export default function AdminAuthLayout({ title, children }: AdminAuthLayoutProps) {
    return (
        <div className="flex min-h-screen flex-col bg-canvas">
            <header className="border-b border-line bg-brand px-4 py-5">
                <div className="mx-auto flex max-w-md flex-col items-center text-center">
                    <Link href="/admin/login" aria-label="OurNear admin">
                        <BrandMark inverted className="text-xl" />
                    </Link>
                    <p className="mt-2 text-xs tracking-wide text-white/80">
                        Right People. Right Training. Right Results.
                    </p>
                </div>
            </header>
            <main className="flex flex-1 items-center justify-center px-4 py-10">
                <div className="w-full max-w-md rounded-xl border border-line bg-white p-6 shadow-sm sm:p-8">
                    {title && <h1 className="mb-6 text-2xl font-semibold text-ink">{title}</h1>}
                    {children}
                </div>
            </main>
        </div>
    );
}
