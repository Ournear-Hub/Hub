import { Link, usePage } from '@inertiajs/react';
import BrandMark from '@/Components/BrandMark';
import PageContainer from '@/Components/Public/PageContainer';
import { focusRing } from '@/design-system/classNames';
import type { SharedPageProps } from '@/types/page';

const footerLinks = [
    { label: 'Jobs', href: '/jobs' },
    { label: 'Hiring', href: '/hire' },
    { label: 'Talent Pool', href: '/talent-pool' },
    { label: 'Staffing', href: '/staffing' },
    { label: 'About', href: '/about' },
    { label: 'Contact', href: '/contact' },
    { label: 'Privacy Policy', href: '/privacy-policy' },
    { label: 'Terms & Conditions', href: '/terms-and-conditions' },
];

const linkClass = `inline-flex min-h-9 items-center py-1 text-sm text-white/85 hover:text-white ${focusRing}`;

export default function Footer() {
    const page = usePage<SharedPageProps>().props;

    return (
        <footer className="mt-auto w-full bg-brand text-white">
            <PageContainer className="flex flex-col gap-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:gap-6 sm:py-5">
                <Link href="/" className="shrink-0" aria-label={`${page.branding?.website_name || 'OurNear'} home`}>
                    <BrandMark inverted usePublicBranding logosOnly compact placement="footer" />
                </Link>
                <nav aria-label="Footer" className="min-w-0">
                    <ul className="flex flex-wrap gap-x-4 gap-y-1 sm:justify-end md:gap-x-5">
                        {footerLinks.map((item) => (
                            <li key={item.href}>
                                <Link href={item.href} className={linkClass}>
                                    {item.label}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </nav>
            </PageContainer>
            <div className="border-t border-white/10">
                <PageContainer className="py-2.5 text-xs text-white/70">
                    <p>{page.publicContent?.copyright ?? `© ${new Date().getFullYear()} OurNear. All rights reserved.`}</p>
                </PageContainer>
            </div>
        </footer>
    );
}
