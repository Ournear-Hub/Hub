import OurNearIcon from '@/icons/OurNearIcon';
import { SegmentedControl } from '@/Components/molecules';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { segmentedControlCatalog } from '@/ui-lab/catalog';
import { useState } from 'react';

export default function SegmentedControlLabSection() {
    const [view, setView] = useState('list');
    const [period, setPeriod] = useState('week');
    const [status, setStatus] = useState('active');
    const [detail, setDetail] = useState('overview');

    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Basic (two options)">
                    <SegmentedControl
                        aria-label="View"
                        options={[
                            { value: 'list', label: 'List' },
                            { value: 'grid', label: 'Grid' },
                        ]}
                        value={view}
                        onChange={setView}
                    />
                    <p className="mt-2 text-xs text-muted">Selected: {view}</p>
                </LabPanel>

                <LabPanel title="Three options">
                    <SegmentedControl
                        aria-label="Period"
                        options={[
                            { value: 'week', label: 'Week' },
                            { value: 'month', label: 'Month' },
                            { value: 'year', label: 'Year' },
                        ]}
                        value={period}
                        onChange={setPeriod}
                    />
                </LabPanel>

                <LabPanel title="Mode example">
                    <SegmentedControl
                        aria-label="Detail level"
                        options={[
                            { value: 'overview', label: 'Overview' },
                            { value: 'details', label: 'Details' },
                        ]}
                        value={detail}
                        onChange={setDetail}
                    />
                </LabPanel>

                <LabPanel title="Disabled option">
                    <SegmentedControl
                        aria-label="Archive filter"
                        options={[
                            { value: 'active', label: 'Active' },
                            { value: 'archived', label: 'Archived' },
                            { value: 'all', label: 'All', disabled: true },
                        ]}
                        value={status}
                        onChange={setStatus}
                    />
                </LabPanel>

                <LabPanel title="Fully disabled">
                    <SegmentedControl
                        aria-label="Disabled control"
                        options={[
                            { value: 'a', label: 'A' },
                            { value: 'b', label: 'B' },
                        ]}
                        value="a"
                        onChange={() => undefined}
                        disabled
                    />
                </LabPanel>

                <LabPanel title="Icon + label">
                    <SegmentedControl
                        aria-label="View with icons"
                        size="sm"
                        options={[
                            {
                                value: 'list',
                                label: (
                                    <>
                                        <OurNearIcon name="menu" size="xs" decorative />
                                        List
                                    </>
                                ),
                            },
                            {
                                value: 'grid',
                                label: (
                                    <>
                                        <OurNearIcon name="briefcase" size="xs" decorative />
                                        Grid
                                    </>
                                ),
                            },
                        ]}
                        value={view}
                        onChange={setView}
                    />
                </LabPanel>

                <LabPanel title="Keyboard & accessibility">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>Semantics: radiogroup + radio (not tablist)</li>
                        <li>Arrow keys move selection among enabled options</li>
                        <li>Home / End — first / last enabled option</li>
                        <li>Tab focuses the selected segment; Space/Enter activate focused segment</li>
                        <li>aria-checked reflects selection; no route or panel switching</li>
                    </ul>
                </LabPanel>
            </div>

            <InfoPanel entry={segmentedControlCatalog} />
        </div>
    );
}
