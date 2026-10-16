import { Progress } from '@/Components/atoms';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { progressCatalog } from '@/ui-lab/catalog';

export default function ProgressLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Sizes">
                    <div className="max-w-md space-y-4">
                        <Progress size="sm" value={30} label="Upload" showValue />
                        <Progress size="md" value={55} label="Import" showValue />
                        <Progress size="lg" value={80} label="Onboarding" showValue />
                    </div>
                </LabPanel>

                <LabPanel title="Value only">
                    <Progress value={45} className="max-w-xs" />
                </LabPanel>

                <LabPanel title="Custom max">
                    <Progress value={3} max={5} label="Steps completed" showValue />
                </LabPanel>

                <LabPanel title="Profile-style (V2 vocabulary)">
                    <p className="mb-3 max-w-lg text-sm text-muted">
                        Matches read-only evidence from{' '}
                        <code className="text-ink">ProfileCompletionCenter</code>: surface track, brand fill,{' '}
                        <code className="text-ink">role=&quot;progressbar&quot;</code> — V2 file not modified.
                    </p>
                    <Progress value={72} label="Profile completion" showValue />
                </LabPanel>

                <LabPanel title="Distinct from Spinner / Skeleton">
                    <p className="max-w-lg text-sm text-muted">
                        Spinner = indeterminate operation. Skeleton = forthcoming content shape. Progress = measurable
                        completion between min and max.
                    </p>
                </LabPanel>
            </div>

            <InfoPanel entry={progressCatalog} />
        </div>
    );
}
