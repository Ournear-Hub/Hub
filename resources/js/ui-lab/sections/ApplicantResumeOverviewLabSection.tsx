import ApplicantResumeOverview from '@/Components/organisms/ApplicantResumeOverview';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { applicantResumeOverviewCatalog } from '@/ui-lab/catalog';

export default function ApplicantResumeOverviewLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="With summary and resume actions">
                    <ApplicantResumeOverview
                        summary={{
                            designation: 'Site Engineer',
                            experience: '6 years',
                            qualification: 'B.E. Civil',
                            location: 'Dubai',
                            skillsLine: 'AutoCAD · QA/QC · HSE',
                            educationLine: 'Anna University',
                        }}
                        emptyMessage="Resume not available."
                        actions={
                            <>
                                <a href="#" className="inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline">
                                    View
                                </a>
                                <a href="#" className="inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline">
                                    Download
                                </a>
                            </>
                        }
                        footnote={<p>Application resume snapshots are historical files from when the candidate applied.</p>}
                    />
                </LabPanel>

                <LabPanel title="Minimal (resume unavailable)">
                    <ApplicantResumeOverview emptyMessage="Resume not available for this application snapshot." />
                </LabPanel>

                <LabPanel title="Feature boundary">
                    <p className="text-sm text-muted">
                        Resume URLs, permissions, and mapping stay on employer application/submission pages. Pass{' '}
                        <code className="text-ink">actions</code> only when the server already authorized links.
                    </p>
                </LabPanel>
            </div>

            <InfoPanel entry={applicantResumeOverviewCatalog} />
        </div>
    );
}
