/**
 * @vitest-environment jsdom
 */
import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it } from 'vitest';
import ScreeningStagesTable from '@/Components/organisms/ScreeningStagesTable';

describe('ScreeningStagesTable', () => {
    afterEach(() => {
        cleanup();
    });

    it('renders populated stage rows in caller order', () => {
        render(
            <ScreeningStagesTable
                rows={[
                    {
                        id: 'screening_started',
                        stageLabel: 'Screening started',
                        statusLabel: 'Complete',
                        occurredAt: '12 Mar 2026, 09:14',
                        performedBy: 'Recruiter A',
                        remarks: 'Initial review',
                        evidence: 'Not recorded yet',
                    },
                    {
                        id: 'contact',
                        stageLabel: 'Contact',
                        statusLabel: 'Pending',
                        occurredAt: 'Not recorded yet',
                        performedBy: 'Not recorded yet',
                        remarks: 'Not recorded yet',
                        evidence: 'Not recorded yet',
                    },
                ]}
            />,
        );

        const table = screen.getByRole('table', { name: 'Screening stages' });
        expect(table).toBeTruthy();
        expect(screen.getByText('Screening started')).toBeTruthy();
        expect(screen.getByText('Initial review')).toBeTruthy();
        expect(screen.getByText('Contact')).toBeTruthy();
        expect(screen.getByText('Recruiter A')).toBeTruthy();
    });

    it('renders empty copy when rows are empty and empty is provided', () => {
        render(<ScreeningStagesTable rows={[]} empty="No screening stages recorded yet." />);

        expect(screen.getByText('No screening stages recorded yet.')).toBeTruthy();
        expect(screen.queryByRole('table')).toBeNull();
    });
});
