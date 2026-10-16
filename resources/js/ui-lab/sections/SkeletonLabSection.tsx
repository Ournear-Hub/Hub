import { Skeleton } from '@/Components/atoms';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { skeletonCatalog } from '@/ui-lab/catalog';

function CardSkeletonDemo() {
    return (
        <div className="max-w-sm space-y-3 rounded-xl border border-line bg-white p-5">
            <Skeleton variant="rect" height={140} />
            <Skeleton variant="text" width="75%" />
            <Skeleton variant="text" width="50%" />
            <div className="flex gap-2 pt-1">
                <Skeleton variant="rect" width={88} height={36} />
                <Skeleton variant="rect" width={88} height={36} />
            </div>
        </div>
    );
}

function TableRowSkeletonDemo() {
    return (
        <div className="overflow-hidden rounded-xl border border-line bg-white">
            <div className="flex gap-4 border-b border-line bg-surface px-4 py-3">
                <Skeleton variant="text" width="20%" />
                <Skeleton variant="text" width="30%" />
                <Skeleton variant="text" width="25%" />
            </div>
            {[0, 1, 2].map((row) => (
                <div key={row} className="flex items-center gap-4 border-b border-line px-4 py-3 last:border-0">
                    <Skeleton variant="circle" width={32} height={32} />
                    <Skeleton variant="text" className="flex-1" />
                    <Skeleton variant="text" width="15%" />
                    <Skeleton variant="text" width="12%" />
                </div>
            ))}
        </div>
    );
}

export default function SkeletonLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Variants">
                    <div className="max-w-md space-y-4">
                        <div>
                            <p className="mb-2 text-xs font-medium text-muted">text</p>
                            <Skeleton variant="text" />
                        </div>
                        <div>
                            <p className="mb-2 text-xs font-medium text-muted">rect</p>
                            <Skeleton variant="rect" height={80} />
                        </div>
                        <div>
                            <p className="mb-2 text-xs font-medium text-muted">circle</p>
                            <Skeleton variant="circle" />
                        </div>
                    </div>
                </LabPanel>

                <LabPanel title="Custom width / height">
                    <div className="flex flex-wrap items-end gap-4">
                        <Skeleton variant="text" width={240} />
                        <Skeleton variant="rect" width={120} height={48} />
                        <Skeleton variant="circle" width={48} height={48} />
                    </div>
                </LabPanel>

                <LabPanel title="Card loading composition">
                    <CardSkeletonDemo />
                </LabPanel>

                <LabPanel title="Table row composition">
                    <TableRowSkeletonDemo />
                </LabPanel>

                <LabPanel title="Accessibility pattern">
                    <p className="mb-3 max-w-lg text-sm text-muted">
                        Skeleton shapes are decorative (<code className="text-ink">aria-hidden</code>). Wrap loading
                        regions with <code className="text-ink">aria-busy=&quot;true&quot;</code> and visible or
                        screen-reader-only loading text on the parent — not on each Skeleton.
                    </p>
                    <div aria-busy="true" className="max-w-sm space-y-2 rounded-lg border border-line p-4">
                        <span className="sr-only">Loading profile</span>
                        <Skeleton variant="text" />
                        <Skeleton variant="text" width="60%" />
                    </div>
                </LabPanel>
            </div>

            <InfoPanel entry={skeletonCatalog} />
        </div>
    );
}
