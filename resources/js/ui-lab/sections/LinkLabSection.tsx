import { Link, Text } from '@/Components/atoms';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { linkCatalog } from '@/ui-lab/catalog';

export default function LinkLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Variants">
                    <div className="flex flex-wrap gap-4">
                        <Link href="/admin/design-system" variant="default">
                            Default (brand)
                        </Link>
                        <Link href="/admin/design-system" variant="muted">
                            Muted
                        </Link>
                        <Link href="/admin/design-system" variant="subtle">
                            Subtle
                        </Link>
                    </div>
                </LabPanel>

                <LabPanel title="Underline">
                    <div className="flex flex-wrap gap-4">
                        <Link href="/admin/design-system" underline="none">
                            None
                        </Link>
                        <Link href="/admin/design-system" underline="hover">
                            Hover
                        </Link>
                        <Link href="/admin/design-system" underline="always">
                            Always
                        </Link>
                    </div>
                </LabPanel>

                <LabPanel title="External (caller-owned)">
                    <p className="mb-3 max-w-lg text-sm text-muted">
                        Canonical Link does not apply routing policy. Set <code className="text-ink">target</code> /{' '}
                        <code className="text-ink">rel</code> on the anchor when needed; use Inertia{' '}
                        <code className="text-ink">Link</code> at the page layer for SPA navigation.
                    </p>
                    <Link href="https://ournear.com" target="_blank" rel="noopener noreferrer">
                        External site
                    </Link>
                </LabPanel>

                <LabPanel title="Inline with text">
                    <Text>
                        Read the{' '}
                        <Link href="/admin/design-system/foundation" underline="always">
                            foundation tokens
                        </Link>{' '}
                        before composing layouts.
                    </Text>
                </LabPanel>

                <LabPanel title="V2 evidence (read-only)">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>
                            Inertia <code className="text-ink">Link</code> used directly with ad hoc classes (header
                            nav, profile, pages)
                        </li>
                        <li>
                            Button <code className="text-ink">variant=&quot;link&quot;</code>: brand + hover underline
                            — action styled as link, unchanged
                        </li>
                        <li>Nav active state on parent patterns (border/text), not a shared Link atom</li>
                        <li>No reusable canonical Link component in V2</li>
                    </ul>
                </LabPanel>
            </div>

            <InfoPanel entry={linkCatalog} />
        </div>
    );
}
