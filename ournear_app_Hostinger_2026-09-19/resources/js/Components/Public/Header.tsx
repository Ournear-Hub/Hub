import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import BrandMark from '@/Components/BrandMark';
import Button from '@/Components/Public/Button';
import PageContainer from '@/Components/Public/PageContainer';
import { isNavActive, primaryNav } from '@/content/navigation';
import type { SharedPageProps } from '@/types/page';

export default function Header() {
    const { url, props } = usePage<SharedPageProps>();
    const user = props.auth.user;
    const isMember = user?.role === 'candidate' || user?.role === 'employer';
    const [open, setOpen] = useState(false);
    const [servicesOpen, setServicesOpen] = useState(false);

    return (
        <header className="sticky top-0 z-40 border-b border-line bg-white/95 backdrop-blur">
            <PageContainer className="flex h-16 items-center justify-between gap-4">
                <Link href="/" className="shrink-0" aria-label={`${props.branding?.website_name || 'OurNear Talent'} home`}>
                    <BrandMark className="text-lg" usePublicBranding />
                </Link>

                <nav className="hidden items-center gap-5 lg:flex" aria-label="Primary">
                    {primaryNav.map((item) => {
                        const active = isNavActive(url, item.href);

                        if (item.children) {
                            return (
                                <div key={item.href} className="relative">
                                    <button
                                        type="button"
                                        className={`text-sm font-medium ${active ? 'text-brand' : 'text-muted hover:text-ink'}`}
                                        aria-expanded={servicesOpen}
                                        aria-haspopup="true"
                                        onClick={() => setServicesOpen((value) => !value)}
                                        onBlur={() => window.setTimeout(() => setServicesOpen(false), 150)}
                                    >
                                        {item.label}
                                    </button>
                                    {servicesOpen && (
                                        <div className="absolute left-0 mt-2 w-52 rounded-md border border-line bg-white py-2 shadow-sm">
                                            <Link
                                                href={item.href}
                                                className="block px-3 py-2 text-sm text-ink hover:bg-surface"
                                            >
                                                All services
                                            </Link>
                                            {item.children.map((child) => (
                                                <Link
                                                    key={child.href}
                                                    href={child.href}
                                                    className="block px-3 py-2 text-sm text-muted hover:bg-surface hover:text-ink"
                                                >
                                                    {child.label}
                                                </Link>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            );
                        }

                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={`text-sm font-medium ${active ? 'text-brand' : 'text-muted hover:text-ink'}`}
                            >
                                {item.label}
                            </Link>
                        );
                    })}
                </nav>

                <div className="flex items-center gap-3">
                    {!user ? (
                        <>
                            <Link href="/login" className="hidden text-sm font-medium text-muted hover:text-ink sm:inline">
                                Log in
                            </Link>
                            <Link href="/register" className="hidden text-sm font-medium text-brand hover:text-ink sm:inline">
                                Register
                            </Link>
                        </>
                    ) : isMember ? (
                        <>
                            <Link href="/account" className="hidden text-sm font-medium text-muted hover:text-ink sm:inline">
                                Account
                            </Link>
                            <button
                                type="button"
                                className="hidden text-sm font-medium text-muted hover:text-ink sm:inline"
                                onClick={() => router.post('/logout')}
                            >
                                Log out
                            </button>
                        </>
                    ) : null}
                    <div className="hidden sm:block">
                        <Button href="/jobs">Find Jobs</Button>
                    </div>
                    <button
                        type="button"
                        className="inline-flex h-10 w-10 items-center justify-center rounded-md border border-line text-ink lg:hidden"
                        aria-expanded={open}
                        aria-controls="public-mobile-nav"
                        onClick={() => setOpen((value) => !value)}
                    >
                        <span className="sr-only">Toggle navigation</span>
                        <span className="flex flex-col gap-1.5">
                            <span className="block h-0.5 w-4 bg-current" />
                            <span className="block h-0.5 w-4 bg-current" />
                            <span className="block h-0.5 w-4 bg-current" />
                        </span>
                    </button>
                </div>
            </PageContainer>

            {open && (
                <div id="public-mobile-nav" className="border-t border-line bg-white lg:hidden">
                    <PageContainer className="flex flex-col gap-1 py-3">
                        {primaryNav.map((item) => (
                            <div key={item.href}>
                                <Link
                                    href={item.href}
                                    className="block rounded-md px-2 py-2 text-sm font-medium text-ink hover:bg-surface"
                                    onClick={() => setOpen(false)}
                                >
                                    {item.label}
                                </Link>
                                {item.children?.map((child) => (
                                    <Link
                                        key={child.href}
                                        href={child.href}
                                        className="block rounded-md px-4 py-2 text-sm text-muted hover:bg-surface"
                                        onClick={() => setOpen(false)}
                                    >
                                        {child.label}
                                    </Link>
                                ))}
                            </div>
                        ))}
                        <Link
                            href="/jobs"
                            className="mt-2 rounded-md bg-accent px-3 py-2 text-center text-sm font-semibold text-white"
                            onClick={() => setOpen(false)}
                        >
                            Find Jobs
                        </Link>
                        {!user ? (
                            <>
                                <Link
                                    href="/login"
                                    className="rounded-md px-2 py-2 text-sm font-medium text-ink hover:bg-surface"
                                    onClick={() => setOpen(false)}
                                >
                                    Log in
                                </Link>
                                <Link
                                    href="/register"
                                    className="rounded-md px-2 py-2 text-sm font-medium text-ink hover:bg-surface"
                                    onClick={() => setOpen(false)}
                                >
                                    Register
                                </Link>
                            </>
                        ) : isMember ? (
                            <>
                                <Link
                                    href="/account"
                                    className="rounded-md px-2 py-2 text-sm font-medium text-ink hover:bg-surface"
                                    onClick={() => setOpen(false)}
                                >
                                    Account
                                </Link>
                                <button
                                    type="button"
                                    className="rounded-md px-2 py-2 text-left text-sm font-medium text-ink hover:bg-surface"
                                    onClick={() => {
                                        setOpen(false);
                                        router.post('/logout');
                                    }}
                                >
                                    Log out
                                </button>
                            </>
                        ) : null}
                    </PageContainer>
                </div>
            )}
        </header>
    );
}
