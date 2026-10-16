import DataTable from '@/Components/molecules/DataTable';
import type { DataTableColumn } from '@/Components/molecules/DataTable';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { dataTableCatalog } from '@/ui-lab/catalog';
import { useMemo, useState } from 'react';

type DemoRow = {
    id: number;
    name: string;
    status: string;
};

const sampleRows: DemoRow[] = [
    { id: 1, name: 'Frontend engineer', status: 'Open' },
    { id: 2, name: 'Account executive', status: 'Paused' },
    { id: 3, name: 'Recruiting coordinator', status: 'Open' },
];

export default function DataTableLabSection() {
    const [selectedKey, setSelectedKey] = useState<number | null>(2);

    const columns = useMemo<Array<DataTableColumn<DemoRow>>>(
        () => [
            { key: 'name', header: 'Role', render: (row) => row.name },
            { key: 'status', header: 'Status', render: (row) => row.status },
        ],
        [],
    );

    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Rows with column definitions">
                    <DataTable
                        columns={columns}
                        rows={sampleRows}
                        rowKey={(row) => row.id}
                        emptyTitle="No rows"
                        onRowClick={(row) => setSelectedKey(row.id)}
                        selectedKey={selectedKey}
                    />
                    <p className="mt-2 text-xs text-muted">
                        Selected row id: {selectedKey ?? 'none'} (master–detail pattern; keyboard parity not in V2).
                    </p>
                </LabPanel>

                <LabPanel title="Empty state (canonical EmptyState, sm, in-cell)">
                    <DataTable
                        columns={columns}
                        rows={[]}
                        rowKey={(row) => row.id}
                        emptyTitle="No matching records"
                        emptyDescription="Try clearing filters or widening your search."
                    />
                </LabPanel>

                <LabPanel title="Non-goals">
                    <p className="text-sm text-muted">
                        No sorting, filtering, pagination, bulk selection, loading skeleton rows, or data fetching. Caller
                        supplies <code className="text-ink">rows</code> and composes Pagination as a sibling in production
                        lists.
                    </p>
                </LabPanel>

                <LabPanel title="V2 reference (unchanged)">
                    <p className="text-sm text-muted">
                        Production admin pages still import{' '}
                        <code className="text-ink">Components/Admin/ui/DataTable.tsx</code> (~29 consumers). M-A does not
                        migrate importers.
                    </p>
                </LabPanel>
            </div>

            <InfoPanel entry={dataTableCatalog} />
        </div>
    );
}
