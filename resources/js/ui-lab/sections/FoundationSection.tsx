import { color, radius, shadow } from '@/foundation/tokens';
import { focusRing, labelBase, statusTone, typography } from '@/design-system/classNames';
import LabPanel from '@/ui-lab/components/LabPanel';
import { foundationStateVocabulary, spacingScalePx } from '@/ui-lab/metadata';
import TypographyLabSection from '@/ui-lab/sections/TypographyLabSection';

function Swatch({ name, hex, className }: { name: string; hex: string; className?: string }) {
    return (
        <div className="rounded-lg border border-line bg-white p-3">
            <div className={`h-14 rounded-md border border-line ${className ?? ''}`} style={className ? undefined : { backgroundColor: hex }} />
            <p className="mt-2 text-sm font-semibold text-ink">{name}</p>
            <p className="font-mono text-xs text-muted">{hex}</p>
        </div>
    );
}

const semanticColors = [
    { name: 'canvas', className: 'bg-canvas' },
    { name: 'surface', className: 'bg-surface' },
    { name: 'line', className: 'bg-line' },
    { name: 'ink', className: 'bg-ink' },
    { name: 'muted', className: 'bg-muted' },
    { name: 'disabled', className: 'bg-disabled' },
    { name: 'success', className: 'bg-success' },
    { name: 'warning', className: 'bg-warning' },
    { name: 'danger', className: 'bg-danger' },
    { name: 'information (brand)', className: 'bg-brand' },
] as const;

export default function FoundationSection() {
    return (
        <div className="space-y-6">
            <LabPanel title="Brand" description="Primitive brand colors from foundation/tokens.ts and app.css @theme.">
                <div className="grid gap-4 sm:grid-cols-3">
                    <Swatch name="Primary (brand)" hex={color.brand.toUpperCase()} />
                    <Swatch name="Accent" hex={color.accent.toUpperCase()} />
                    <Swatch name="White" hex={color.white.toUpperCase()} className="bg-white" />
                </div>
            </LabPanel>

            <LabPanel title="Semantic colors" description="Tailwind theme tokens — use utility classes in product UI.">
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {semanticColors.map((item) => (
                        <Swatch key={item.name} name={item.name} hex="—" className={item.className} />
                    ))}
                </div>
            </LabPanel>

            <LabPanel title="Typography (token preview)" description="Raw classNames.typography — canonical atoms are demonstrated below.">
                <div className="space-y-4">
                    <p className={typography.display}>Display — OurNear corporate headline</p>
                    <p className={typography.pageTitle}>Heading — page title scale</p>
                    <p className={typography.sectionTitle}>Section title</p>
                    <p className={typography.body}>Body — primary reading text at 14px.</p>
                    <p className={labelBase}>Label — form label style</p>
                    <p className={typography.caption}>Caption — secondary metadata</p>
                </div>
            </LabPanel>

            <LabPanel title="Spacing" description="Common Tailwind spacing steps used in OurNear layouts (px).">
                <ul className="space-y-3">
                    {spacingScalePx.map((step) => (
                        <li key={step.token} className="flex items-center gap-4">
                            <span className="w-16 font-mono text-xs text-muted">{step.token}</span>
                            <span className="w-12 text-sm text-ink">{step.px}px</span>
                            <div className="h-3 rounded bg-brand/80" style={{ width: step.px }} />
                        </li>
                    ))}
                </ul>
            </LabPanel>

            <LabPanel title="Radius, borders, shadows" description="From foundation/tokens.ts — mirrored in @theme.">
                <div className="grid gap-4 md:grid-cols-2">
                    <div>
                        <h4 className="text-sm font-semibold text-ink">Radius</h4>
                        <ul className="mt-2 space-y-2 text-sm text-muted">
                            {Object.entries(radius).map(([key, value]) => (
                                <li key={key}>
                                    {key}: {value}
                                </li>
                            ))}
                        </ul>
                        <div className="mt-4 flex flex-wrap gap-3">
                            {Object.entries(radius).map(([key, value]) => (
                                <div
                                    key={key}
                                    className="flex h-16 w-16 items-center justify-center border border-line bg-surface text-xs font-semibold text-ink"
                                    style={{ borderRadius: value }}
                                >
                                    {key}
                                </div>
                            ))}
                        </div>
                    </div>
                    <div>
                        <h4 className="text-sm font-semibold text-ink">Shadows & focus</h4>
                        <ul className="mt-2 space-y-2 text-sm text-muted">
                            {Object.entries(shadow).map(([key, value]) => (
                                <li key={key}>
                                    {key}: {value}
                                </li>
                            ))}
                        </ul>
                        <div className="mt-4 space-y-3">
                            {Object.entries(shadow).map(([key, value]) => (
                                <div key={key} className="rounded-lg border border-line bg-white p-4" style={{ boxShadow: value }}>
                                    shadow-{key}
                                </div>
                            ))}
                            <button type="button" className={`rounded-md border border-line bg-white px-4 py-2 text-sm font-semibold text-ink ${focusRing}`}>
                                Focus ring sample (tab to focus)
                            </button>
                        </div>
                    </div>
                </div>
            </LabPanel>

            <LabPanel title="Status tones" description="Semantic badge/alert presentation tokens (statusTone).">
                <div className="flex flex-wrap gap-2">
                    {Object.entries(statusTone).map(([key, classes]) => (
                        <span key={key} className={`rounded-full px-3 py-1 text-xs font-semibold ${classes}`}>
                            {key}
                        </span>
                    ))}
                </div>
            </LabPanel>

            <LabPanel title="States" description="Foundation vocabulary — not every state has a dedicated component yet.">
                <ul className="grid gap-2 sm:grid-cols-2">
                    {foundationStateVocabulary.map((state) => (
                        <li key={state} className="rounded-md border border-line bg-surface/40 px-3 py-2 text-sm text-ink">
                            {state}
                        </li>
                    ))}
                </ul>
            </LabPanel>

            <TypographyLabSection />
        </div>
    );
}
