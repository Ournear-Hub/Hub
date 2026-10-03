type TimelineItem = {
    id: string | number;
    title: string;
    meta?: string;
};

type TimelineProps = {
    items: TimelineItem[];
    empty?: string;
};

export default function Timeline({ items, empty = 'No timeline events yet.' }: TimelineProps) {
    if (items.length === 0) {
        return <p className="text-sm text-muted">{empty}</p>;
    }

    return (
        <ol className="space-y-3 border-l border-line pl-4">
            {items.map((item) => (
                <li key={item.id} className="relative text-sm">
                    <span className="absolute -left-[21px] mt-1.5 h-2.5 w-2.5 rounded-full bg-brand" />
                    <p className="font-medium text-ink">{item.title}</p>
                    {item.meta ? <p className="mt-0.5 text-xs text-muted">{item.meta}</p> : null}
                </li>
            ))}
        </ol>
    );
}
