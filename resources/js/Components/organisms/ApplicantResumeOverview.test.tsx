/**
 * @vitest-environment jsdom
 */
import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it } from 'vitest';
import ApplicantResumeOverview from '@/Components/organisms/ApplicantResumeOverview';

describe('ApplicantResumeOverview', () => {
    afterEach(() => {
        cleanup();
    });

    it('renders resume actions when supplied', () => {
        render(
            <ApplicantResumeOverview
                emptyMessage="No resume"
                actions={
                    <>
                        <a href="/resume/view">View</a>
                        <a href="/resume/download">Download</a>
                    </>
                }
                footnote={<p>Historical snapshot only.</p>}
            />,
        );

        expect(screen.getByRole('region', { name: 'Applicant resume overview' })).toBeTruthy();
        expect(screen.getByRole('heading', { name: 'Resume' })).toBeTruthy();
        expect(screen.getByRole('link', { name: 'View' })).toBeTruthy();
        expect(screen.getByText('Historical snapshot only.')).toBeTruthy();
    });

    it('renders optional summary fields and omits missing ones', () => {
        render(
            <ApplicantResumeOverview
                summary={{
                    designation: 'Site Engineer',
                    experience: '6 years',
                    qualification: null,
                    skillsLine: '',
                }}
                emptyMessage="No resume on file."
                actions={<a href="/resume">View</a>}
            />,
        );

        expect(screen.getByRole('heading', { name: 'Professional summary' })).toBeTruthy();
        expect(screen.getByText('Site Engineer')).toBeTruthy();
        expect(screen.getByText('6 years')).toBeTruthy();
        expect(screen.queryByText('Qualification')).toBeNull();
        expect(screen.queryByText('Skills')).toBeNull();
    });

    it('shows empty message without inventing resume links', () => {
        render(<ApplicantResumeOverview emptyMessage="Resume not available for this snapshot." />);

        expect(screen.getByText('Resume not available for this snapshot.')).toBeTruthy();
        expect(screen.queryByRole('link')).toBeNull();
    });
});
