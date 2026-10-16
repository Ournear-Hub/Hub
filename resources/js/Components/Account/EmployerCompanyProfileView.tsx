import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import CompanyContactVerificationSection from '@/Components/Account/CompanyContactVerificationSection';
import type { AccountCompanySocials, AccountProfile } from '@/types/account';

type Props = {
    profile: AccountProfile;
};

function EmptyValue() {
    return <span className="text-muted">Not added yet</span>;
}

function DetailRow({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="grid gap-1 border-b border-line py-3 last:border-b-0 sm:grid-cols-[minmax(0,140px)_1fr] sm:gap-4">
            <dt className="text-sm text-muted">{label}</dt>
            <dd className="text-sm font-medium text-ink">{children}</dd>
        </div>
    );
}

function Section({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="rounded-xl border border-line bg-white p-4 sm:p-5">
            <h2 className="text-base font-semibold text-ink">{title}</h2>
            <dl className="mt-3">{children}</dl>
        </section>
    );
}

function socialEntries(socials?: AccountCompanySocials): Array<{ label: string; href: string }> {
    if (!socials) {
        return [];
    }

    const labels: Record<string, string> = {
        linkedin: 'LinkedIn',
        facebook: 'Facebook',
        twitter: 'X / Twitter',
        instagram: 'Instagram',
        youtube: 'YouTube',
    };

    return Object.entries(socials)
        .filter(([, value]) => typeof value === 'string' && value.trim() !== '')
        .map(([key, value]) => ({
            label: labels[key] ?? key,
            href: value as string,
        }));
}

export default function EmployerCompanyProfileView({ profile }: Props) {
    const locationNames = (profile.locations ?? []).map((location) => location.name);
    const benefits = profile.benefits ?? [];
    const socialLinks = socialEntries(profile.socials);
    const jobsHref = (profile.hiring_links ?? []).find((link) => link.label === 'Jobs')?.href ?? '/account/jobs';

    return (
        <div className="min-w-0 space-y-4">
            <Section title="Overview">
                <DetailRow label="About">
                    <span className="whitespace-pre-wrap">{profile.about_company || <EmptyValue />}</span>
                </DetailRow>
                <DetailRow label="Industry">{profile.industry || <EmptyValue />}</DetailRow>
                <DetailRow label="Company size">{profile.company_size || <EmptyValue />}</DetailRow>
            </Section>

            <CompanyContactVerificationSection profile={profile} />

            <Section title="Contact person">
                <DetailRow label="Name">{profile.contact_person || <EmptyValue />}</DetailRow>
            </Section>

            <Section title="Locations & benefits">
                <DetailRow label="Locations">
                    {locationNames.length > 0 ? locationNames.join(', ') : <EmptyValue />}
                </DetailRow>
                <DetailRow label="Benefits">
                    {benefits.length > 0 ? (
                        <ul className="list-inside list-disc space-y-0.5 font-normal">
                            {benefits.map((benefit) => (
                                <li key={benefit}>{benefit}</li>
                            ))}
                        </ul>
                    ) : (
                        <EmptyValue />
                    )}
                </DetailRow>
            </Section>

            <Section title="Website & social">
                <DetailRow label="Website">
                    {profile.company_website ? (
                        <a href={profile.company_website} className="text-brand hover:underline" target="_blank" rel="noreferrer">
                            {profile.company_website}
                        </a>
                    ) : (
                        <EmptyValue />
                    )}
                </DetailRow>
                {socialLinks.length > 0 ? (
                    socialLinks.map((link) => (
                        <DetailRow key={link.label} label={link.label}>
                            <a href={link.href} className="break-all text-brand hover:underline" target="_blank" rel="noreferrer">
                                {link.href}
                            </a>
                        </DetailRow>
                    ))
                ) : (
                    <DetailRow label="Social links">
                        <EmptyValue />
                    </DetailRow>
                )}
            </Section>

            <Section title="Additional details">
                <DetailRow label="Details">
                    <span className="whitespace-pre-wrap">{profile.company_details || <EmptyValue />}</span>
                </DetailRow>
                <DetailRow label="Jobs">
                    <Link href={jobsHref} className="font-semibold text-brand hover:underline">
                        Manage jobs
                    </Link>
                </DetailRow>
                <DetailRow label="Documents">
                    <Link href="/account/company-documents" className="font-semibold text-brand hover:underline">
                        Company documents
                    </Link>
                </DetailRow>
            </Section>
        </div>
    );
}
