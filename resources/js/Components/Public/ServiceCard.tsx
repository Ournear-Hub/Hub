import Card from '@/Components/atoms/Card';

type ServiceCardProps = {
    title: string;
    summary: string;
    id?: string;
    bullets?: string[];
};

export default function ServiceCard({ title, summary, id, bullets = [] }: ServiceCardProps) {
    return (
        <Card as="article" padding="standard">
            <h3 id={id} className="text-lg font-semibold text-ink">
                {title}
            </h3>
            {summary ? <p className="mt-2 text-sm text-muted">{summary}</p> : null}
            {bullets.length > 0 ? (
                <ul className="mt-3 list-disc space-y-1 pl-5 text-sm text-muted">
                    {bullets.map((bullet) => (
                        <li key={bullet}>{bullet}</li>
                    ))}
                </ul>
            ) : null}
        </Card>
    );
}
