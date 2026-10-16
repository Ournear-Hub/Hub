/**
 * @vitest-environment jsdom
 */
import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import DetailSection from '@/Components/organisms/DetailSection';

describe('DetailSection', () => {
    afterEach(() => {
        cleanup();
    });

    it('renders title, description, and children', () => {
        render(
            <DetailSection title="About" description="Short professional summary.">
                <p>Candidate bio text</p>
            </DetailSection>,
        );

        expect(screen.getByRole('heading', { level: 2, name: 'About' })).toBeTruthy();
        expect(screen.getByText('Short professional summary.')).toBeTruthy();
        expect(screen.getByText('Candidate bio text')).toBeTruthy();
    });

    it('calls onEdit when the default Edit control is used', () => {
        const onEdit = vi.fn();

        render(
            <DetailSection title="About" onEdit={onEdit}>
                <p>Body</p>
            </DetailSection>,
        );

        screen.getByRole('button', { name: 'Edit' }).click();
        expect(onEdit).toHaveBeenCalledTimes(1);
    });

    it('renders caller-provided actions instead of default controls', () => {
        render(
            <DetailSection
                title="Skills"
                actions={
                    <button type="button">
                        Custom action
                    </button>
                }
            >
                <p>Skill list</p>
            </DetailSection>,
        );

        expect(screen.getByRole('button', { name: 'Custom action' })).toBeTruthy();
        expect(screen.queryByRole('button', { name: 'Edit' })).toBeNull();
    });
});
