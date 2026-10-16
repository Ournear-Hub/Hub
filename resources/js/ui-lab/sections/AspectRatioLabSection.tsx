import { AspectRatio, Card, Image, Text } from '@/Components/atoms';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { aspectRatioCatalog } from '@/ui-lab/catalog';

const sampleSrc =
    'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?auto=format&fit=crop&w=800&q=80';

const presets = [
    { ratio: 'square' as const, label: 'square — 1 / 1 (Tailwind aspect-square)' },
    { ratio: 'video' as const, label: 'video — 16 / 9 (Tailwind aspect-video)' },
    { ratio: 'portrait' as const, label: 'portrait — 4 / 5 (V2 About founder)' },
];

export default function AspectRatioLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Ratio presets">
                    <div className="grid max-w-md gap-4">
                        {presets.map((item) => (
                            <div key={item.ratio}>
                                <Text variant="caption">{item.label}</Text>
                                <AspectRatio ratio={item.ratio} className="mt-1 overflow-hidden rounded-md border border-line bg-surface">
                                    <div className="flex h-full w-full items-center justify-center text-xs text-muted">
                                        {item.ratio}
                                    </div>
                                </AspectRatio>
                            </div>
                        ))}
                    </div>
                </LabPanel>

                <LabPanel title="Image inside AspectRatio">
                    <AspectRatio ratio="video" className="max-w-sm overflow-hidden rounded-md">
                        <Image src={sampleSrc} alt="Landscape in 16:9 box" className="h-full w-full" fit="cover" />
                    </AspectRatio>
                    <p className="mt-2 text-xs text-muted">
                        AspectRatio sets the box; Image owns src, alt, and object-fit. Caller adds overflow/radius via{' '}
                        <code className="text-ink">className</code>.
                    </p>
                </LabPanel>

                <LabPanel title="Plain content">
                    <AspectRatio ratio="square" className="max-w-xs rounded-md border border-dashed border-line bg-surface/50">
                        <div className="flex h-full items-center justify-center p-4 text-center text-sm text-muted">
                            Non-image children — layout box only
                        </div>
                    </AspectRatio>
                </LabPanel>

                <LabPanel title="Card + AspectRatio">
                    <Card className="max-w-sm overflow-hidden">
                        <AspectRatio ratio="video">
                            <Image src={sampleSrc} alt="Card media" className="h-full w-full overflow-hidden" fit="cover" />
                        </AspectRatio>
                        <div className="p-4">
                            <Text>Card body unchanged — ratio is not Card&apos;s job.</Text>
                        </div>
                    </Card>
                </LabPanel>

                <LabPanel title="Overflow ownership">
                    <p className="mb-2 text-xs text-muted">
                        AspectRatio does not set overflow by default. Left: no overflow. Right: caller{' '}
                        <code className="text-ink">overflow-hidden</code>.
                    </p>
                    <div className="grid max-w-lg gap-4 sm:grid-cols-2">
                        <AspectRatio ratio="square" className="rounded-md border border-line">
                            <Image src={sampleSrc} alt="No overflow clip" className="h-full w-full" fit="cover" />
                        </AspectRatio>
                        <AspectRatio ratio="square" className="overflow-hidden rounded-md border border-line">
                            <Image src={sampleSrc} alt="With overflow clip" className="h-full w-full" fit="cover" />
                        </AspectRatio>
                    </div>
                </LabPanel>

                <LabPanel title="Responsive behavior">
                    <p className="text-sm text-muted">
                        Ratio is CSS-only (<code className="text-ink">aspect-*</code>). Width follows parent; height is
                        derived. No breakpoint props.
                    </p>
                    <AspectRatio ratio="video" className="overflow-hidden rounded-md">
                        <Image src={sampleSrc} alt="Full width responsive box" className="h-full w-full" fit="cover" />
                    </AspectRatio>
                </LabPanel>

                <LabPanel title="Avatar boundary">
                    <p className="text-sm text-muted">
                        Avatar owns identity sizing (xs–xl). Do not replace Avatar with AspectRatio for profile chips.
                    </p>
                </LabPanel>

                <LabPanel title="V2 evidence (read-only)">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>
                            <code className="text-ink">aspect-[4/5]</code> — About founder image/placeholder
                        </li>
                        <li>Logos/thumbs — fixed <code className="text-ink">h-* w-*</code> squares (JobCard, etc.)</li>
                        <li>Banners — fixed <code className="text-ink">h-48/h-64 w-full object-cover</code> (About hero)</li>
                        <li>No V2 AspectRatio component; Image lab used fixed-height boxes</li>
                    </ul>
                </LabPanel>

                <LabPanel title="Non-goals">
                    <p className="max-w-lg text-sm text-muted">
                        No object-fit, media src, fallback, caption, background, border, radius, or semantic roles on
                        AspectRatio. No numeric ratio API or wide (21:9) preset without evidence.
                    </p>
                </LabPanel>
            </div>

            <InfoPanel entry={aspectRatioCatalog} />
        </div>
    );
}
