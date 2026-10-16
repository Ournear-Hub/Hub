import {
    timelineEmpty,
    timelineItem,
    timelineList,
    timelineMarker,
    timelineMeta,
    timelineTitle,
} from '@/design-system/classNames';
import type { ReactNode } from 'react';

export type TimelineItem = {
    id: string | number;
    title: string;
    meta?: ReactNode;
};

export type TimelineProps = {
    items: TimelineItem[];
    /** Message when `items` is empty (V2 default: "No timeline events yet."). */
    empty?: string;
    /** Accessible name when the list is the primary landmark for a history region. */
    'aria-label'?: string;
    className?: string;
};

export default function Timeline({
    items,
    empty = 'No timeline events yet.',
    'aria-label': ariaLabel,
    className = '',
}: TimelineProps) {
    if (items.length === 0) {
        return <p className={timelineEmpty}>{empty}</p>;
    }

    const listClass = `${timelineList} ${className}`.trim();

    return (
        <ol className={listClass} aria-label={ariaLabel}>
            {items.map((item) => (
                <li key={item.id} className={timelineItem}>
                    <span className={timelineMarker} aria-hidden="true" />
                    <p className={timelineTitle}>{item.title}</p>
                    {item.meta ? <p className={timelineMeta}>{item.meta}</p> : null}
                </li>
            ))}
        </ol>
    );
}
