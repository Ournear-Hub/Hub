import { Card, Container, Heading, Text } from '@/Components/atoms';
import type { ContainerSize } from '@/Components/atoms/Container';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { containerCatalog } from '@/ui-lab/catalog';

function WidthDemo({ size, label }: { size: ContainerSize; label: string }) {
    return (
        <Container size={size} gutter="md" className="rounded-md border border-dashed border-line bg-surface/50 py-3">
            <Text variant="caption">{label}</Text>
        </Container>
    );
}

export default function ContainerLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Default Container">
                    <div className="rounded-md border border-line bg-canvas py-4">
                        <Container>
                            <div className="rounded-md bg-brand/10 px-3 py-2 text-sm text-ink">
                                size=&quot;md&quot; (max-w-6xl) · gutter=&quot;md&quot;
                            </div>
                        </Container>
                    </div>
                </LabPanel>

                <LabPanel title="Width scale">
                    <div className="space-y-3 rounded-md border border-line bg-canvas py-4">
                        <WidthDemo size="sm" label="sm — max-w-3xl (V2 narrow)" />
                        <WidthDemo size="md" label="md — max-w-6xl (V2 default / admin)" />
                        <WidthDemo size="lg" label="lg — max-w-7xl (V2 wide)" />
                        <WidthDemo size="xl" label="xl — max-w-screen-xl" />
                        <WidthDemo size="2xl" label="2xl — max-w-screen-2xl" />
                        <WidthDemo size="full" label="full — no max-width cap" />
                    </div>
                </LabPanel>

                <LabPanel title="Gutter behavior">
                    <div className="rounded-md border border-line bg-canvas py-2">
                        <Container gutter="none" className="bg-brand/5 py-2">
                            <Text variant="caption">gutter=&quot;none&quot; — shell-owned padding (admin pattern)</Text>
                        </Container>
                        <Container gutter="sm" className="mt-2 bg-brand/5 py-2">
                            <Text variant="caption">gutter=&quot;sm&quot; — px-4</Text>
                        </Container>
                        <Container gutter="md" className="mt-2 bg-brand/5 py-2">
                            <Text variant="caption">gutter=&quot;md&quot; — px-4 sm:px-6 lg:px-8 (V2 PageContainer)</Text>
                        </Container>
                    </div>
                </LabPanel>

                <LabPanel title="Container + typography">
                    <Container size="sm">
                        <Heading level={2}>Readable column</Heading>
                        <Text variant="secondary" className="mt-2">
                            Container bounds width; typography atoms supply meaning.
                        </Text>
                    </Container>
                </LabPanel>

                <LabPanel title="Container + Card">
                    <Container>
                        <Card>
                            <Heading level={3}>Card inside Container</Heading>
                            <Text variant="secondary" className="mt-2">
                                Container = width/gutters. Card = bounded visual surface.
                            </Text>
                        </Card>
                    </Container>
                </LabPanel>

                <LabPanel title="Layout semantics">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>Non-semantic &lt;div&gt; — no landmark roles</li>
                        <li>CSS-only responsive gutters (sm/md/lg breakpoints)</li>
                        <li>Not a grid, stack, or page shell</li>
                    </ul>
                </LabPanel>

                <LabPanel title="V2 evidence (read-only)">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>
                            <code className="text-ink">PageContainer</code> — max-w-6xl/3xl/7xl + px-4 sm:px-6 lg:px-8
                        </li>
                        <li>
                            <code className="text-ink">AdminContainer</code> — max-w-6xl only; main{' '}
                            <code className="text-ink">p-4 sm:p-6</code> owns gutters
                        </li>
                        <li>Single generic Container — no Public/Admin variants</li>
                    </ul>
                </LabPanel>
            </div>

            <InfoPanel entry={containerCatalog} />
        </div>
    );
}
