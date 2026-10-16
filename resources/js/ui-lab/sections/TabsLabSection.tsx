import { useState } from 'react';
import Tabs from '@/Components/molecules/Tabs';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { tabsCatalog } from '@/ui-lab/catalog';

const workspaceTabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'activity', label: 'Activity' },
    { id: 'documents', label: 'Documents' },
];

export default function TabsLabSection() {
    const [active, setActive] = useState('overview');

    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Panels API (full tab/tabpanel wiring)">
                    <Tabs
                        aria-label="Workspace sections"
                        tabs={workspaceTabs}
                        active={active}
                        onChange={setActive}
                        panels={{
                            overview: <p className="text-sm text-ink">Overview content for the selected workspace.</p>,
                            activity: <p className="text-sm text-ink">Activity timeline and audit entries.</p>,
                            documents: <p className="text-sm text-ink">Uploaded files and verification documents.</p>,
                        }}
                    />
                </LabPanel>

                <LabPanel title="Children slot (single active panel)">
                    <Tabs
                        aria-label="Demo tabs"
                        tabs={[
                            { id: 'a', label: 'Tab A' },
                            { id: 'b', label: 'Tab B' },
                        ]}
                        active="a"
                        onChange={() => undefined}
                    >
                        <p className="text-sm text-muted">Caller renders the active body (V2 Users/Show pattern).</p>
                    </Tabs>
                </LabPanel>

                <LabPanel title="Tablist only (head-only)">
                    <p className="mb-3 text-xs text-muted">
                        Some V2 pages render panel content as siblings below the tab row. Prefer <code className="text-ink">panels</code>{' '}
                        for new work.
                    </p>
                    <Tabs
                        aria-label="Campaign views"
                        tabs={[
                            { id: 'campaigns', label: 'Campaigns' },
                            { id: 'links', label: 'Links' },
                            { id: 'shares', label: 'Shares' },
                        ]}
                        active="campaigns"
                        onChange={() => undefined}
                    />
                </LabPanel>

                <LabPanel title="SegmentedControl boundary">
                    <p className="text-sm text-muted">
                        <code className="text-ink">SegmentedControl</code> = <code className="text-ink">radiogroup</code> for compact
                        mode/value toggles without tabpanels. <code className="text-ink">Tabs</code> ={' '}
                        <code className="text-ink">tablist</code> + associated panels.
                    </p>
                </LabPanel>

                <LabPanel title="V2 evidence (read-only)">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>
                            <code className="text-ink">Components/Admin/ui/Tabs.tsx</code> — ~15 admin consumers, partial ARIA (no
                            keyboard, weak panel wiring)
                        </li>
                        <li>URL/query “tabs” on Settings, Website Content, ProfileShell → navigation or ad-hoc buttons (not migrated)</li>
                    </ul>
                </LabPanel>

                <LabPanel title="Non-goals">
                    <p className="text-sm text-muted">
                        No Inertia/router, URL sync, route detection, or shell layout. Caller owns <code className="text-ink">active</code>{' '}
                        and <code className="text-ink">onChange</code>.
                    </p>
                </LabPanel>
            </div>

            <InfoPanel entry={tabsCatalog} />
        </div>
    );
}
