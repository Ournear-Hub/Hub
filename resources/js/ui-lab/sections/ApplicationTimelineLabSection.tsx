import ApplicationTimeline from '@/Components/organisms/ApplicationTimeline';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { applicationTimelineCatalog } from '@/ui-lab/catalog';

const sampleEvents = [
    { key: 'submitted', label: 'Application submitted', at: 'Jan 12, 2026 · 10:42' },
    { key: 'review', label: 'Moved to Reviewing', at: 'Jan 14, 2026 · 09:05' },
    { key: 'screen', label: 'Screening scheduled', at: null },
];

export default function ApplicationTimelineLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Populated application history">
                    <ApplicationTimeline events={sampleEvents} description="Chronological recruitment events supplied by the page." />
                </LabPanel>

                <LabPanel title="Empty (candidate detail)">
                    <ApplicationTimeline events={[]} empty="No timeline events yet." title="Timeline" />
                </LabPanel>

                <LabPanel title="Hidden when empty (employer detail)">
                    <p className="mb-3 text-sm text-muted">
                        With <code className="text-ink">hideWhenEmpty</code>, nothing renders below this note.
                    </p>
                    <ApplicationTimeline events={[]} hideWhenEmpty />
                </LabPanel>

                <LabPanel title="Loading and error">
                    <div className="space-y-4">
                        <ApplicationTimeline events={[]} loading title="Timeline" />
                        <ApplicationTimeline events={[]} error="Could not load application history." title="Timeline" />
                    </div>
                </LabPanel>

                <LabPanel title="Feature boundary">
                    <p className="text-sm text-muted">
                        Maps server <code className="text-ink">{'{ key, label, at }'}</code> rows to the frozen{' '}
                        <strong>Timeline</strong> molecule. Event selection, permissions, and persistence stay on account/admin
                        pages — not inside the organism.
                    </p>
                </LabPanel>
            </div>

            <InfoPanel entry={applicationTimelineCatalog} />
        </div>
    );
}
