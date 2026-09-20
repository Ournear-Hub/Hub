import { Link, usePage } from '@inertiajs/react';
import BrandMark from '@/Components/BrandMark';
import PageContainer from '@/Components/Public/PageContainer';
import type { SharedPageProps } from '@/types/page';

const quickLinks = [
    { label: 'Home', href: '/' },
    { label: 'Jobs', href: '/jobs' },
    { label: 'About Us', href: '/about' },
    { label: 'Contact Us', href: '/contact' },
];

const serviceLinks = [
    { label: 'Manpower Supply', href: '/services#manpower-supply' },
    { label: 'Training & Development', href: '/services#training-development' },
    { label: 'Project Support & Management', href: '/services#project-support-management' },
];

const employerLinks = [
    { label: 'For Employers', href: '/for-employers' },
    { label: 'Contact', href: '/contact' },
];

export default function Footer() {
    const page = usePage<SharedPageProps>().props;
    const contact = page.publicContent?.contact;
    const socials = page.socialLinks ?? [];
    return (
        <footer className="mt-auto bg-brand text-white">
            <PageContainer className="grid gap-8 py-12 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <BrandMark inverted className="text-lg" usePublicBranding />
                    <p className="mt-3 text-sm text-white/80">Right People. Right Training. Right Results.</p>
                </div>
                <div>
                    <h2 className="text-sm font-semibold">Quick links</h2>
                    <ul className="mt-3 space-y-2 text-sm text-white/80">
                        {quickLinks.map((item) => (
                            <li key={item.href}>
                                <Link href={item.href} className="hover:text-white">
                                    {item.label}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </div>
                <div>
                    <h2 className="text-sm font-semibold">Services</h2>
                    <ul className="mt-3 space-y-2 text-sm text-white/80">
                        {serviceLinks.map((item) => (
                            <li key={item.href}>
                                <Link href={item.href} className="hover:text-white">
                                    {item.label}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </div>
                <div>
                    <h2 className="text-sm font-semibold">Employers</h2>
                    <ul className="mt-3 space-y-2 text-sm text-white/80">
                        {employerLinks.map((item) => (
                            <li key={item.href}>
                                <Link href={item.href} className="hover:text-white">
                                    {item.label}
                                </Link>
                            </li>
                        ))}
                    </ul>
                    <p className="mt-4 text-sm text-white/70">
                        {contact?.email}
                        <br />
                        {contact?.phone}
                    </p>
                    {socials.length > 0 ? (
                        <div className="mt-3 flex flex-wrap gap-3 text-sm text-white/80">
                            {socials.map((item) => (
                                <a key={item.platform} href={item.href} className="hover:text-white" target="_blank" rel="noreferrer">
                                    {item.label}
                                </a>
                            ))}
                        </div>
                    ) : null}
                </div>
            </PageContainer>
            <div className="border-t border-white/10">
                <PageContainer className="flex flex-col gap-2 py-4 text-xs text-white/70 sm:flex-row sm:justify-between">
                    <p>{page.publicContent?.copyright ?? `© ${new Date().getFullYear()} OurNear Talent. All rights reserved.`}</p>
                    <p className="flex gap-4">
                        <Link href="/privacy-policy" className="hover:text-white">
                            Privacy Policy
                        </Link>
                        <Link href="/terms-and-conditions" className="hover:text-white">
                            Terms & Conditions
                        </Link>
                    </p>
                </PageContainer>
            </div>
        </footer>
    );
}
