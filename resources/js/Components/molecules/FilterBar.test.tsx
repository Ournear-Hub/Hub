/**
 * @vitest-environment jsdom
 */
import FilterBar from '@/Components/molecules/FilterBar';
import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';

describe('FilterBar', () => {
    afterEach(() => {
        cleanup();
    });

    it('renders filter controls passed as children', () => {
        render(
            <FilterBar onSubmit={() => {}}>
                <input name="q" type="search" aria-label="Search jobs" />
                <button type="submit">Apply</button>
                <button type="button">Reset filters</button>
            </FilterBar>,
        );

        expect(screen.getByRole('searchbox', { name: 'Search jobs' })).toBeTruthy();
        expect(screen.getByRole('button', { name: 'Apply' })).toBeTruthy();
        expect(screen.getByRole('button', { name: 'Reset filters' })).toBeTruthy();
    });

    it('invokes onSubmit when the form is submitted', () => {
        const onSubmit = vi.fn((event: { preventDefault: () => void }) => {
            event.preventDefault();
        });

        render(
            <FilterBar onSubmit={onSubmit}>
                <input name="q" defaultValue="nurse" aria-label="Keyword" />
                <button type="submit">Filter</button>
            </FilterBar>,
        );

        fireEvent.submit(screen.getByRole('button', { name: 'Filter' }).closest('form')!);

        expect(onSubmit).toHaveBeenCalledTimes(1);
    });

    it('merges optional className onto the form shell', () => {
        render(
            <FilterBar onSubmit={() => {}} className="md:grid-cols-6">
                <span data-testid="slot">child</span>
            </FilterBar>,
        );

        const form = screen.getByTestId('slot').closest('form');
        expect(form?.className).toContain('md:grid-cols-6');
    });
});
