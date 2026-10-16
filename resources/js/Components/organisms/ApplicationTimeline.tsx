import Spinner from '@/Components/atoms/Spinner';
import Alert from '@/Components/molecules/Alert';
import Timeline from '@/Components/molecules/Timeline';
import type { TimelineItem } from '@/Components/molecules/Timeline';
import DetailSection from '@/Components/organisms/DetailSection';
import type { ReactNode } from 'react';

/** Server-shaped application / recruitment history row (feature layer supplies data). */
export type ApplicationTimelineEvent = {
    key: string;
    label: string;
    at?: string | null;
};

export type ApplicationTimelineProps = {
    events: ApplicationTimelineEvent[];
    /** Section heading (default: Application timeline). */
    title?: string;
    description?: string;
    /** Passed to the Timeline molecule when `events` is empty. */
    empty?: string;
    /** Omit the entire block when there are no events (employer detail pattern). */
    hideWhenEmpty?: boolean;
    loading?: boolean;
    error?: string | null;
    className?: string;
    /** Accessible name for the underlying timeline list. */
    'aria-label'?: string;
};

export function mapApplicationTimelineEvents(events: ApplicationTimelineEvent[]): TimelineItem[] {
    return events.map((event) => ({
        id: `${event.key}-${event.at ?? 'none'}`,
        title: event.label,
        meta: event.at ?? '—',
    }));
}

export default function ApplicationTimeline({
    events,
    title = 'Application timeline',
    description,
    empty = 'No timeline events yet.',
    hideWhenEmpty = false,
    loading = false,
    error = null,
    className = '',
    'aria-label': ariaLabel = 'Application timeline',
}: ApplicationTimelineProps) {
    if (hideWhenEmpty && !loading && !error && events.length === 0) {
        return null;
    }

    let body: ReactNode;
    if (loading) {
        body = <Spinner size="sm" label="Loading application timeline" />;
    } else if (error) {
        body = (
            <Alert variant="error" title="Timeline unavailable">
                {error}
            </Alert>
        );
    } else {
        body = <Timeline items={mapApplicationTimelineEvents(events)} empty={empty} aria-label={ariaLabel} />;
    }

    const section = (
        <DetailSection title={title} description={description}>
            {body}
        </DetailSection>
    );

    if (!className) {
        return section;
    }

    return <div className={className}>{section}</div>;
}
