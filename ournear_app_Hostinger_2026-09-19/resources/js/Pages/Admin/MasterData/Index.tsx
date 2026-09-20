import { router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useMemo, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import {
    ActivityFeed,
    Button,
    ConfirmDialog,
    DataTable,
    Drawer,
    FilterBar,
    Input,
    Label,
    SearchBar,
    Select,
    StatusBadge,
    Tabs,
    Textarea,
} from '@/Components/Admin/ui';
import type { DataTableColumn } from '@/Components/Admin/ui';
import type { MasterRecord, MasterWorkspaceProps } from '@/types/masterData';

export default function MasterDataIndex({ tab, tabs, meta, records, filters, options }: MasterWorkspaceProps) {
    const [editing, setEditing] = useState<MasterRecord | 'new' | null>(null);
    const [statusTarget, setStatusTarget] = useState<MasterRecord | null>(null);

    const form = useForm({
        name: '',
        description: '',
        sort_order: 0,
        is_active: true,
        industry_id: '',
        job_category_id: '',
    });

    const visit = (overrides: Record<string, string | undefined> = {}) => {
        const next = {
            tab,
            search: filters.search,
            status: filters.status,
            industry_id: filters.industry_id,
            job_category_id: filters.job_category_id,
            ...overrides,
        };

        const params: Record<string, string> = { tab: next.tab };
        if (next.search) {
            params.search = next.search;
        }
        if (next.status && next.status !== 'all') {
            params.status = next.status;
        }
        if (next.industry_id) {
            params.industry_id = next.industry_id;
        }
        if (next.job_category_id) {
            params.job_category_id = next.job_category_id;
        }

        router.get('/admin/master-data', params, { preserveState: true, preserveScroll: true });
    };

    const openCreate = () => {
        form.reset();
        form.clearErrors();
        form.setData({
            name: '',
            description: '',
            sort_order: 0,
            is_active: true,
            industry_id: filters.industry_id,
            job_category_id: filters.job_category_id,
        });
        setEditing('new');
    };

    const openEdit = (record: MasterRecord) => {
        form.clearErrors();
        form.setData({
            name: record.name,
            description: record.description ?? '',
            sort_order: record.sort_order,
            is_active: record.is_active,
            industry_id: record.industry_id ? String(record.industry_id) : '',
            job_category_id: record.job_category_id ? String(record.job_category_id) : '',
        });
        setEditing(record);
    };

    const closeDrawer = () => {
        setEditing(null);
        form.reset();
        form.clearErrors();
    };

    const selectedIndustry = form.data.industry_id;
    const categoryOptions = useMemo(
        () =>
            options.categories.filter((category) => {
                if (selectedIndustry && String(category.industry_id) !== String(selectedIndustry)) {
                    return false;
                }

                if (editing === 'new') {
                    return category.selectable !== false;
                }

                return true;
            }),
        [options.categories, selectedIndustry, editing],
    );

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (editing === 'new') {
            form.post(`/admin/master-data/${tab}`, {
                preserveScroll: true,
                onSuccess: () => closeDrawer(),
            });
            return;
        }

        if (editing) {
            form.put(`/admin/master-data/${tab}/${editing.id}`, {
                preserveScroll: true,
                onSuccess: () => closeDrawer(),
            });
        }
    };

    const confirmStatus = () => {
        if (!statusTarget) {
            return;
        }

        router.patch(
            `/admin/master-data/${tab}/${statusTarget.id}/status`,
            { is_active: !statusTarget.is_active },
            {
                preserveScroll: true,
                onSuccess: () => setStatusTarget(null),
            },
        );
    };

    const columns: Array<DataTableColumn<MasterRecord>> = [
        {
            key: 'name',
            header: 'Name',
            className: 'text-ink',
            render: (row) => (
                <div>
                    <p className="font-medium text-ink">{row.name}</p>
                    {row.code ? <p className="text-xs text-muted">Code: {row.code}</p> : null}
                    {row.industry_name ? <p className="text-xs text-muted">{row.industry_name}</p> : null}
                    {row.category_name ? <p className="text-xs text-muted">{row.category_name}</p> : null}
                </div>
            ),
        },
        {
            key: 'status',
            header: 'Status',
            render: (row) => <StatusBadge status={row.status_label} />,
        },
        {
            key: 'usage',
            header: 'In use',
            render: (row) => row.usage_count,
        },
        {
            key: 'actions',
            header: 'Actions',
            render: (row) => (
                <div className="flex flex-wrap gap-2" onClick={(event) => event.stopPropagation()}>
                    <Button variant="secondary" onClick={() => openEdit(row)}>
                        Edit
                    </Button>
                    {row.can_deactivate ? (
                        <Button variant="danger" onClick={() => setStatusTarget(row)}>
                            Deactivate
                        </Button>
                    ) : null}
                    {row.can_activate ? (
                        <Button variant="secondary" onClick={() => setStatusTarget(row)}>
                            Activate
                        </Button>
                    ) : null}
                </div>
            ),
        },
    ];

    const fieldError = (name: keyof typeof form.errors) =>
        form.errors[name] ? <p className="mt-1 text-sm text-danger">{form.errors[name]}</p> : null;

    const editingRecord = editing !== null && editing !== 'new' ? editing : null;

    return (
        <AdminLayout title="Master Data">
            <div className="mb-6 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight text-ink">Master Data</h1>
                    <p className="mt-1 text-sm text-muted">
                        Classification and lookup values for recruitment. Existing job and application text is preserved.
                    </p>
                </div>
                {meta.allows_create ? <Button onClick={openCreate}>Add {meta.singular}</Button> : null}
            </div>

            <Tabs
                tabs={tabs}
                active={tab}
                onChange={(id) => {
                    closeDrawer();
                    visit({ tab: id, job_category_id: '', industry_id: '' });
                }}
            />

            <FilterBar
                className="mt-4 md:grid-cols-5"
                onSubmit={(event) => {
                    event.preventDefault();
                    const data = new FormData(event.currentTarget);
                    visit({
                        search: String(data.get('search') ?? ''),
                        status: String(data.get('status') ?? 'all'),
                        industry_id: String(data.get('industry_id') ?? ''),
                        job_category_id: String(data.get('job_category_id') ?? ''),
                    });
                }}
            >
                <SearchBar defaultValue={filters.search} placeholder={`Search ${meta.label.toLowerCase()}`} />
                <Select name="status" defaultValue={filters.status || 'all'} aria-label="Status">
                    <option value="all">All statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </Select>
                {meta.has_industry_parent ? (
                    <Select name="industry_id" defaultValue={filters.industry_id} aria-label="Industry">
                        <option value="">All industries</option>
                        {options.industries.map((industry) => (
                            <option key={industry.id} value={industry.id}>
                                {industry.name}
                                {industry.is_active ? '' : ' (inactive)'}
                            </option>
                        ))}
                    </Select>
                ) : null}
                {meta.has_category_parent ? (
                    <Select name="job_category_id" defaultValue={filters.job_category_id} aria-label="Category">
                        <option value="">All categories</option>
                        {options.categories
                            .filter((category) => !filters.industry_id || String(category.industry_id) === filters.industry_id)
                            .map((category) => (
                                <option key={category.id} value={category.id}>
                                    {category.name}
                                </option>
                            ))}
                    </Select>
                ) : null}
                <Button type="submit" variant="secondary">
                    Apply
                </Button>
            </FilterBar>

            <DataTable
                columns={columns}
                rows={records.data}
                rowKey={(row) => row.id}
                emptyTitle={`No ${meta.label.toLowerCase()} yet`}
                emptyDescription={meta.allows_create ? `Add the first ${meta.singular.toLowerCase()} to get started.` : undefined}
                onRowClick={openEdit}
                selectedKey={editingRecord?.id ?? null}
            />

            <Pagination links={records.links} />

            <Drawer
                open={editing !== null}
                title={editing === 'new' ? `Add ${meta.singular}` : `Edit ${meta.singular}`}
                onClose={closeDrawer}
                footer={
                    <div className="flex justify-end gap-2">
                        <Button variant="secondary" onClick={closeDrawer}>
                            Cancel
                        </Button>
                        <Button type="submit" form="master-record-form" disabled={form.processing}>
                            {form.processing ? 'Saving…' : 'Save'}
                        </Button>
                    </div>
                }
            >
                <form id="master-record-form" onSubmit={submit} className="space-y-4">
                    {meta.has_industry_parent ? (
                        <div>
                            <Label htmlFor="industry_id">Industry</Label>
                            <Select
                                id="industry_id"
                                value={form.data.industry_id}
                                onChange={(event) => {
                                    form.setData('industry_id', event.target.value);
                                    if (meta.has_category_parent) {
                                        form.setData('job_category_id', '');
                                    }
                                }}
                            >
                                <option value="">Select industry</option>
                                {options.industries
                                    .filter((industry) => industry.is_active || String(industry.id) === String(form.data.industry_id))
                                    .map((industry) => (
                                        <option key={industry.id} value={industry.id}>
                                            {industry.name}
                                        </option>
                                    ))}
                            </Select>
                            {fieldError('industry_id')}
                        </div>
                    ) : null}

                    {meta.has_category_parent ? (
                        <div>
                            <Label htmlFor="job_category_id">Category</Label>
                            <Select
                                id="job_category_id"
                                value={form.data.job_category_id}
                                onChange={(event) => form.setData('job_category_id', event.target.value)}
                            >
                                <option value="">Select category</option>
                                {categoryOptions.map((category) => (
                                    <option key={category.id} value={category.id}>
                                        {category.name}
                                    </option>
                                ))}
                            </Select>
                            {fieldError('job_category_id')}
                        </div>
                    ) : null}

                    <div>
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            value={form.data.name}
                            disabled={editingRecord?.can_edit_name === false}
                            onChange={(event) => form.setData('name', event.target.value)}
                        />
                        {fieldError('name')}
                    </div>

                    {meta.has_code && editingRecord?.code ? (
                        <p className="text-sm text-muted">Existing records continue to use code “{editingRecord.code}”.</p>
                    ) : null}

                    {meta.has_description ? (
                        <div>
                            <Label htmlFor="description">Description</Label>
                            <Textarea
                                id="description"
                                value={form.data.description}
                                onChange={(event) => form.setData('description', event.target.value)}
                            />
                            {fieldError('description')}
                        </div>
                    ) : null}

                    <div>
                        <Label htmlFor="sort_order">Sort order</Label>
                        <Input
                            id="sort_order"
                            type="number"
                            min={0}
                            value={form.data.sort_order}
                            onChange={(event) => form.setData('sort_order', Number(event.target.value))}
                        />
                        {fieldError('sort_order')}
                    </div>

                    {editingRecord ? (
                        <div>
                            <p className="mb-2 text-sm font-medium text-ink">Recent changes</p>
                            <ActivityFeed items={editingRecord.recent_changes} empty="No recorded changes yet." />
                        </div>
                    ) : null}
                </form>
            </Drawer>

            <ConfirmDialog
                open={statusTarget !== null}
                title={statusTarget?.is_active ? `Deactivate ${meta.singular.toLowerCase()}?` : `Activate ${meta.singular.toLowerCase()}?`}
                description={
                    statusTarget?.is_active
                        ? `${statusTarget.name} will no longer be selectable for new records. Historical records stay unchanged${
                              statusTarget.usage_count > 0 ? ` (${statusTarget.usage_count} in use)` : ''
                          }.`
                        : `${statusTarget?.name ?? 'This record'} will be available for new records again.`
                }
                confirmLabel={statusTarget?.is_active ? 'Deactivate' : 'Activate'}
                danger={statusTarget?.is_active === true}
                onConfirm={confirmStatus}
                onClose={() => setStatusTarget(null)}
            />
        </AdminLayout>
    );
}
