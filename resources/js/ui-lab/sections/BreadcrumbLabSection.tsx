import Breadcrumb from '@/Components/molecules/Breadcrumb';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { breadcrumbCatalog } from '@/ui-lab/catalog';

export default function BreadcrumbLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Multi-level">
                    <Breadcrumb
                        items={[
                            { label: 'Admin', href: '/admin' },
                            { label: 'Users', href: '/admin/users' },
                            { label: 'Jane Doe' },
                        ]}
                    />
                </LabPanel>

                <LabPanel title="Single current page">
                    <Breadcrumb items={[{ label: 'Dashboard' }]} />
                </LabPanel>

                <LabPanel title="Accessible label">
                    <Breadcrumb
                        ariaLabel="You are here"
                        items={[
                            { label: 'Account', href: '/account' },
                            { label: 'Settings' },
                        ]}
                    />
                </LabPanel>

                <LabPanel title="Separator">
                    <p className="mb-3 text-xs text-muted">
                        Decorative <code className="text-ink">/</code> between items (V2 contract) — not a focus stop.
                    </p>
                    <Breadcrumb
                        items={[
                            { label: 'Home', href: '/' },
                            { label: 'Jobs', href: '/jobs' },
                            { label: 'Apply' },
                        ]}
                    />
                </LabPanel>

                <LabPanel title="Link relationship">
                    <p className="text-sm text-muted">
                        Ancestors use canonical <code className="text-ink">Link</code> (native anchor). Current page is a{' '}
                        <code className="text-ink">span</code> with <code className="text-ink">aria-current=&quot;page&quot;</code>.
                    </p>
                </LabPanel>

                <LabPanel title="V2 evidence (read-only)">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>
                            <code className="text-ink">Components/Navigation/Breadcrumbs.tsx</code> — Admin + Account layouts
                        </li>
                        <li>Same nav/ol/separator/current semantics; V2 uses Inertia Link (unchanged)</li>
                    </ul>
                </LabPanel>

                <LabPanel title="Non-goals">
                    <p className="text-sm text-muted">
                        No route generation, URL detection, page title, or shell layout. Caller supplies{' '}
                        <code className="text-ink">items</code>.
                    </p>
                </LabPanel>
            </div>

            <InfoPanel entry={breadcrumbCatalog} />
        </div>
    );
}
