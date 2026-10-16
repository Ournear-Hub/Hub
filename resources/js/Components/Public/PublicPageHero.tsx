import type { ReactNode } from 'react';
import Breadcrumbs, { type BreadcrumbItem } from '@/Components/Navigation/Breadcrumbs';
import PageContainer from '@/Components/Public/PageContainer';
import { typography } from '@/design-system/classNames';

type Props = {
    eyebrow?: string;
    title: string;
    description?: string;
    actions?: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
    /** Brand teal band (default) or light canvas */
    tone?: 'brand' | 'canvas';
    /** Tighter vertical rhythm for service pages */
    compact?: boolean;
};

export default function PublicPageHero({ eyebrow, title, description, actions, breadcrumbs, tone = 'brand', compact = false }: Props) {
    const isBrand = tone === 'brand';
    const pad = compact ? 'py-5 sm:py-6' : 'py-8 sm:py-10';
    const titleClass = compact ? 'text-2xl sm:text-3xl' : 'text-3xl sm:text-4xl';

    return (
        <section className={`border-b border-line ${isBrand ? 'bg-brand text-white' : 'bg-canvas text-ink'}`}>
            <PageContainer className={`min-w-0 ${pad}`}>
                {breadcrumbs ? (
                    <div
                        className={
                            isBrand
                                ? 'mb-4 text-white/90 [&_a]:text-white [&_a:hover]:text-white [&_li]:text-white/75'
                                : 'mb-4'
                        }
                    >
                        <Breadcrumbs items={breadcrumbs} />
                    </div>
                ) : null}
                {eyebrow ? (
                    <p className={isBrand ? 'text-xs font-semibold uppercase tracking-wide text-white/80' : typography.eyebrow}>
                        {eyebrow}
                    </p>
                ) : null}
                <h1
                    className={`mt-2 font-semibold tracking-tight ${titleClass} ${isBrand ? 'text-white' : typography.pageTitle}`}
                >
                    {title}
                </h1>
                {description ? (
                    <p className={`mt-2 max-w-2xl text-sm leading-snug ${isBrand ? 'text-white/85' : typography.secondary}`}>
                        {description}
                    </p>
                ) : null}
                {actions ? <div className={`flex flex-wrap gap-3 ${compact ? 'mt-4' : 'mt-6'}`}>{actions}</div> : null}
            </PageContainer>
        </section>
    );
}
