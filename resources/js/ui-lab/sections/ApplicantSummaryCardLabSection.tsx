import Button from '@/Components/atoms/Button';
import ApplicantSummaryCard from '@/Components/organisms/ApplicantSummaryCard';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { applicantSummaryCardCatalog } from '@/ui-lab/catalog';

export default function ApplicantSummaryCardLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Application summary (populated)">
                    <ApplicantSummaryCard
                        name="Aisha Khan"
                        statusLabel="Under Review"
                        initials="AK"
                        designation="Product Manager"
                        metaLine="Dubai · 8 years · MBA"
                        contextPrefix="Applied for"
                        contextTitle="Senior PM — Marketplace"
                        timeLine="Applied 3 days ago"
                        referenceLine="Application ID APP-1001"
                        notices={<p>Contact details appear on the application when provided at apply time.</p>}
                        actions={
                            <>
                                <Button surface="public" href="#" variant="primary" className="min-h-11 w-full sm:w-auto">
                                    View application
                                </Button>
                                <Button surface="public" href="#" variant="secondary" className="min-h-11 w-full sm:w-auto">
                                    View profile
                                </Button>
                            </>
                        }
                    />
                </LabPanel>

                <LabPanel title="Minimal fields">
                    <ApplicantSummaryCard
                        name="Jordan Lee"
                        statusLabel="New"
                        initials="JL"
                        contextPrefix="Submitted for"
                        contextTitle="Warehouse Supervisor"
                        timeLine="Submitted recently"
                        actions={
                            <Button surface="public" href="#" variant="primary" className="min-h-11 w-full sm:w-auto">
                                View submission
                            </Button>
                        }
                    />
                </LabPanel>

                <LabPanel title="Feature boundary">
                    <p className="text-sm text-muted">
                        Use <code className="text-ink">RecruiterCandidateSummary</code> in employer pages for
                        application vs submission mode, contact copy, and resume/profile links.
                    </p>
                </LabPanel>
            </div>

            <InfoPanel entry={applicantSummaryCardCatalog} />
        </div>
    );
}
