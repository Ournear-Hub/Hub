import { Heading, Label, Text } from '@/Components/atoms';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { headingCatalog, labelCatalog, textCatalog } from '@/ui-lab/catalog';

export default function TypographyLabSection() {
    return (
        <div id="typography-atoms" className="scroll-mt-6 space-y-6">
            <div className="rounded-xl border border-brand/20 bg-brand/5 p-4">
                <h2 className="text-lg font-semibold text-ink">Typography atoms</h2>
                <p className="mt-1 text-sm text-muted">
                    Canonical Text, Heading, and Label from <code className="text-xs">@/Components/atoms</code>.
                </p>
            </div>

            <div className="grid gap-6 lg:grid-cols-[1fr_280px]">
                <div className="space-y-6">
                    <LabPanel title="Text" description="Variants map to classNames.typography; tones use semantic text colors.">
                        <div className="space-y-3">
                            <Text>Default body text for paragraphs and general copy.</Text>
                            <Text variant="secondary">Secondary / muted body text.</Text>
                            <Text variant="caption">Caption scale for metadata.</Text>
                            <Text variant="eyebrow">Eyebrow label</Text>
                            <Text tone="danger">Danger tone</Text>
                            <Text tone="success">Success tone</Text>
                            <Text tone="warning">Warning tone</Text>
                            <Text tone="disabled">Disabled tone</Text>
                            <div className="flex flex-wrap gap-4 pt-2">
                                <Text as="span" className="rounded-md border border-line px-2 py-1">
                                    Inline span
                                </Text>
                                <Text as="div" className="rounded-md border border-line px-2 py-1">
                                    Block div
                                </Text>
                            </div>
                        </div>
                    </LabPanel>

                    <LabPanel title="Heading" description="Semantic h1–h6 with existing typography scale classes.">
                        <div className="space-y-3">
                            <Heading level={1}>Heading level 1 — page title</Heading>
                            <Heading level={2}>Heading level 2 — section</Heading>
                            <Heading level={3}>Heading level 3 — card title</Heading>
                            <Heading level={4}>Heading level 4</Heading>
                            <Heading level={5}>Heading level 5</Heading>
                            <Heading level={6}>Heading level 6</Heading>
                        </div>
                    </LabPanel>

                    <LabPanel title="Label" description="Form labels use labelBase from the design system.">
                        <div className="max-w-md space-y-4">
                            <div>
                                <Label htmlFor="ui-lab-email">Email address</Label>
                                <input
                                    id="ui-lab-email"
                                    type="text"
                                    readOnly
                                    placeholder="Preview only"
                                    className="mt-1 w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink"
                                />
                            </div>
                            <div>
                                <Label htmlFor="ui-lab-name" required>
                                    Full name
                                </Label>
                                <input
                                    id="ui-lab-name"
                                    type="text"
                                    readOnly
                                    className="mt-1 w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink"
                                />
                            </div>
                        </div>
                    </LabPanel>
                </div>

                <div className="space-y-6">
                    <InfoPanel entry={textCatalog} />
                    <InfoPanel entry={headingCatalog} />
                    <InfoPanel entry={labelCatalog} />
                </div>
            </div>
        </div>
    );
}
