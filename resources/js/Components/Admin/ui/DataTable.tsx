import type { ReactNode } from 'react';
import EmptyState from '@/Components/Admin/ui/EmptyState';

export type DataTableColumn<T> = {
    key: string;
    header: string;
    render: (row: T) => ReactNode;
    className?: string;
};

type DataTableProps<T> = {
    columns: Array<DataTableColumn<T>>;
    rows: T[];
    rowKey: (row: T) => string | number;
    emptyTitle: string;
    emptyDescription?: string;
    onRowClick?: (row: T) => void;
    selectedKey?: string | number | null;
};

export default function DataTable<T>({
    columns,
    rows,
    rowKey,
    emptyTitle,
    emptyDescription,
    onRowClick,
    selectedKey,
}: DataTableProps<T>) {
    return (
        <div className="overflow-x-auto rounded-xl border border-line bg-white">
            <table className="min-w-full text-left text-sm">
                <thead className="border-b border-line bg-surface text-muted">
                    <tr>
                        {columns.map((column) => (
                            <th key={column.key} className="px-4 py-3 font-medium">
                                {column.header}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {rows.length === 0 ? (
                        <tr>
                            <td colSpan={columns.length}>
                                <EmptyState title={emptyTitle} description={emptyDescription} />
                            </td>
                        </tr>
                    ) : (
                        rows.map((row) => {
                            const key = rowKey(row);
                            const selected = selectedKey !== undefined && selectedKey !== null && selectedKey === key;

                            return (
                                <tr
                                    key={key}
                                    className={`border-b border-line last:border-0 ${
                                        onRowClick ? 'cursor-pointer' : ''
                                    } ${selected ? 'bg-surface' : onRowClick ? 'hover:bg-surface/60' : ''}`}
                                    onClick={onRowClick ? () => onRowClick(row) : undefined}
                                >
                                    {columns.map((column) => (
                                        <td key={column.key} className={`px-4 py-3 ${column.className ?? 'text-muted'}`}>
                                            {column.render(row)}
                                        </td>
                                    ))}
                                </tr>
                            );
                        })
                    )}
                </tbody>
            </table>
        </div>
    );
}
