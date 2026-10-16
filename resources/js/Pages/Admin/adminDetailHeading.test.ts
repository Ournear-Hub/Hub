import { readFileSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { describe, expect, it } from 'vitest';

const adminPagesDir = path.dirname(fileURLToPath(import.meta.url));

describe('admin detail heading hygiene (FE-504 wave 6)', () => {
    it('Users/Show keeps entity name in h1 only; breadcrumbs end at Users', () => {
        const source = readFileSync(path.join(adminPagesDir, 'Users', 'Show.tsx'), 'utf8');

        expect(source).toContain("{ label: 'Users', href: '/admin/users' }");
        expect(source).not.toMatch(/breadcrumbs[\s\S]*label:\s*user\.name/);
        expect(source).toMatch(/<h1 className="text-2xl font-semibold tracking-tight text-ink">\{user\.name\}<\/h1>/);
    });

    it('Candidates/ResumeImport uses DetailHeader h2 distinct from breadcrumb terminal label', () => {
        const source = readFileSync(path.join(adminPagesDir, 'Candidates', 'ResumeImport.tsx'), 'utf8');

        expect(source).toContain("label: 'Bulk resume import'");
        expect(source).toContain('headingLevel={2}');
        expect(source).toContain('title="Import and review resumes"');
        expect(source).not.toMatch(/<h1[^>]*>Bulk resume import<\/h1>/);
    });
});

describe('admin index heading consistency (FE-504 waves 7–8)', () => {
    const wave7Paths = ['Jobs/Index.tsx', 'Users/Index.tsx', 'Candidates/Index.tsx', 'Employers/Index.tsx'];
    const wave8Paths = ['Tasks/Index.tsx', 'Interviews/Index.tsx'];
    const wave9Paths = [
        'Submissions/Index.tsx',
        'Placements/Index.tsx',
        'Replacements/Index.tsx',
        'Employees/Index.tsx',
        'Work/Index.tsx',
    ];
    const wave10Paths = ['EmployerEnquiries/Index.tsx'];

    it.each([...wave7Paths, ...wave8Paths, ...wave9Paths, ...wave10Paths])(
        '%s uses DetailHeader h2 without duplicate section h1',
        (relativePath) => {
            const source = readFileSync(path.join(adminPagesDir, relativePath), 'utf8');
            expect(source).toContain('DetailHeader');
            expect(source).toContain('headingLevel={2}');
            expect(source).not.toMatch(/<h1 className="text-2xl font-semibold tracking-tight text-ink">/);
        },
    );

    it('Tasks/Index breadcrumb matches wave 7 Admin section pattern', () => {
        const source = readFileSync(path.join(adminPagesDir, 'Tasks/Index.tsx'), 'utf8');
        expect(source).toContain("breadcrumbs={[{ label: 'Admin', href: '/admin' }, { label: 'Tasks' }]}");
        expect(source).toContain('title="Staff follow-up worklist"');
    });

    it('Interviews/Index preserves scorecard templates link in DetailHeader actions', () => {
        const source = readFileSync(path.join(adminPagesDir, 'Interviews/Index.tsx'), 'utf8');
        expect(source).toContain('/admin/scorecard-templates');
        expect(source).toContain('title="Application interview schedule"');
    });

    it('Submissions/Index aligns breadcrumb with admin nav Recruitment Partnership label', () => {
        const source = readFileSync(path.join(adminPagesDir, 'Submissions/Index.tsx'), 'utf8');
        expect(source).toContain("label: 'Recruitment Partnership'");
        expect(source).toContain('title="Shortlisted candidates for employer review"');
    });

    it('EmployerEnquiries/Index aligns breadcrumb with admin nav Staffing Enquiries label', () => {
        const source = readFileSync(path.join(adminPagesDir, 'EmployerEnquiries/Index.tsx'), 'utf8');
        expect(source).toContain("label: 'Staffing Enquiries'");
        expect(source).toContain('title="Workforce and project requests"');
        expect(source).not.toMatch(/<h1[^>]*>Employer Enquiries<\/h1>/);
    });
});
