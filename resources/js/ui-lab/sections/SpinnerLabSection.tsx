import { Button, Spinner } from '@/Components/atoms';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { spinnerCatalog } from '@/ui-lab/catalog';

export default function SpinnerLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Sizes">
                    <div className="flex flex-wrap items-center gap-6 text-brand">
                        <Spinner size="sm" label="Loading small" />
                        <Spinner size="md" label="Loading medium" />
                        <Spinner size="lg" label="Loading large" />
                    </div>
                </LabPanel>

                <LabPanel title="Custom label">
                    <Spinner label="Saving application" className="text-accent" />
                </LabPanel>

                <LabPanel title="Panel / region loading">
                    <div
                        className="flex min-h-32 items-center justify-center rounded-xl border border-line bg-white"
                        aria-busy="true"
                    >
                        <Spinner label="Loading jobs" />
                    </div>
                </LabPanel>

                <LabPanel title="Distinct from Button loading">
                    <p className="mb-3 max-w-lg text-sm text-muted">
                        Canonical Button and IconButton still use <code className="text-ink">OurNearIcon</code>{' '}
                        <code className="text-ink">spinner</code> inline with <code className="text-ink">aria-busy</code>{' '}
                        on the control (V2-compatible pattern, unchanged). Use the Spinner atom for standalone regions and
                        async panels.
                    </p>
                    <Button type="button" surface="admin" loading>
                        Submitting
                    </Button>
                </LabPanel>

                <LabPanel title="Distinct from Skeleton">
                    <p className="max-w-lg text-sm text-muted">
                        Skeleton preserves layout for forthcoming content. Spinner signals an active operation without
                        implying final content shape.
                    </p>
                </LabPanel>
            </div>

            <InfoPanel entry={spinnerCatalog} />
        </div>
    );
}
