import ScreeningStagesTable from '@/Components/organisms/ScreeningStagesTable';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { screeningStagesTableCatalog } from '@/ui-lab/catalog';

const sampleRows = [
    {
        id: 'screening_started',
        stageLabel: 'Screening started',
        statusLabel: 'Complete',
        occurredAt: '12 Mar 2026, 09:14',
        performedBy: 'Recruiter A',
        remarks: 'Initial review complete',
        evidence: 'Not recorded yet',
    },
    {
        id: 'contact',
        stageLabel: 'Contact',
        statusLabel: 'Pending',
        occurredAt: 'Not recorded yet',
        performedBy: 'Not recorded yet',
        remarks: 'Not recorded yet',
        evidence: 'Not recorded yet',
    },
    {
        id: 'interviewed',
        stageLabel: 'Interviewed',
        statusLabel: 'Not started',
        occurredAt: 'Not recorded yet',
        performedBy: 'Not recorded yet',
        remarks: 'Not recorded yet',
        evidence: 'Not recorded yet',
    },
];

export default function ScreeningStagesTableLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Screening stages (lab data)">
                    <ScreeningStagesTable rows={sampleRows} />
                </LabPanel>

                <LabPanel title="Empty">
                    <ScreeningStagesTable rows={[]} empty="No screening stages recorded yet." />
                </LabPanel>

                <LabPanel title="Screening boundary">
                    <p className="text-sm text-muted">
                        Stage definitions and screening mutations stay in Laravel and{' '}
                        <code className="text-ink">ApplicationDrawer.tsx</code>. Map{' '}
                        <code className="text-ink">ScreeningStageRow</code> payloads to{' '}
                        <code className="text-ink">ScreeningStagesTableRow</code> on the consumer.
                    </p>
                </LabPanel>
            </div>

            <InfoPanel entry={screeningStagesTableCatalog} />
        </div>
    );
}
