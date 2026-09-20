type ActivityItem = {
    id: string | number;
    title: string;
    meta?: string;
};

type ActivityFeedProps = {
    items: ActivityItem[];
    empty?: string;
};

export default function ActivityFeed({ items, empty = 'No activity yet.' }: ActivityFeedProps) {
    if (items.length === 0) {
        return <p className="text-sm text-muted">{empty}</p>;
    }

    return (
        <ul className="space-y-2">
            {items.map((item) => (
                <li key={item.id} className="rounded-md bg-surface px-3 py-2 text-sm">
                    <p className="text-ink">{item.title}</p>
                    {item.meta ? <p className="mt-1 text-xs text-muted">{item.meta}</p> : null}
                </li>
            ))}
        </ul>
    );
}
