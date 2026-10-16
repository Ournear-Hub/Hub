import { Divider, Text } from '@/Components/atoms';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { dividerCatalog } from '@/ui-lab/catalog';

export default function DividerLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Horizontal (decorative)">
                    <div className="space-y-3 rounded-xl border border-line bg-white p-4">
                        <Text>Section one</Text>
                        <Divider />
                        <Text>Section two</Text>
                    </div>
                </LabPanel>

                <LabPanel title="Horizontal (semantic)">
                    <div className="space-y-3 rounded-xl border border-line bg-white p-4">
                        <Text>Above the rule</Text>
                        <Divider decorative={false} />
                        <Text>Below the rule</Text>
                    </div>
                </LabPanel>

                <LabPanel title="Vertical (decorative)">
                    <div className="flex min-h-12 items-stretch gap-4 rounded-xl border border-line bg-white p-4">
                        <Text className="self-center">Jobs</Text>
                        <Divider orientation="vertical" />
                        <Text className="self-center">Candidates</Text>
                        <Divider orientation="vertical" />
                        <Text className="self-center">Reports</Text>
                    </div>
                </LabPanel>

                <LabPanel title="Vertical (semantic)">
                    <div className="flex min-h-12 items-stretch gap-4 rounded-xl border border-line bg-white p-4">
                        <span className="self-center text-sm text-ink">Left</span>
                        <Divider orientation="vertical" decorative={false} />
                        <span className="self-center text-sm text-ink">Right</span>
                    </div>
                </LabPanel>

                <LabPanel title="V2 evidence (read-only)">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>
                            Ad hoc <code className="text-ink">border-t border-line</code> on sections (drawers, profile
                            tabs, layouts)
                        </li>
                        <li>No reusable Divider component; no <code className="text-ink">&lt;hr&gt;</code> in quick scan</li>
                        <li>
                            <code className="text-ink">DropdownMenuSeparator</code> stays menu-specific (
                            <code className="text-ink">role=&quot;separator&quot;</code>, inset <code className="text-ink">h-px</code>)
                        </li>
                    </ul>
                </LabPanel>
            </div>

            <InfoPanel entry={dividerCatalog} />
        </div>
    );
}
