/**
 * @vitest-environment jsdom
 */
import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it } from 'vitest';
import MatchComparisonTable from '@/Components/organisms/MatchComparisonTable';

describe('MatchComparisonTable', () => {
    afterEach(() => {
        cleanup();
    });

    it('renders populated comparison rows in caller order', () => {
        render(
            <MatchComparisonTable
                rows={[
                    {
                        id: 'location',
                        factorLabel: 'Location',
                        sourceHint: 'Application snapshot',
                        jobValue: 'Dubai',
                        candidateValue: 'Dubai',
                        stateLabel: 'Matched',
                        detailText: 'Recorded values match.',
                    },
                    {
                        id: 'experience',
                        factorLabel: 'Experience',
                        sourceHint: 'Current candidate profile',
                        jobValue: '5+ years',
                        candidateValue: '3 years',
                        stateLabel: 'Partial',
                        detailText: 'Recorded values do not match.',
                    },
                ]}
                snapshotNote="Snapshot note for lab."
            />,
        );

        const table = screen.getByRole('table', { name: 'Candidate versus job comparison' });
        expect(table).toBeTruthy();

        const rows = screen.getAllByRole('row');
        expect(rows.length).toBeGreaterThan(2);

        expect(screen.getByText('Location')).toBeTruthy();
        expect(screen.getByText('Application snapshot')).toBeTruthy();
        expect(screen.getByText('Recorded values match.')).toBeTruthy();
        expect(screen.getByText('Experience')).toBeTruthy();
        expect(screen.getByText('Snapshot note for lab.')).toBeTruthy();
    });

    it('renders empty copy when rows are empty and empty is provided', () => {
        render(<MatchComparisonTable rows={[]} empty="No match factors recorded yet." />);

        expect(screen.getByText('No match factors recorded yet.')).toBeTruthy();
        expect(screen.queryByRole('table')).toBeNull();
    });
});
