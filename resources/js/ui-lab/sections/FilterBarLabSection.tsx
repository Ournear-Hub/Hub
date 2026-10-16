import Button from '@/Components/atoms/Button';
import Input from '@/Components/atoms/Input';
import FilterBar from '@/Components/molecules/FilterBar';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { filterBarCatalog } from '@/ui-lab/catalog';
import type { FormEvent } from 'react';
import { useState } from 'react';

export default function FilterBarLabSection() {
    const [lastSubmit, setLastSubmit] = useState<string>('—');

    function handleDemoSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const data = new FormData(event.currentTarget);
        const search = String(data.get('search') ?? '');
        setLastSubmit(search ? `search=${search}` : '(empty search field)');
    }

    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Default grid (md:grid-cols-4)">
                    <FilterBar onSubmit={handleDemoSubmit}>
                        <Input name="search" type="search" surface="admin" placeholder="Search…" aria-label="Search" />
                        <div className="flex flex-wrap gap-2 md:col-span-2">
                            <Button type="submit" surface="admin" size="sm">
                                Apply
                            </Button>
                            <Button type="button" surface="admin" variant="secondary" size="sm">
                                Reset
                            </Button>
                        </div>
                    </FilterBar>
                    <p className="text-xs text-muted">Last submit (lab only): {lastSubmit}</p>
                </LabPanel>

                <LabPanel title="Caller className override (md:grid-cols-6)">
                    <FilterBar onSubmit={handleDemoSubmit} className="md:grid-cols-6">
                        <Input name="search" type="search" surface="admin" placeholder="Keyword" aria-label="Keyword" />
                        <Input name="code" surface="admin" placeholder="Code" aria-label="Code" />
                        <div className="flex flex-wrap gap-2 md:col-span-2">
                            <Button type="submit" surface="admin" size="sm">
                                Filter
                            </Button>
                        </div>
                    </FilterBar>
                </LabPanel>

                <LabPanel title="Non-goals">
                    <p className="text-sm text-muted">
                        No query props, reset handlers, filter chips, or Inertia integration. JobsSearchPanel and account
                        employer searches remain separate product components.
                    </p>
                </LabPanel>

                <LabPanel title="Production import lanes">
                    <p className="text-sm text-muted">
                        <strong className="font-medium text-ink">Canonical:</strong>{' '}
                        <code className="text-ink">Components/molecules/FilterBar.tsx</code> — admin list indexes migrated in
                        PR-5O (~28 direct molecule imports). This lab section demos that implementation.
                    </p>
                    <p className="mt-2 text-sm text-muted">
                        <strong className="font-medium text-ink">Legacy duplicate:</strong>{' '}
                        <code className="text-ink">Components/Admin/ui/FilterBar.tsx</code> remains a separate grid/border
                        implementation still exported from <code className="text-ink">Admin/ui</code> until governance item{' '}
                        <strong className="font-medium text-ink">FE-201</strong> unifies the barrel. Do not add new divergent
                        copies — import the molecule or follow FE-201 when touching the barrel.
                    </p>
                </LabPanel>
            </div>

            <InfoPanel entry={filterBarCatalog} />
        </div>
    );
}
