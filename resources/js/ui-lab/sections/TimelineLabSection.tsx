import Timeline from '@/Components/molecules/Timeline';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { timelineCatalog } from '@/ui-lab/catalog';

const sampleItems = [
    { id: 1, title: 'Application submitted', meta: 'Candidate · 12 Mar 2026, 09:14' },
    { id: 2, title: 'Screening started', meta: 'Recruiter · 13 Mar 2026, 11:02' },
    { id: 3, title: 'Shortlisted for employer review', meta: 'System · 14 Mar 2026, 16:45' },
];

export default function TimelineLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Activity history">
                    <Timeline aria-label="Submission activity" items={sampleItems} />
                </LabPanel>

                <LabPanel title="Empty state">
                    <Timeline items={[]} empty="No submission activity yet." />
                </LabPanel>

                <LabPanel title="ActivityFeed boundary">
                    <p className="text-sm text-muted">
                        V2 <code className="text-ink">ActivityFeed</code> uses stacked surface cards (no vertical rail). It is a
                        separate admin pattern — not merged into Timeline.
                    </p>
                </LabPanel>

                <LabPanel title="V2 evidence (read-only)">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>
                            <code className="text-ink">Components/Admin/ui/Timeline.tsx</code> — drawers/tabs on Submissions,
                            Placements, Finance, Employees, ApplicationDrawer, etc.
                        </li>
                        <li>Account application/submission show pages use similar inline ordered lists (unchanged)</li>
                    </ul>
                </LabPanel>

                <LabPanel title="Non-goals">
                    <p className="text-sm text-muted">
                        Display-only. No fetching, workflow logic, expandable rows, or ActivityFeed card layout. Caller maps domain
                        events to <code className="text-ink">items</code>.
                    </p>
                </LabPanel>
            </div>

            <InfoPanel entry={timelineCatalog} />
        </div>
    );
}
