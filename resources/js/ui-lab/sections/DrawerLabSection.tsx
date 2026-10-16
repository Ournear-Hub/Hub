import Button from '@/Components/atoms/Button';
import Drawer from '@/Components/molecules/Drawer';
import Tabs from '@/Components/molecules/Tabs';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { drawerCatalog } from '@/ui-lab/catalog';
import { useState } from 'react';

export default function DrawerLabSection() {
    const [openMd, setOpenMd] = useState(false);
    const [openXl, setOpenXl] = useState(false);
    const [tab, setTab] = useState('overview');

    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Default width (md)">
                    <Button type="button" surface="admin" onClick={() => setOpenMd(true)}>
                        Open drawer
                    </Button>
                    <Drawer
                        open={openMd}
                        onClose={() => setOpenMd(false)}
                        title="Notification detail"
                        description="Entity workspace shell — scrollable body, optional footer."
                    >
                        <p className="text-sm text-ink">
                            Callers compose tabs, tables, and forms here. Async loading belongs in children (e.g. Skeleton), not
                            on Drawer.
                        </p>
                    </Drawer>
                </LabPanel>

                <LabPanel title="Wide workspace (xl)">
                    <Button type="button" surface="admin" variant="secondary" onClick={() => setOpenXl(true)}>
                        Open xl drawer
                    </Button>
                    <Drawer
                        open={openXl}
                        onClose={() => setOpenXl(false)}
                        title="CAN-10482"
                        size="xl"
                        footer={
                            <div className="flex justify-end gap-2">
                                <Button type="button" surface="admin" variant="secondary" onClick={() => setOpenXl(false)}>
                                    Cancel
                                </Button>
                                <Button type="button" surface="admin" onClick={() => setOpenXl(false)}>
                                    Save
                                </Button>
                            </div>
                        }
                    >
                        <Tabs
                            aria-label="Candidate sections"
                            tabs={[
                                { id: 'overview', label: 'Overview' },
                                { id: 'activity', label: 'Activity' },
                            ]}
                            active={tab}
                            onChange={setTab}
                        />
                        <p className="mt-4 text-sm text-muted">
                            {tab === 'overview' ? 'Identity and recruitment summary (caller-owned).' : 'Activity tab content.'}
                        </p>
                    </Drawer>
                </LabPanel>

                <LabPanel title="Dialog boundary">
                    <p className="text-sm text-muted">
                        <strong className="font-medium text-ink">Dialog</strong> (PR-5H) remains the centered{' '}
                        <code className="text-ink">max-w-md</code> task modal. Drawer is the full-height right workspace — not a
                        Dialog presentation prop.
                    </p>
                </LabPanel>

                <LabPanel title="V2 evidence (read-only)">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>
                            <code className="text-ink">Admin/ui/Drawer.tsx</code> — ~26 usages across admin index pages +
                            ApplicationWorkspacePage.
                        </li>
                        <li>
                            <code className="text-ink">size="xl"</code> on heavy entity drawers (Candidates, Employers, Placements,
                            …); default md width otherwise.
                        </li>
                        <li>
                            V2 <code className="text-ink">loading</code> prop unused by consumers — omitted from DS molecule.
                        </li>
                        <li>All V2 drawers are right-aligned; no slide animation in V2.</li>
                    </ul>
                </LabPanel>
            </div>

            <InfoPanel entry={drawerCatalog} />
        </div>
    );
}
