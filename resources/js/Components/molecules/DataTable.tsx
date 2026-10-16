import EmptyState from '@/Components/molecules/EmptyState';
import {
    dataTableBodyCell,
    dataTableBodyCellMuted,
    dataTableBodyRow,
    dataTableBodyRowClickable,
    dataTableBodyRowHover,
    dataTableBodyRowSelected,
    dataTableEmptyStateInCell,
    dataTableHeadCell,
    dataTableHeadRow,
    dataTableRoot,
    dataTableTable,
} from '@/design-system/classNames';
import type { ReactNode } from 'react';

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
        <div className={dataTableRoot}>
            <table className={dataTableTable}>
                <thead className={dataTableHeadRow}>
                    <tr>
                        {columns.map((column) => (
                            <th key={column.key} className={dataTableHeadCell}>
                                {column.header}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {rows.length === 0 ? (
                        <tr>
                            <td colSpan={columns.length}>
                                <EmptyState
                                    size="sm"
                                    align="center"
                                    title={emptyTitle}
                                    description={emptyDescription}
                                    className={dataTableEmptyStateInCell}
                                />
                            </td>
                        </tr>
                    ) : (
                        rows.map((row) => {
                            const key = rowKey(row);
                            const selected =
                                selectedKey !== undefined && selectedKey !== null && selectedKey === key;

                            return (
                                <tr
                                    key={key}
                                    className={`${dataTableBodyRow} ${
                                        onRowClick ? dataTableBodyRowClickable : ''
                                    } ${selected ? dataTableBodyRowSelected : onRowClick ? dataTableBodyRowHover : ''}`}
                                    onClick={onRowClick ? () => onRowClick(row) : undefined}
                                >
                                    {columns.map((column) => (
                                        <td
                                            key={column.key}
                                            className={`${dataTableBodyCell} ${column.className ?? dataTableBodyCellMuted}`}
                                        >
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
