import { Link, usePage } from '@inertiajs/react';
import BrandMark from '@/Components/BrandMark';
import PageContainer from '@/Components/Public/PageContainer';
import SocialIcons from '@/Components/Public/SocialIcons';
import type { SharedPageProps } from '@/types/page';

const exploreLinks = [
    { label: 'Jobs', href: '/jobs' },
    { label: 'Companies', href: '/companies' },
    { label: 'Hire', href: '/hire' },
    { label: 'Talent Pool', href: '/talent-pool' },
    { label: 'Staffing', href: '/staffing' },
    { label: 'About', href: '/about' },
];

const supportLinks = [
    { label: 'Contact', href: '/contact' },
    { label: 'Privacy', href: '/privacy-policy' },
    { label: 'Terms', href: '/terms-and-conditions' },
];

export default function Footer() {
    const page = usePage<SharedPageProps>().props;
    const contact = page.publicContent?.contact;
    const socials = page.socialLinks ?? [];

    return (
        <footer className="mt-auto bg-brand text-white">
            <PageContainer className="grid gap-8 py-10 sm:grid-cols-2 sm:py-12 lg:grid-cols-4">
                <div className="sm:col-span-2 lg:col-span-1">
                    <BrandMark inverted className="text-lg" usePublicBranding />
                    <p className="mt-3 max-w-xs text-sm text-white/80">Right People. Right Training. Right Results.</p>
                </div>
                <div>
                    <h2 className="text-sm font-semibold">Explore</h2>
                    <ul className="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-sm text-white/80 sm:grid-cols-1">
                        {exploreLinks.map((item) => (
                            <li key={item.href}>
                                <Link href={item.href} className="hover:text-white">
                                    {item.label}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </div>
                <div>
                    <h2 className="text-sm font-semibold">Support</h2>
                    <ul className="mt-3 space-y-2 text-sm text-white/80">
                        {supportLinks.map((item) => (
                            <li key={item.href}>
                                <Link href={item.href} className="hover:text-white">
                                    {item.label}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </div>
                <div>
                    <h2 className="text-sm font-semibold">Contact</h2>
                    {contact?.email || contact?.phone ? (
                        <p className="mt-3 text-sm text-white/80">
                            {contact?.email ? (
                                <>
                                    {contact.email}
                                    <br />
                                </>
                            ) : null}
                            {contact?.phone}
                        </p>
                    ) : (
                        <p className="mt-3 text-sm text-white/70">
                            <Link href="/contact" className="hover:text-white">
                                Get in touch
                            </Link>
                        </p>
                    )}
                    {contact?.whatsapp_url ? (
                        <p className="mt-2 text-sm">
                            <a href={contact.whatsapp_url} className="text-white/80 hover:text-white" target="_blank" rel="noreferrer">
                                WhatsApp {contact.whatsapp}
                            </a>
                        </p>
                    ) : null}
                    {socials.length > 0 ? (
                        <div className="mt-4">
                            <SocialIcons links={socials} />
                        </div>
                    ) : null}
                </div>
            </PageContainer>
            <div className="border-t border-white/10">
                <PageContainer className="flex flex-col gap-2 py-4 text-xs text-white/70 sm:flex-row sm:items-center sm:justify-between">
                    <p>{page.publicContent?.copyright ?? `© ${new Date().getFullYear()} OurNear. All rights reserved.`}</p>
                    <p className="flex flex-wrap gap-x-4 gap-y-1">
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
