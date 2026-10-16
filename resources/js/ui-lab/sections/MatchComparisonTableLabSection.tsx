import MatchComparisonTable from '@/Components/organisms/MatchComparisonTable';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { matchComparisonTableCatalog } from '@/ui-lab/catalog';

const sampleRows = [
    {
        id: 'location',
        factorLabel: 'Location',
        sourceHint: 'Application snapshot',
        jobValue: 'Dubai',
        candidateValue: 'Dubai',
        stateLabel: 'Matched',
        detailText: 'Recorded values match.',
    },
    {
        id: 'experience',
        factorLabel: 'Experience range',
        sourceHint: 'Current candidate profile',
        jobValue: '5+ years',
        candidateValue: '3 years',
        stateLabel: 'Partial',
        detailText: 'Recorded values do not match.',
    },
    {
        id: 'qualification',
        factorLabel: 'Qualification',
        sourceHint: 'Application snapshot',
        jobValue: 'Bachelor degree',
        candidateValue: 'Not recorded',
        stateLabel: 'Missing',
        detailText: 'Candidate value not recorded on application.',
    },
];

export default function MatchComparisonTableLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Screening comparison (lab data)">
                    <MatchComparisonTable
                        disclaimer="Match % compares recorded fields only; does not determine suitability; human decision remains final."
                        rows={sampleRows}
                        snapshotNote="Location, experience, and qualification use the application snapshot. Other professional fields use the current candidate profile when an account exists."
                    />
                </LabPanel>

                <LabPanel title="Empty">
                    <MatchComparisonTable rows={[]} empty="No match factors recorded yet." />
                </LabPanel>

                <LabPanel title="Screening boundary">
                    <p className="text-sm text-muted">
                        Scoring, suitability, and screening actions stay in Laravel services and{' '}
                        <code className="text-ink">ApplicationDrawer.tsx</code>. Map server rows to{' '}
                        <code className="text-ink">MatchComparisonTableRow</code> on the consumer.
                    </p>
                </LabPanel>
            </div>

            <InfoPanel entry={matchComparisonTableCatalog} />
        </div>
    );
}
