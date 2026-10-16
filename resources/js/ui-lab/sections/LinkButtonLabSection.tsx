import { Button, Link, LinkButton } from '@/Components/atoms';
import type { ButtonSurface, ButtonVariant } from '@/Components/atoms/Button';
import OurNearIcon from '@/icons/OurNearIcon';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { linkButtonCatalog } from '@/ui-lab/catalog';
import { buttonIconSize } from '@/design-system/buttonVisuals';

const publicVariants: ButtonVariant[] = ['primary', 'secondary', 'outline', 'ghost', 'destructive'];
const adminVariants: ButtonVariant[] = ['primary', 'secondary', 'outline', 'ghost', 'destructive'];

function VariantRow({ surface, label }: { surface: ButtonSurface; label: string }) {
    const variants = surface === 'public' || surface === 'account' ? publicVariants : adminVariants;

    return (
        <LabPanel title={`Variants (${label})`}>
            <div className="flex flex-wrap gap-2">
                {variants.map((variant) => (
                    <LinkButton key={variant} href="#" surface={surface} variant={variant}>
                        {variant}
                    </LinkButton>
                ))}
            </div>
        </LabPanel>
    );
}

export default function LinkButtonLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Primary navigation CTA">
                    <LinkButton href="/jobs" surface="public" variant="primary">
                        View Jobs
                    </LinkButton>
                </LabPanel>

                <VariantRow surface="public" label="public / account" />
                <VariantRow surface="admin" label="admin / corporate" />

                <LabPanel title="Sizes">
                    <div className="flex flex-wrap items-center gap-2">
                        <LinkButton href="#" size="sm">
                            Small
                        </LinkButton>
                        <LinkButton href="#" size="md">
                            Medium
                        </LinkButton>
                        <LinkButton href="#" size="lg">
                            Large
                        </LinkButton>
                    </div>
                </LabPanel>

                <LabPanel title="Leading / trailing icon">
                    <div className="flex flex-wrap gap-2">
                        <LinkButton
                            href="#"
                            leadingIcon={<OurNearIcon name="briefcase" size={buttonIconSize.md} decorative />}
                        >
                            View details
                        </LinkButton>
                        <LinkButton
                            href="#"
                            trailingIcon={<OurNearIcon name="arrow-right" size={buttonIconSize.md} decorative />}
                        >
                            Learn more
                        </LinkButton>
                    </div>
                </LabPanel>

                <LabPanel title="Full width">
                    <LinkButton href="#" fullWidth>
                        Go to dashboard
                    </LinkButton>
                </LabPanel>

                <LabPanel title="Disabled">
                    <LinkButton href="/account" disabled>
                        Unavailable route
                    </LinkButton>
                </LabPanel>

                <LabPanel title="Native href / target / rel">
                    <LinkButton href="https://example.com" target="_blank" rel="noopener noreferrer">
                        Visit site
                    </LinkButton>
                </LabPanel>

                <LabPanel title="Attribute forwarding">
                    <LinkButton href="#" id="lab-link-button" data-testid="link-button-demo" title="Demo title">
                        With id + data-* + title
                    </LinkButton>
                </LabPanel>

                <LabPanel title="Link vs LinkButton vs Button">
                    <div className="flex flex-col gap-3">
                        <Link href="/jobs">Text link (Link atom)</Link>
                        <LinkButton href="/jobs">Button-styled navigation (LinkButton)</LinkButton>
                        <Button type="button" onClick={() => undefined}>
                            Action (Button)
                        </Button>
                    </div>
                    <ul className="mt-3 max-w-lg list-inside list-disc text-xs text-muted">
                        <li>Link — inline anchor typography</li>
                        <li>LinkButton — native &lt;a&gt;, Button visuals, no Inertia</li>
                        <li>Button — &lt;button&gt; / form actions (frozen href+Inertia on Button unchanged)</li>
                    </ul>
                </LabPanel>

                <LabPanel title="Accessibility">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>Always native &lt;a&gt; — no role=&quot;button&quot;</li>
                        <li>Disabled: aria-disabled, tabIndex -1, click prevented</li>
                        <li>Visible label text carries meaning; no auto aria-label</li>
                    </ul>
                </LabPanel>

                <LabPanel title="Non-goals">
                    <p className="max-w-lg text-sm text-muted">
                        No Inertia, no URL heuristics, no automatic target/rel, no form submit semantics, no V2 CTA
                        migration in PR-4Q.
                    </p>
                </LabPanel>

                <LabPanel title="V2 evidence (read-only)">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>
                            CtaSection and similar use <code className="text-ink">Button href=…</code> (Inertia for
                            internal paths)
                        </li>
                        <li>Ad hoc <code className="text-ink">&lt;a className=&quot;…button…&quot;&gt;</code> in headers/CTAs</li>
                        <li>LinkButton = canonical native-anchor alternative for design-system phase</li>
                    </ul>
                </LabPanel>
            </div>

            <InfoPanel entry={linkButtonCatalog} />
        </div>
    );
}
