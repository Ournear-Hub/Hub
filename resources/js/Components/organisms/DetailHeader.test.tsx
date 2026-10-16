/**
 * @vitest-environment jsdom
 */
import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import DetailHeader from '@/Components/organisms/DetailHeader';

describe('DetailHeader', () => {
    it('renders title with default h1 heading', () => {
        render(<DetailHeader title="Candidate profile" />);

        expect(screen.getByRole('banner')).toBeTruthy();
        const heading = screen.getByRole('heading', { level: 1, name: 'Candidate profile' });
        expect(heading).toBeTruthy();
    });

    it('renders eyebrow, description, meta, and actions when provided', () => {
        render(
            <DetailHeader
                eyebrow="Billing"
                headingLevel={2}
                title="Invoices"
                description="Download and review issued invoices."
                meta={<span data-testid="meta-slot">Meta</span>}
                actions={<button type="button">Download</button>}
            />,
        );

        expect(screen.getByText('Billing')).toBeTruthy();
        expect(screen.getByRole('heading', { level: 2, name: 'Invoices' })).toBeTruthy();
        expect(screen.getByText('Download and review issued invoices.')).toBeTruthy();
        expect(screen.getByTestId('meta-slot')).toBeTruthy();
        expect(screen.getByRole('button', { name: 'Download' })).toBeTruthy();
    });
});
