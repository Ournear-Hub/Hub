/**
 * @vitest-environment jsdom
 */
import DataTable from '@/Components/molecules/DataTable';
import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';

type Row = { id: number; name: string; status: string };

const columns = [
    { key: 'name', header: 'Name', render: (row: Row) => row.name },
    { key: 'status', header: 'Status', render: (row: Row) => row.status },
];

describe('DataTable', () => {
    afterEach(() => {
        cleanup();
    });

    it('renders column headers and row cells', () => {
        render(
            <DataTable<Row>
                columns={columns}
                rows={[
                    { id: 1, name: 'Ada Lovelace', status: 'Active' },
                    { id: 2, name: 'Grace Hopper', status: 'Pending' },
                ]}
                rowKey={(row) => row.id}
                emptyTitle="No rows"
            />,
        );

        expect(screen.getByRole('table')).toBeTruthy();
        expect(screen.getByRole('columnheader', { name: 'Name' })).toBeTruthy();
        expect(screen.getByRole('columnheader', { name: 'Status' })).toBeTruthy();
        expect(screen.getByRole('cell', { name: 'Ada Lovelace' })).toBeTruthy();
        expect(screen.getByRole('cell', { name: 'Pending' })).toBeTruthy();
    });

    it('shows empty state copy when there are no rows', () => {
        render(
            <DataTable<Row>
                columns={columns}
                rows={[]}
                rowKey={(row) => row.id}
                emptyTitle="No applications yet"
                emptyDescription="Adjust filters or check back later."
            />,
        );

        expect(screen.getByText('No applications yet')).toBeTruthy();
        expect(screen.getByText('Adjust filters or check back later.')).toBeTruthy();
        expect(screen.queryByRole('columnheader', { name: 'Name' })).toBeTruthy();
        expect(screen.queryByText('Ada Lovelace')).toBeNull();
    });

    it('calls onRowClick with the clicked row when provided', () => {
        const onRowClick = vi.fn();
        const rows = [{ id: 10, name: 'Row A', status: 'Open' }];

        render(
            <DataTable<Row>
                columns={columns}
                rows={rows}
                rowKey={(row) => row.id}
                emptyTitle="Empty"
                onRowClick={onRowClick}
            />,
        );

        fireEvent.click(screen.getByRole('cell', { name: 'Row A' }));

        expect(onRowClick).toHaveBeenCalledTimes(1);
        expect(onRowClick).toHaveBeenCalledWith(rows[0]);
    });

    it('renders with selectedKey without data rows regressing', () => {
        render(
            <DataTable<Row>
                columns={columns}
                rows={[{ id: 5, name: 'Selected row', status: 'Active' }]}
                rowKey={(row) => row.id}
                emptyTitle="Empty"
                selectedKey={5}
                onRowClick={() => {}}
            />,
        );

        expect(screen.getByRole('cell', { name: 'Selected row' })).toBeTruthy();
    });
});
