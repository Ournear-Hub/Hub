import { Link } from '@inertiajs/react';
import PageContainer from '@/Components/Public/PageContainer';
import OurNearIcon from '@/icons/OurNearIcon';
import { focusRing } from '@/design-system/classNames';

export type AudienceFeatureCard = {
    title: string;
    body: string;
    icon: string;
    href: string | null;
};

type Props = {
    eyebrow: string;
    title: string;
    items: AudienceFeatureCard[];
    variant?: 'employer' | 'candidate';
};

const employerShell =
    'group flex h-full min-h-[7.5rem] min-w-0 flex-col items-start justify-center rounded-xl border border-line bg-white p-5 shadow-sm transition hover:border-brand/40 hover:shadow-md focus-visible:outline-none';

const candidateShell =
    'group flex h-full min-w-0 flex-col rounded-xl border border-line bg-white p-4 transition hover:border-brand/35 hover:shadow-sm';

export default function HomeAudienceFeatureCards({ eyebrow, title, items, variant = 'employer' }: Props) {
    if (items.length === 0) {
        return null;
    }

    const isEmployer = variant === 'employer';
    const accent = isEmployer ? 'bg-brand/10 text-brand ring-1 ring-brand/10' : 'bg-accent/10 text-accent';

    return (
        <section className="bg-canvas py-8 md:py-10">
            <PageContainer className="min-w-0">
                <p className="text-xs font-semibold uppercase tracking-wide text-brand">{eyebrow}</p>
                <h2 className="mt-1 text-lg font-semibold text-ink sm:text-xl">{title}</h2>
                <div className="mt-5 grid min-w-0 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    {items.map((item) => {
                        if (isEmployer) {
                            const inner = (
                                <>
                                    <span className={`inline-flex h-10 w-10 items-center justify-center rounded-lg ${accent}`}>
                                        <OurNearIcon name={item.icon} size="lg" />
                                    </span>
                                    <h3 className="mt-3 text-base font-bold leading-tight text-ink group-hover:text-brand">{item.title}</h3>
                                </>
                            );

                            if (item.href) {
                                return (
                                    <Link
                                        key={item.title}
                                        href={item.href}
                                        className={`${employerShell} ${focusRing}`}
                                        aria-label={item.title}
                                    >
                                        {inner}
                                    </Link>
                                );
                            }

                            return (
                                <article
                                    key={item.title}
                                    className={`${employerShell} opacity-95`}
                                    aria-disabled="true"
                                    aria-label={`${item.title} — coming soon`}
                                >
                                    {inner}
                                </article>
                            );
                        }

                        const inner = (
                            <>
                                <span className={`inline-flex h-9 w-9 items-center justify-center rounded-lg ${accent}`}>
                                    <OurNearIcon name={item.icon} size="lg" />
                                </span>
                                <h3 className="mt-2 text-sm font-semibold text-ink group-hover:text-brand">{item.title}</h3>
                                <p className="mt-1 flex-1 text-xs leading-relaxed text-muted">{item.body}</p>
                                {item.href ? (
                                    <span className="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-brand">
                                        Learn more
                                        <OurNearIcon name="chevron-right" size="sm" className="text-brand" />
                                    </span>
                                ) : (
                                    <span className="mt-3 text-xs font-medium text-muted">Coming soon on jobs board</span>
                                )}
                            </>
                        );

                        if (item.href) {
                            return (
                                <Link key={item.title} href={item.href} className={`${candidateShell} ${focusRing}`}>
                                    {inner}
                                </Link>
                            );
                        }

                        return (
                            <article key={item.title} className={`${candidateShell} opacity-95`} aria-disabled="true">
                                {inner}
                            </article>
                        );
                    })}
                </div>
            </PageContainer>
        </section>
    );
}
