import { Link } from '@inertiajs/react';
import Button from '@/Components/atoms/Button';
import type { RecruiterCandidateSummaryData } from '@/Components/Account/Recruiter/RecruiterCandidateSummary';

export type EmployerCompareApplicant = RecruiterCandidateSummaryData & {
    match_label?: string | null;
    match_percent?: number | null;
    match_disclaimer?: string | null;
    skills?: string | null;
    current_company?: string | null;
    relocation?: string | null;
    notice_period?: string | null;
    current_salary?: string | null;
    expected_salary?: string | null;
};

type Props = {
    jobTitle: string;
    applicants: EmployerCompareApplicant[];
    onHide: () => void;
};

function recorded(value?: string | null): string {
    return value && value.trim() !== '' ? value : 'Not recorded yet';
}

function matchLabel(applicant: EmployerCompareApplicant): string {
    if (applicant.match_label && applicant.match_label.trim() !== '') {
        return applicant.match_label;
    }

    if (applicant.match_percent != null) {
        return `${applicant.match_percent}%`;
    }

    return 'Incomplete match data';
}

export default function EmployerApplicantComparePanel({ jobTitle, applicants, onHide }: Props) {
    const rows: Array<[string, (row: EmployerCompareApplicant) => string]> = [
        ['Application ID', (row) => recorded(row.business_id)],
        ['Designation', (row) => recorded(row.designation)],
        ['Current company', (row) => recorded(row.current_company)],
        ['Experience', (row) => recorded(row.experience)],
        ['Qualification', (row) => recorded(row.qualification)],
        ['Location', (row) => recorded(row.location)],
        ['Skills', (row) => recorded(row.skills)],
        ['Relocation', (row) => recorded(row.relocation)],
        ['Salary', (row) => recorded(row.current_salary ?? row.expected_salary)],
        ['Notice period', (row) => recorded(row.notice_period)],
        ['Pipeline stage', (row) => recorded(row.pipeline_stage_label ?? row.ats_stage_label)],
        ['Application status', (row) => recorded(row.status_label ?? row.status)],
        ['Match %', (row) => matchLabel(row)],
        ['Resume on file', (row) => (row.has_resume ? 'Yes' : 'No')],
    ];

    return (
        <div
            id="employer-applicant-compare-panel"
            className="mb-4 overflow-x-auto rounded-xl border border-line bg-white p-4 sm:p-5"
        >
            <div className="mb-3 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 className="text-sm font-semibold text-ink">Compare applicants for {jobTitle}</h2>
                    <div className="mt-2 flex flex-col gap-1 sm:flex-row sm:flex-wrap sm:gap-x-3">
                        {applicants.map((row) => (
                            <span key={row.id} data-compare-candidate="" className="text-sm font-medium text-ink">
                                {row.name}
                            </span>
                        ))}
                    </div>
                    <p className="mt-1 text-xs text-muted">
                        {applicants[0]?.match_disclaimer ??
                            'Match % compares recorded fields only; it does not rank, shortlist, reject, or hire.'}
                    </p>
                </div>
                <Button surface="public" variant="secondary" type="button" onClick={onHide}>
                    Hide comparison
                </Button>
            </div>

            <table className="min-w-full text-left text-sm">
                <thead className="bg-surface text-muted">
                    <tr>
                        <th className="sticky left-0 bg-surface px-3 py-2 font-medium">Field</th>
                        {applicants.map((row) => (
                            <th key={row.id} className="min-w-40 px-3 py-2 font-medium text-ink">
                                <div>{row.name}</div>
                                {row.application_href ? (
                                    <Link href={row.application_href} className="text-xs font-semibold text-brand hover:underline">
                                        Open application
                                    </Link>
                                ) : null}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {rows.map(([label, value]) => (
                        <tr key={label} className="border-t border-line">
                            <th className="sticky left-0 bg-white px-3 py-2 font-medium text-muted">{label}</th>
                            {applicants.map((row) => (
                                <td key={row.id} className="px-3 py-2 text-ink">
                                    {value(row)}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
