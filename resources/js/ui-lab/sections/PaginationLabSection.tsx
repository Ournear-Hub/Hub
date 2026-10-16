import Pagination from '@/Components/molecules/Pagination';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { paginationCatalog } from '@/ui-lab/catalog';

const sampleLinks = [
    { url: '/jobs?page=1', label: '&laquo; Previous', active: false },
    { url: '/jobs?page=1', label: '1', active: false },
    { url: '/jobs?page=2', label: '2', active: true },
    { url: '/jobs?page=3', label: '3', active: false },
    { url: null, label: '...', active: false },
    { url: '/jobs?page=10', label: '10', active: false },
    { url: '/jobs?page=3', label: 'Next &raquo;', active: false },
];

const disabledPrevLinks = [
    { url: null, label: '&laquo; Previous', active: false },
    { url: null, label: '1', active: true },
    { url: '/jobs?page=2', label: '2', active: false },
    { url: '/jobs?page=2', label: 'Next &raquo;', active: false },
];

export default function PaginationLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Multi-page (Laravel-shaped links)">
                    <Pagination links={sampleLinks} />
                </LabPanel>

                <LabPanel title="Disabled previous on first page">
                    <Pagination links={disabledPrevLinks} />
                </LabPanel>

                <LabPanel title="Hidden when ≤3 links">
                    <p className="mb-3 text-sm text-muted">
                        Single-page lists render <code className="text-ink">null</code> (V2 contract).
                    </p>
                    <Pagination
                        links={[
                            { url: null, label: '&laquo; Previous', active: false },
                            { url: null, label: '1', active: true },
                            { url: null, label: 'Next &raquo;', active: false },
                        ]}
                    />
                    <p className="mt-2 text-xs text-muted">Nothing rendered above this line.</p>
                </LabPanel>

                <LabPanel title="Link relationship">
                    <p className="text-sm text-muted">
                        Navigable items use canonical <code className="text-ink">Link</code> (native anchor). Current page is a{' '}
                        <code className="text-ink">span</code> with <code className="text-ink">aria-current=&quot;page&quot;</code>.
                        Disabled gaps use <code className="text-ink">url: null</code>.
                    </p>
                </LabPanel>

                <LabPanel title="V2 evidence (read-only)">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>
                            <code className="text-ink">Components/Pagination.tsx</code> — ~39 admin/account/public index pages
                        </li>
                        <li>Same <code className="text-ink">links</code> prop, nav landmark, pill styling; V2 uses Inertia Link (unchanged)</li>
                    </ul>
                </LabPanel>

                <LabPanel title="Non-goals">
                    <p className="text-sm text-muted">
                        No Laravel paginator types, Inertia, URL generation, fetch state, or compact/mobile-only variant. Caller supplies{' '}
                        <code className="text-ink">links</code>.
                    </p>
                </LabPanel>
            </div>

            <InfoPanel entry={paginationCatalog} />
        </div>
    );
}
