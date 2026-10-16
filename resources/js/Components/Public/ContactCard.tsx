import Card from '@/Components/atoms/Card';

type ContactCardProps = {
    label: string;
    value: string;
    href?: string | null;
};

export default function ContactCard({ label, value, href }: ContactCardProps) {
    return (
        <Card as="article" padding="standard">
            <h3 className="text-sm font-semibold text-ink">{label}</h3>
            {href ? (
                <a href={href} className="mt-2 block text-sm text-brand hover:underline" target="_blank" rel="noreferrer">
                    {value}
                </a>
            ) : (
                <p className="mt-2 text-sm text-muted">{value}</p>
            )}
        </Card>
    );
}
