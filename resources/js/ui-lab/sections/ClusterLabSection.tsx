import {
    Avatar,
    Button,
    Card,
    Chip,
    Cluster,
    Container,
    LinkButton,
    Stack,
    Text,
} from '@/Components/atoms';
import OurNearIcon from '@/icons/OurNearIcon';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { clusterCatalog } from '@/ui-lab/catalog';

const gaps = [
    { gap: 'xs' as const, label: 'xs — gap-1 (4px)' },
    { gap: 'sm' as const, label: 'sm — gap-2 (8px, default)' },
    { gap: 'md' as const, label: 'md — gap-4 (16px)' },
    { gap: 'lg' as const, label: 'lg — gap-6 (24px)' },
    { gap: 'xl' as const, label: 'xl — gap-8 (32px)' },
];

export default function ClusterLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Default Cluster">
                    <Cluster>
                        <Text>Label</Text>
                        <Text variant="secondary">Value</Text>
                    </Cluster>
                </LabPanel>

                <LabPanel title="Gap scale">
                    <Stack gap="md">
                        {gaps.map((item) => (
                            <Cluster key={item.gap} gap={item.gap} className="rounded-md border border-line bg-surface/40 p-3">
                                <Text variant="caption">{item.label}</Text>
                                <div className="h-6 w-10 rounded bg-brand/30" />
                                <div className="h-6 w-10 rounded bg-brand/30" />
                            </Cluster>
                        ))}
                    </Stack>
                </LabPanel>

                <LabPanel title="Align">
                    <Stack gap="sm">
                        {(['start', 'center', 'end', 'baseline', 'stretch'] as const).map((align) => (
                            <Cluster
                                key={align}
                                align={align}
                                gap="sm"
                                className="min-h-14 rounded-md border border-line bg-canvas p-3"
                            >
                                <Text variant="caption">align=&quot;{align}&quot;</Text>
                                <div className="rounded bg-brand/20 px-2 py-3 text-xs text-ink">Tall</div>
                                <div className="rounded bg-brand/20 px-2 py-1 text-xs text-ink">Short</div>
                            </Cluster>
                        ))}
                    </Stack>
                </LabPanel>

                <LabPanel title="Icon + text">
                    <Cluster gap="sm">
                        <OurNearIcon name="message" size="sm" />
                        <Text>contact@ournear.test</Text>
                    </Cluster>
                </LabPanel>

                <LabPanel title="Avatar + text">
                    <Cluster gap="sm">
                        <Avatar name="Jane Doe" size="sm" />
                        <Stack gap="xs">
                            <Text>Jane Doe</Text>
                            <Text variant="caption">Recruiter</Text>
                        </Stack>
                    </Cluster>
                </LabPanel>

                <LabPanel title="Button / LinkButton group">
                    <Cluster gap="sm">
                        <Button surface="admin" variant="secondary" size="sm">
                            Cancel
                        </Button>
                        <Button surface="admin" size="sm">
                            Save
                        </Button>
                        <LinkButton href="#cluster-demo" surface="admin" variant="link" size="sm">
                            Learn more
                        </LinkButton>
                    </Cluster>
                </LabPanel>

                <LabPanel title="Chip collection (wrap)">
                    <Cluster gap="sm" wrap className="max-w-xs">
                        <Chip>React</Chip>
                        <Chip variant="accent">TypeScript</Chip>
                        <Chip variant="brand">Laravel</Chip>
                        <Chip onRemove={() => undefined} removeLabel="Remove Removable">
                            Removable
                        </Chip>
                    </Cluster>
                </LabPanel>

                <LabPanel title="Container + Cluster">
                    <Container size="sm">
                        <Cluster gap="md">
                            <Text variant="caption">Metadata</Text>
                            <Text>Updated today</Text>
                        </Cluster>
                    </Container>
                </LabPanel>

                <LabPanel title="Stack + Cluster">
                    <Stack gap="sm">
                        <Cluster gap="sm">
                            <OurNearIcon name="clipboard" size="sm" />
                            <Text variant="secondary">Oct 8, 2026</Text>
                        </Cluster>
                        <Cluster gap="sm">
                            <OurNearIcon name="briefcase" size="sm" />
                            <Text variant="secondary">Remote</Text>
                        </Cluster>
                    </Stack>
                </LabPanel>

                <LabPanel title="Semantics & spacing ownership">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>Non-semantic div — no toolbar/list/group roles</li>
                        <li>Cluster owns horizontal sibling gap only</li>
                        <li>Button groups compose Cluster + Button; no ButtonGroup atom</li>
                    </ul>
                </LabPanel>

                <LabPanel title="V2 evidence (read-only)">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>
                            Ad hoc <code className="text-ink">flex items-center gap-*</code> and{' '}
                            <code className="text-ink">flex flex-wrap gap-2</code> (e.g. Chip lab, MultiSelect control)
                        </li>
                        <li>
                            <code className="text-ink">inputGroupRoot</code> — horizontal join layout (distinct)
                        </li>
                        <li>No canonical V2 Row/Inline/Cluster component</li>
                    </ul>
                </LabPanel>

                <LabPanel title="Non-goals">
                    <Card padding="compact">
                        <Text variant="caption">
                            No justify, direction, grow/shrink, responsive props, or semantic toolbar injection.
                        </Text>
                    </Card>
                </LabPanel>
            </div>

            <InfoPanel entry={clusterCatalog} />
        </div>
    );
}
