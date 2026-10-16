import { IconButton, VisuallyHidden } from '@/Components/atoms';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { visuallyHiddenCatalog } from '@/ui-lab/catalog';

export default function VisuallyHiddenLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Basic">
                    <p className="text-sm text-ink">
                        Save job
                        <VisuallyHidden> to your account</VisuallyHidden>
                    </p>
                    <p className="mt-2 text-xs text-muted">Text after the hidden span is visible; hidden part is for AT only.</p>
                </LabPanel>

                <LabPanel title="Icon-only control">
                    <IconButton icon="close" label="Dismiss panel" variant="ghost" />
                    <p className="mt-2 text-xs text-muted">
                        IconButton requires <code className="text-ink">label</code>; VisuallyHidden is for supplementary
                        context elsewhere.
                    </p>
                </LabPanel>

                <LabPanel title="Polymorphic as">
                    <VisuallyHidden as="p">Status: filters applied</VisuallyHidden>
                    <VisuallyHidden as="div" className="mt-0">
                        (div wrapper — same sr-only recipe)
                    </VisuallyHidden>
                </LabPanel>

                <LabPanel title="Not aria-hidden">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>VisuallyHidden keeps content in the accessibility tree</li>
                        <li>
                            Do not use for decorative icons — use <code className="text-ink">aria-hidden</code>
                        </li>
                        <li>Recipe: canonical <code className="text-ink">visuallyHidden</code> → Tailwind <code className="text-ink">sr-only</code></li>
                    </ul>
                </LabPanel>

                <LabPanel title="V2 evidence (read-only)">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>
                            Ad hoc <code className="text-ink">className=&quot;sr-only&quot;</code> on Label required text,
                            FieldGroup/FieldSet, Admin TopBar, forms
                        </li>
                        <li>No reusable VisuallyHidden component in V2</li>
                        <li>Canonical Label atom still uses inline sr-only — unchanged in PR-4L</li>
                    </ul>
                </LabPanel>
            </div>

            <InfoPanel entry={visuallyHiddenCatalog} />
        </div>
    );
}
