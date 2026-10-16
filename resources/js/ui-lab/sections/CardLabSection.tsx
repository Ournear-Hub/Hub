import { Avatar, Badge, Card, Chip, Heading, Image, Link, LinkButton, Text } from '@/Components/atoms';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { cardCatalog } from '@/ui-lab/catalog';

const sampleImage =
    'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?auto=format&fit=crop&w=400&q=80';

export default function CardLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Basic Card">
                    <Card>
                        <Text>Default padding preset (standard).</Text>
                    </Card>
                </LabPanel>

                <LabPanel title="Padding presets">
                    <div className="grid gap-4 md:grid-cols-2">
                        <Card padding="none" className="p-4">
                            <Text variant="caption">padding=&quot;none&quot; + caller p-4</Text>
                        </Card>
                        <Card padding="compact">
                            <Text variant="caption">compact</Text>
                        </Card>
                        <Card padding="standard">
                            <Text variant="caption">standard (default)</Text>
                        </Card>
                        <Card padding="feature">
                            <Text variant="caption">feature</Text>
                        </Card>
                    </div>
                </LabPanel>

                <LabPanel title="Heading + text">
                    <Card>
                        <Heading level={3}>Section summary</Heading>
                        <Text variant="secondary" className="mt-2">
                            Supporting description inside a bounded surface.
                        </Text>
                    </Card>
                </LabPanel>

                <LabPanel title="With Badge and Chip">
                    <Card padding="compact">
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge variant="info">Updated</Badge>
                            <Chip>Remote</Chip>
                            <Chip variant="brand">Full-time</Chip>
                        </div>
                    </Card>
                </LabPanel>

                <LabPanel title="With Avatar and Image">
                    <Card>
                        <div className="flex gap-4">
                            <Avatar name="Alex Morgan" size="md" />
                            <Image src={sampleImage} alt="Landscape preview" className="h-16 w-24 rounded-md" />
                        </div>
                    </Card>
                </LabPanel>

                <LabPanel title="Clickable composition (caller-owned)">
                    <Card padding="compact" className="transition hover:border-brand/40">
                        <Heading level={4}>External resource</Heading>
                        <Text variant="secondary" className="mt-1">
                            Card is not interactive — wrap or place LinkButton inside.
                        </Text>
                        <LinkButton href="#" className="mt-3" size="sm">
                            View details
                        </LinkButton>
                    </Card>
                    <p className="mt-2 text-xs text-muted">
                        Text link alternative: <Link href="#">Learn more</Link>
                    </p>
                </LabPanel>

                <LabPanel title="Accessibility">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>Renders a non-semantic &lt;div&gt; — no automatic landmarks</li>
                        <li>Caller adds headings/regions when grouping needs AT structure</li>
                        <li>No href, onClick, or role=button on Card</li>
                    </ul>
                </LabPanel>

                <LabPanel title="API notes">
                    <pre className="overflow-x-auto rounded-lg bg-surface p-4 text-xs text-ink">
                        {`<Card
  padding="none" | "compact" | "standard" | "feature"
  className?
>
  {children}
</Card>`}
                    </pre>
                </LabPanel>

                <LabPanel title="Non-goals">
                    <p className="max-w-lg text-sm text-muted">
                        No JobCard, headers/footers subcomponents, selection, loading, or V2 Admin/ui/Card migration in
                        PR-4R.
                    </p>
                </LabPanel>

                <LabPanel title="V2 evidence (read-only)">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>
                            <code className="text-ink">design-system/classNames.cardDensity</code> — compact /
                            standard / feature
                        </li>
                        <li>
                            <code className="text-ink">Components/Admin/ui/Card.tsx</code> — ad hoc section surface
                            (unchanged)
                        </li>
                        <li>JobCard — business + interactive (not migrated)</li>
                    </ul>
                </LabPanel>
            </div>

            <InfoPanel entry={cardCatalog} />
        </div>
    );
}
