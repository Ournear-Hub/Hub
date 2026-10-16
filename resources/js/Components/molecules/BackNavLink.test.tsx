import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import BackNavLink, { BackNavButton } from '@/Components/molecules/BackNavLink';

describe('BackNavLink', () => {
    it('renders href and visible label with back icon', () => {
        render(<BackNavLink href="/account/applications" label="Back to applications" />);

        const link = screen.getByRole('link', { name: /Back to applications/i });
        expect(link.getAttribute('href')).toBe('/account/applications');
    });

    it('renders button variant for in-page back', () => {
        render(<BackNavButton label="Back to profile" onClick={() => undefined} />);

        expect(screen.getByRole('button', { name: /Back to profile/i })).toBeTruthy();
    });
});
