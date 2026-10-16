import { Badge, Chip } from '@/Components/atoms';
import OurNearIcon from '@/icons/OurNearIcon';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { chipCatalog } from '@/ui-lab/catalog';

export default function ChipLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Default">
                    <Chip>React</Chip>
                </LabPanel>

                <LabPanel title="Variants">
                    <div className="flex flex-wrap gap-2">
                        <Chip variant="neutral">Neutral</Chip>
                        <Chip variant="accent">Accent</Chip>
                        <Chip variant="brand">Brand</Chip>
                    </div>
                </LabPanel>

                <LabPanel title="Sizes">
                    <div className="flex flex-wrap items-center gap-2">
                        <Chip size="sm">Small</Chip>
                        <Chip size="md">Medium</Chip>
                    </div>
                </LabPanel>

                <LabPanel title="Icon + label">
                    <Chip icon={<OurNearIcon name="briefcase" size="xs" decorative />} variant="brand">
                        Remote
                    </Chip>
                </LabPanel>

                <LabPanel title="Removable">
                    <Chip onRemove={() => undefined} removeLabel="Remove TypeScript">
                        TypeScript
                    </Chip>
                </LabPanel>

                <LabPanel title="Multiple together">
                    <div className="flex max-w-md flex-wrap gap-1.5">
                        <Chip>JavaScript</Chip>
                        <Chip>Laravel</Chip>
                        <Chip>Inertia</Chip>
                        <Chip onRemove={() => undefined} removeLabel="Remove Tailwind">
                            Tailwind
                        </Chip>
                    </div>
                </LabPanel>

                <LabPanel title="Long content">
                    <Chip className="max-w-[10rem]">
                        Senior full-stack engineer with platform experience
                    </Chip>
                    <p className="mt-2 text-xs text-muted">Label truncates inside max width (chip recipe).</p>
                </LabPanel>

                <LabPanel title="Badge vs Chip">
                    <div className="space-y-3">
                        <div className="flex flex-wrap items-center gap-2">
                            <span className="text-xs font-medium text-muted">Badge (status):</span>
                            <Badge variant="success">Active</Badge>
                            <Badge variant="warning">Pending</Badge>
                        </div>
                        <div className="flex flex-wrap items-center gap-2">
                            <span className="text-xs font-medium text-muted">Chip (metadata):</span>
                            <Chip>Skill</Chip>
                            <Chip onRemove={() => undefined} removeLabel="Remove filter Remote">
                                Filter: Remote
                            </Chip>
                        </div>
                        <ul className="max-w-lg list-inside list-disc text-xs text-muted">
                            <li>Badge — rounded-full semantic status tones; non-removable</li>
                            <li>Chip — rounded-md metadata; optional remove control</li>
                        </ul>
                    </div>
                </LabPanel>

                <LabPanel title="Accessibility">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>Chip body is non-interactive text (no button role on wrapper)</li>
                        <li>Remove uses native button + caller <code className="text-ink">removeLabel</code></li>
                        <li>Leading icons should be decorative when redundant with visible text</li>
                    </ul>
                </LabPanel>

                <LabPanel title="Non-goals">
                    <p className="max-w-lg text-sm text-muted">
                        No selection state, form values, filtering, chip input, or navigation. MultiSelect owns compound
                        selection + hidden inputs (V2 unchanged).
                    </p>
                </LabPanel>

                <LabPanel title="V2 evidence (read-only)">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>
                            <code className="text-ink">MultiSelect</code> — inline{' '}
                            <code className="text-ink">multiSelectChip*</code> with remove button (rounded-md surface)
                        </li>
                        <li>
                            <code className="text-ink">Badge</code> atom — rounded-full{' '}
                            <code className="text-ink">statusTone</code> for state labels
                        </li>
                        <li>No standalone V2 Chip/Tag component — patterns are ad hoc</li>
                    </ul>
                </LabPanel>
            </div>

            <InfoPanel entry={chipCatalog} />
        </div>
    );
}
