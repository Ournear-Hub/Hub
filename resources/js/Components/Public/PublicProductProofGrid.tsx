import { Link } from '@inertiajs/react';
import Card from '@/Components/atoms/Card';
import OurNearIcon from '@/icons/OurNearIcon';
import { productProofItems } from '@/content/publicModules';

type Props = {
    className?: string;
};

export default function PublicProductProofGrid({ className = '' }: Props) {
    return (
        <div className={`grid min-w-0 gap-3 sm:grid-cols-3 ${className}`}>
            {productProofItems.map((item) => (
                <Link key={item.title} href={item.href} className="group block min-w-0">
                    <Card
                        padding="compact"
                        className="h-full transition hover:border-brand/30 hover:shadow-sm"
                    >
                        <span className="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-accent/15 text-accent">
                            <OurNearIcon name={item.icon} size="sm" />
                        </span>
                        <h3 className="mt-3 text-sm font-semibold text-ink group-hover:text-brand">{item.title}</h3>
                        <p className="mt-1 text-xs leading-relaxed text-muted">{item.body}</p>
                    </Card>
                </Link>
            ))}
        </div>
    );
}
