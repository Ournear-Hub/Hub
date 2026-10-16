/**
 * @vitest-environment jsdom
 */
import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import DocumentGroupList from '@/Components/organisms/DocumentGroupList';

describe('DocumentGroupList', () => {
    it('renders empty copy when there are no items', () => {
        render(<DocumentGroupList items={[]} empty="No documents uploaded yet." />);

        expect(screen.getByText('No documents uploaded yet.')).toBeTruthy();
    });

    it('groups items by group label and falls back to Other', () => {
        render(
            <DocumentGroupList
                empty="Empty"
                items={[
                    { id: 1, group: 'Certificates', title: 'AWS cert', meta: 'pdf' },
                    { id: 2, group: 'Certificates', title: 'Safety training', meta: 'pdf' },
                    { id: 3, group: '', title: 'Misc file', meta: 'doc' },
                ]}
            />,
        );

        expect(screen.getByRole('heading', { name: 'Certificates' })).toBeTruthy();
        expect(screen.getByRole('heading', { name: 'Other' })).toBeTruthy();
        expect(screen.getByText('AWS cert')).toBeTruthy();
        expect(screen.getByText('Misc file')).toBeTruthy();
    });

    it('renders caller-provided actions without owning behavior', () => {
        render(
            <DocumentGroupList
                empty="Empty"
                items={[
                    {
                        id: 'a',
                        group: 'Resumes',
                        title: 'Resume 2026',
                        actions: (
                            <button type="button">
                                Download
                            </button>
                        ),
                    },
                ]}
            />,
        );

        expect(screen.getByRole('button', { name: 'Download' })).toBeTruthy();
    });
});
