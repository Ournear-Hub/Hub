import { render } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import AccountNavItemIcon from '@/Components/Account/AccountNavItemIcon';

describe('AccountNavItemIcon', () => {
    it('renders nothing when icon metadata is absent', () => {
        const { container } = render(<AccountNavItemIcon />);
        expect(container.firstChild).toBeNull();
    });

    it('renders registry icon when name is valid', () => {
        const { container } = render(<AccountNavItemIcon icon="home" active={false} />);
        expect(container.querySelector('svg')).not.toBeNull();
    });

    it('tolerates unknown icon names without throwing', () => {
        const { container } = render(<AccountNavItemIcon icon="not-a-real-icon" />);
        expect(container.querySelector('[data-missing-icon]')).not.toBeNull();
    });
});
