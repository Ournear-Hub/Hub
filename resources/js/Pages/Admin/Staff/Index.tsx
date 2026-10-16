import { Link, router, useForm } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useState, type FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import {
    Button,
    ConfirmDialog,
    Drawer,
    Input,
    Label,
    Modal,
    SearchBar,
    Select,
    StatusBadge,
} from '@/Components/Admin/ui';
import FilterBar from '@/Components/molecules/FilterBar';
import DataTable from '@/Components/molecules/DataTable';
import type { DataTableColumn } from '@/Components/molecules/DataTable';
import type { Paginator } from '@/types/jobs';
import type { StaffRoleOption, UserDetail, UserListItem } from '@/types/security';

type Props = {
    users: Paginator<UserDetail>;
    selected: UserDetail | null;
    filters: {
        search: string;
        status: string;
        role_id: string;
        user: string;
    };
    options: {
        roles: StaffRoleOption[];
        statuses: Array<{ value: string; label: string }>;
    };
    can_manage: boolean;
    actor_is_super_admin: boolean;
};

export default function StaffIndex({ users, selected, filters, options, can_manage, actor_is_super_admin }: Props) {
    const [createOpen, setCreateOpen] = useState(false);
    const [pendingStatus, setPendingStatus] = useState<string | null>(null);

    const visit = useCallback(
        (overrides: Record<string, string | undefined> = {}, replaceAll = false) => {
            const next = replaceAll ? overrides : { ...filters, ...overrides };
            const params: Record<string, string> = {};
            Object.entries(next).forEach(([key, value]) => {
                if (value) {
                    params[key] = value;
                }
            });
            router.get('/admin/staff', params, { preserveState: true, preserveScroll: true });
        },
        [filters],
    );

    const closeDrawer = useCallback(() => visit({ user: '' }), [visit]);

    const filter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const data = Object.fromEntries(new FormData(event.currentTarget).entries()) as Record<string, string>;
        visit({ ...data, user: filters.user });
    };

    const assignableRoles = options.roles.filter((role) => role.assignable || actor_is_super_admin);

    const createForm = useForm({
        name: '',
        email: '',
        role_ids: [] as number[],
    });

    const detailsForm = useForm({
        name: selected?.name ?? '',
        email: selected?.email ?? '',
    });

    const rolesForm = useForm({
        role_ids: selected?.roles.map((role) => role.id) ?? [],
    });

    useEffect(() => {
        detailsForm.setData({
            name: selected?.name ?? '',
            email: selected?.email ?? '',
        });
        rolesForm.setData({
            role_ids: selected?.roles.map((role) => role.id) ?? [],
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [selected?.id]);

    const selectedRoleIds = useMemo(() => new Set(rolesForm.data.role_ids), [rolesForm.data.role_ids]);

    const columns: Array<DataTableColumn<UserListItem>> = [
        {
            key: 'name',
            header: 'Staff',
            className: 'font-medium text-ink',
            render: (row) => (
                <div>
                    <p>{row.name}</p>
                    <p className="text-xs font-normal text-muted">{row.business_id || row.email}</p>
                </div>
            ),
        },
        { key: 'email', header: 'Email', render: (row) => row.email },
        {
            key: 'roles',
            header: 'Roles',
            render: (row) => row.roles.map((role) => role.name).join(', ') || '—',
        },
        {
            key: 'status',
            header: 'Status',
            render: (row) => <StatusBadge status={row.account_status_label || (row.is_active ? 'Active' : 'Inactive')} />,
        },
        {
            key: 'last_login',
            header: 'Last login',
            render: (row) => row.last_login_at || 'Never',
        },
    ];

    const toggleCreateRole = (roleId: number) => {
        const next = createForm.data.role_ids.includes(roleId)
            ? createForm.data.role_ids.filter((id) => id !== roleId)
            : [...createForm.data.role_ids, roleId];
        createForm.setData('role_ids', next);
    };

    const toggleSelectedRole = (roleId: number) => {
        const next = selectedRoleIds.has(roleId)
            ? rolesForm.data.role_ids.filter((id) => id !== roleId)
            : [...rolesForm.data.role_ids, roleId];
        rolesForm.setData('role_ids', next);
    };

    return (
        <AdminLayout title="Admin Users">
            <div className="mb-6 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight text-ink">Admin Users</h1>
                    <p className="mt-1 text-sm text-muted">
                        Staff accounts are separate from platform users. Passwords are never displayed.
                    </p>
                </div>
                {can_manage ? <Button onClick={() => setCreateOpen(true)}>Create Admin / Staff</Button> : null}
            </div>

            <FilterBar onSubmit={filter}>
                <SearchBar defaultValue={filters.search} placeholder="Search name, email, username, OurNear ID" />
                <Select name="status" defaultValue={filters.status} aria-label="Status">
                    <option value="">All statuses</option>
                    {options.statuses.map((status) => (
                        <option key={status.value} value={status.value}>
                            {status.label}
                        </option>
                    ))}
                </Select>
                <Select name="role_id" defaultValue={filters.role_id} aria-label="Role">
                    <option value="">All roles</option>
                    {options.roles.map((role) => (
                        <option key={role.id} value={role.id}>
                            {role.name}
                        </option>
                    ))}
                </Select>
                <div className="flex flex-wrap gap-2">
                    <Button type="submit">Apply filters</Button>
                    <Button type="button" variant="secondary" onClick={() => visit({}, true)}>
                        Reset filters
                    </Button>
                </div>
            </FilterBar>

            <DataTable
                columns={columns}
                rows={users.data}
                rowKey={(row) => row.id}
                emptyTitle="No staff accounts match these filters"
                emptyDescription="Create an Admin or Staff account to grant operational access."
                onRowClick={(row) => visit({ user: String(row.id) })}
                selectedKey={selected?.id ?? null}
            />
            <Pagination links={users.links} />

            <Drawer open={selected !== null} title={selected?.name ?? 'Staff'} onClose={closeDrawer} size="xl">
                {selected ? (
                    <div className="space-y-6">
                        <div className="flex flex-wrap gap-2">
                            <StatusBadge status={selected.account_status_label || (selected.is_active ? 'Active' : 'Inactive')} />
                            {selected.roles.map((role) => (
                                <StatusBadge key={role.id} status={role.name} />
                            ))}
                        </div>
                        <p className="text-sm text-muted">
                            {selected.business_id || 'OurNear ID pending'} · Last login: {selected.last_login_at || 'Never'}
                        </p>
                        <div className="flex flex-wrap gap-2">
                            <Link href={`/admin/users/${selected.id}`}>
                                <Button variant="secondary">Open User 360</Button>
                            </Link>
                            {can_manage ? (
                                <Button
                                    variant="secondary"
                                    onClick={() =>
                                        router.post(`/admin/users/${selected.id}/password-reset`, {}, { preserveScroll: true })
                                    }
                                >
                                    Send password reset
                                </Button>
                            ) : null}
                        </div>
                        {selected.must_change_password ? (
                            <p className="rounded-md border border-warning/30 bg-warning/10 px-3 py-2 text-sm">
                                This account must change password on next login. The password is never displayed.
                            </p>
                        ) : null}

                        {can_manage ? (
                            <form
                                className="space-y-3"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    detailsForm.put(`/admin/users/${selected.id}`, { preserveScroll: true });
                                }}
                            >
                                <div>
                                    <Label htmlFor="staff-name">Full name</Label>
                                    <Input id="staff-name" value={detailsForm.data.name} onChange={(event) => detailsForm.setData('name', event.target.value)} />
                                    {detailsForm.errors.name ? <p className="mt-1 text-sm text-danger">{detailsForm.errors.name}</p> : null}
                                </div>
                                <div>
                                    <Label htmlFor="staff-email">Email</Label>
                                    <Input id="staff-email" type="email" value={detailsForm.data.email} onChange={(event) => detailsForm.setData('email', event.target.value)} />
                                    {detailsForm.errors.email ? <p className="mt-1 text-sm text-danger">{detailsForm.errors.email}</p> : null}
                                </div>
                                <Button type="submit" disabled={detailsForm.processing}>
                                    Save details
                                </Button>
                            </form>
                        ) : null}

                        {can_manage ? (
                            <form
                                className="space-y-3"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    rolesForm.put(`/admin/users/${selected.id}/roles`, { preserveScroll: true });
                                }}
                            >
                                <Label>Roles</Label>
                                {assignableRoles.map((role) => (
                                    <label key={role.id} className="flex items-start gap-2 text-sm text-ink">
                                        <input
                                            type="checkbox"
                                            className="mt-1"
                                            checked={selectedRoleIds.has(role.id)}
                                            disabled={role.is_locked && !actor_is_super_admin}
                                            onChange={() => toggleSelectedRole(role.id)}
                                        />
                                        <span>
                                            <span className="font-medium">{role.name}</span>
                                            {role.is_locked ? <span className="ml-2 text-muted">Protected</span> : null}
                                        </span>
                                    </label>
                                ))}
                                {rolesForm.errors.role_ids ? <p className="text-sm text-danger">{rolesForm.errors.role_ids}</p> : null}
                                <Button type="submit" disabled={rolesForm.processing}>
                                    Save roles
                                </Button>
                            </form>
                        ) : null}

                        {can_manage ? (
                            <div>
                                <Label>Account status</Label>
                                <div className="mt-2 flex flex-wrap gap-2">
                                    {options.statuses.map((status) => (
                                        <Button
                                            key={status.value}
                                            variant={selected.account_status === status.value ? 'primary' : 'secondary'}
                                            onClick={() => {
                                                if (['blocked', 'suspended', 'deactivated'].includes(status.value)) {
                                                    setPendingStatus(status.value);
                                                } else {
                                                    router.patch(
                                                        `/admin/users/${selected.id}/status`,
                                                        { account_status: status.value },
                                                        { preserveScroll: true },
                                                    );
                                                }
                                            }}
                                        >
                                            {status.label}
                                        </Button>
                                    ))}
                                </div>
                            </div>
                        ) : null}

                        {selected.admin_profile ? (
                            <div className="rounded-lg bg-surface p-4 text-sm">
                                <p className="font-medium text-ink">Staff profile</p>
                                <p className="mt-1 text-muted">
                                    {selected.admin_profile.designation || 'No designation'} · {selected.admin_profile.department || 'No department'}
                                </p>
                                <p className="text-muted">Joined {selected.admin_profile.joined_at || '—'}</p>
                            </div>
                        ) : null}
                    </div>
                ) : null}
            </Drawer>

            <Modal open={createOpen} title="Create Admin / Staff" onClose={() => setCreateOpen(false)}>
                <form
                    className="space-y-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        createForm.post('/admin/staff', {
                            preserveScroll: true,
                            onSuccess: () => {
                                setCreateOpen(false);
                                createForm.reset();
                            },
                        });
                    }}
                >
                    <p className="text-sm text-muted">
                        A password reset email is sent. The password is never displayed. The new account must change password on first login.
                    </p>
                    <div>
                        <Label htmlFor="create-name">Full name</Label>
                        <Input id="create-name" value={createForm.data.name} onChange={(event) => createForm.setData('name', event.target.value)} />
                        {createForm.errors.name ? <p className="mt-1 text-sm text-danger">{createForm.errors.name}</p> : null}
                    </div>
                    <div>
                        <Label htmlFor="create-email">Email</Label>
                        <Input id="create-email" type="email" value={createForm.data.email} onChange={(event) => createForm.setData('email', event.target.value)} />
                        {createForm.errors.email ? <p className="mt-1 text-sm text-danger">{createForm.errors.email}</p> : null}
                    </div>
                    <fieldset className="space-y-2">
                        <Label>Roles</Label>
                        {assignableRoles.map((role) => (
                            <label key={role.id} className="flex items-start gap-2 text-sm text-ink">
                                <input
                                    type="checkbox"
                                    className="mt-1"
                                    checked={createForm.data.role_ids.includes(role.id)}
                                    onChange={() => toggleCreateRole(role.id)}
                                />
                                {role.name}
                            </label>
                        ))}
                        {createForm.errors.role_ids ? <p className="text-sm text-danger">{createForm.errors.role_ids}</p> : null}
                    </fieldset>
                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="secondary" onClick={() => setCreateOpen(false)}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={createForm.processing}>
                            Create staff
                        </Button>
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                open={pendingStatus !== null}
                title="Change staff status"
                description="This keeps the staff history. The account will not be able to sign in if blocked, suspended, or deactivated."
                confirmLabel="Change status"
                danger
                onClose={() => setPendingStatus(null)}
                onConfirm={() => {
                    if (selected && pendingStatus) {
                        router.patch(`/admin/users/${selected.id}/status`, { account_status: pendingStatus }, { preserveScroll: true });
                    }
                    setPendingStatus(null);
                }}
            />
        </AdminLayout>
    );
}
