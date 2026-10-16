import { Link } from '@inertiajs/react';
import Card from '@/Components/atoms/Card';
import OurNearIcon from '@/icons/OurNearIcon';
import { focusRing } from '@/design-system/classNames';

type IndustryCardProps = {
    name: string;
    href?: string;
    icon?: string;
};

export default function IndustryCard({ name, href, icon = 'briefcase' }: IndustryCardProps) {
    const inner = (
        <>
            <span className="mx-auto inline-flex h-10 w-10 items-center justify-center rounded-lg bg-brand/10 text-brand">
                <OurNearIcon name={icon} size="lg" />
            </span>
            <h3 className="mt-2 text-sm font-bold leading-snug text-ink">{name}</h3>
        </>
    );

    if (href) {
        return (
            <Link href={href} className={`block h-full ${focusRing}`}>
                <Card as="article" padding="standard" className="h-full min-h-[7.5rem] py-5 text-center transition hover:border-brand/40 hover:shadow-sm">
                    {inner}
                </Card>
            </Link>
        );
    }

    return (
        <Card as="article" padding="standard" className="h-full min-h-[7.5rem] py-5 text-center">
            {inner}
        </Card>
    );
}
