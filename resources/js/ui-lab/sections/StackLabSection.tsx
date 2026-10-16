import { Card, Container, Heading, Stack, Text } from '@/Components/atoms';
import FormField from '@/Components/molecules/FormField';
import Input from '@/Components/atoms/Input';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { stackCatalog } from '@/ui-lab/catalog';

const gaps = [
    { gap: 'xs' as const, label: 'xs — gap-1 (4px)' },
    { gap: 'sm' as const, label: 'sm — gap-2 (8px)' },
    { gap: 'md' as const, label: 'md — gap-4 (16px, default)' },
    { gap: 'lg' as const, label: 'lg — gap-6 (24px)' },
    { gap: 'xl' as const, label: 'xl — gap-8 (32px)' },
];

export default function StackLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Default Stack">
                    <Stack>
                        <Text>First item</Text>
                        <Text variant="secondary">Second item</Text>
                        <Text variant="caption">gap=&quot;md&quot; · align=&quot;stretch&quot;</Text>
                    </Stack>
                </LabPanel>

                <LabPanel title="Gap scale">
                    <Stack gap="md">
                        {gaps.map((item) => (
                            <Stack key={item.gap} gap={item.gap} className="rounded-md border border-line bg-surface/40 p-3">
                                <Text variant="caption">{item.label}</Text>
                                <div className="h-2 rounded bg-brand/30" />
                                <div className="h-2 rounded bg-brand/30" />
                            </Stack>
                        ))}
                    </Stack>
                </LabPanel>

                <LabPanel title="Align">
                    <div className="grid gap-4 md:grid-cols-2">
                        {(['stretch', 'start', 'center', 'end'] as const).map((align) => (
                            <Stack key={align} align={align} gap="sm" className="min-h-28 rounded-md border border-line bg-canvas p-3">
                                <Text variant="caption">align=&quot;{align}&quot;</Text>
                                <div className="w-24 rounded bg-brand/20 px-2 py-1 text-xs text-ink">Block</div>
                                <div className="w-16 rounded bg-brand/20 px-2 py-1 text-xs text-ink">Block</div>
                            </Stack>
                        ))}
                    </div>
                </LabPanel>

                <LabPanel title="Typography composition">
                    <Stack gap="sm">
                        <Heading level={2}>Section title</Heading>
                        <Text variant="secondary">Supporting copy with consistent vertical rhythm.</Text>
                    </Stack>
                </LabPanel>

                <LabPanel title="FormField composition">
                    <Stack gap="md">
                        <FormField id="stack-demo-name" label="Name">
                            <Input id="stack-demo-name" surface="admin" placeholder="Jane Doe" />
                        </FormField>
                        <FormField id="stack-demo-email" label="Email">
                            <Input id="stack-demo-email" surface="admin" type="email" placeholder="jane@example.com" />
                        </FormField>
                    </Stack>
                </LabPanel>

                <LabPanel title="Container + Card + Stack">
                    <Container size="sm">
                        <Card>
                            <Stack gap="sm">
                                <Heading level={3}>Grouped content</Heading>
                                <Text variant="secondary">Container width · Card surface · Stack spacing.</Text>
                            </Stack>
                        </Card>
                    </Container>
                </LabPanel>

                <LabPanel title="Spacing ownership">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>Stack owns sibling gap only (CSS gap)</li>
                        <li>No outer margin, padding, or width on Stack</li>
                        <li>Not a substitute for FieldGroup legend/semantics</li>
                    </ul>
                </LabPanel>

                <LabPanel title="V2 evidence (read-only)">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>
                            Ad hoc <code className="text-ink">space-y-*</code> in pages and UI Lab sections
                        </li>
                        <li>
                            <code className="text-ink">FieldGroup</code> vertical —{' '}
                            <code className="text-ink">flex flex-col</code> + gap sm/md/lg (form-specific)
                        </li>
                        <li>No standalone V2 Stack component</li>
                    </ul>
                </LabPanel>
            </div>

            <InfoPanel entry={stackCatalog} />
        </div>
    );
}
