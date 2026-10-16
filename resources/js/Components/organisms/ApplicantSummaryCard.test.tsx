/**
 * @vitest-environment jsdom
 */
import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it } from 'vitest';
import ApplicantSummaryCard from '@/Components/organisms/ApplicantSummaryCard';

describe('ApplicantSummaryCard', () => {
    afterEach(() => {
        cleanup();
    });

    it('renders a populated applicant summary', () => {
        render(
            <ApplicantSummaryCard
                name="Aisha Khan"
                statusLabel="Under Review"
                initials="AK"
                designation="Product Manager"
                metaLine="Dubai · 8 years · MBA"
                contextPrefix="Applied for"
                contextTitle="Senior PM — Marketplace"
                timeLine="Applied 3 days ago"
                referenceLine="Application ID APP-1001"
                actions={
                    <button type="button">
                        View application
                    </button>
                }
            />,
        );

        expect(screen.getByRole('article')).toBeTruthy();
        expect(screen.getByRole('heading', { name: 'Aisha Khan' })).toBeTruthy();
        expect(screen.getByText('Under Review')).toBeTruthy();
        expect(screen.getByText('Product Manager')).toBeTruthy();
        expect(screen.getByText('Dubai · 8 years · MBA')).toBeTruthy();
        expect(screen.getByRole('button', { name: 'View application' })).toBeTruthy();
    });

    it('renders minimal fields without broken layout', () => {
        render(
            <ApplicantSummaryCard
                name="Jordan Lee"
                statusLabel="New"
                initials="JL"
                contextPrefix="Submitted for"
                contextTitle="Warehouse Supervisor"
            />,
        );

        expect(screen.getByRole('heading', { name: 'Jordan Lee' })).toBeTruthy();
        expect(screen.getByText('Submitted for:')).toBeTruthy();
        expect(screen.getByText('Warehouse Supervisor')).toBeTruthy();
        expect(screen.queryByText('Application ID')).toBeNull();
    });
});
