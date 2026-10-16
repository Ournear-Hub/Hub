import { Link } from '@inertiajs/react';
import Card from '@/Components/atoms/Card';
import OurNearIcon from '@/icons/OurNearIcon';
import type { PublicModule } from '@/content/publicModules';

type Props = PublicModule & {
    className?: string;
};

export default function PublicModuleCard({ title, benefit, href, cta, icon, className = '' }: Props) {
    return (
        <Card as="article" padding="standard" className={`flex min-w-0 flex-col ${className}`.trim()}>
            <div className="flex items-start gap-3">
                <span className="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand/10 text-brand">
                    <OurNearIcon name={icon} size="md" className="text-brand" />
                </span>
                <div className="min-w-0">
                    <h3 className="text-base font-semibold text-ink">{title}</h3>
                    <p className="mt-1 text-sm leading-snug text-muted">{benefit}</p>
                </div>
            </div>
            <Link
                href={href}
                className="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-brand hover:underline"
            >
                {cta}
                <OurNearIcon name="chevron-right" size="sm" className="text-brand" />
            </Link>
        </Card>
    );
}
