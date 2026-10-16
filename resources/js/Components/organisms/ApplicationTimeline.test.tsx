/**
 * @vitest-environment jsdom
 */
import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it } from 'vitest';
import ApplicationTimeline, { mapApplicationTimelineEvents } from '@/Components/organisms/ApplicationTimeline';

describe('ApplicationTimeline', () => {
    afterEach(() => {
        cleanup();
    });

    it('maps application events to timeline items with stable ids', () => {
        const items = mapApplicationTimelineEvents([
            { key: 'applied', label: 'Application submitted', at: '2026-01-01' },
            { key: 'review', label: 'Under review', at: null },
        ]);

        expect(items).toEqual([
            { id: 'applied-2026-01-01', title: 'Application submitted', meta: '2026-01-01' },
            { id: 'review-none', title: 'Under review', meta: '—' },
        ]);
    });

    it('renders populated timeline inside a detail section', () => {
        render(
            <ApplicationTimeline
                events={[{ key: 'applied', label: 'Application submitted', at: 'Jan 1, 2026' }]}
                title="Timeline"
            />,
        );

        expect(screen.getByRole('heading', { name: 'Timeline' })).toBeTruthy();
        expect(screen.getByRole('list', { name: 'Application timeline' })).toBeTruthy();
        expect(screen.getByText('Application submitted')).toBeTruthy();
        expect(screen.getByText('Jan 1, 2026')).toBeTruthy();
    });

    it('shows empty copy when there are no events', () => {
        render(<ApplicationTimeline events={[]} empty="No activity recorded yet." />);

        expect(screen.getByText('No activity recorded yet.')).toBeTruthy();
    });

    it('returns null when hideWhenEmpty is set and events are empty', () => {
        const { container } = render(<ApplicationTimeline events={[]} hideWhenEmpty />);

        expect(container.firstChild).toBeNull();
    });

    it('surfaces loading and error states without fabricating events', () => {
        const { rerender } = render(<ApplicationTimeline events={[]} loading />);

        expect(screen.getByRole('status', { name: 'Loading application timeline' })).toBeTruthy();

        rerender(<ApplicationTimeline events={[]} error="Could not load history." />);

        expect(screen.getByText('Timeline unavailable')).toBeTruthy();
        expect(screen.getByText('Could not load history.')).toBeTruthy();
        expect(screen.queryByRole('list')).toBeNull();
    });
});
