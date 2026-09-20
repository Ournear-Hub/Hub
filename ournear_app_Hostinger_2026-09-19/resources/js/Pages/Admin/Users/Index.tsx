import { router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import {
    Button,
    DataTable,
    Drawer,
    FilterBar,
    Input,
    Label,
    Modal,
    SearchBar,
    Select,
    StatusBadge,
} from '@/Components/Admin/ui';
import type { DataTableColumn } from '@/Components/Admin/ui';
import type { Paginator } from '@/types/jobs';
import type { StaffRoleOption, UserDetail, UserListItem } from '@/types/security';

type Props = {
    users: Paginator<UserListItem>;
    selected: UserDetail | null;
    filters: {
        search: string;
        account_type: string;
        status: string;
        role_id: string;
        user: string;
    };
    options: {
        account_types: Array<{ value: string; label: string }>;
        roles: StaffRoleOption[];
        statuses: Array<{ value: string; label: string }>;
    };
    can_manage: boolean;
    actor_is_super_admin: boolean;
};

function accountLabel(type: string): string {
    return type.charAt(0).toUpperCase() + type.slice(1);
}

export default function UsersIndex({
    users,
    selected,
    filters,
    options,
    can_manage,
    actor_is_super_admin,
}: Props) {
    const [createOpen, setCreateOpen] = useState(false);
    const visit = (overrides: Record<string, string | undefined> = {}) => {
        const next = { ...filters, ...overrides };
        const params: Record<string, string> = {};
        Object.entries(next).forEach(([key, value]) => {
            if (value) {
                params[key] = value;
            }
        });
        router.get('/admin/users', params, { preserveState: true, preserveScroll: true });
    };

    const closeDrawer = useCallback(() => visit({ user: '' }), [filters]);

    const filter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const data = Object.fromEntries(new FormData(event.currentTarget).entries()) as Record<string, string>;
        visit({ ...data, user: filters.user });
    };

    const assignableRoles = useMemo(
        () => options.roles.filter((role) => actor_is_super_admin || role.slug !== 'super_admin'),
        [actor_is_super_admin, options.roles],
    );

    const createForm = useForm({
        name: '',
        email: '',
        role_ids: [] as number[],
    });

    const profileForm = useForm({
        name: selected?.name ?? '',
        email: selected?.email ?? '',
    });

    const roleForm = useForm({
        role_ids: selected?.roles.map((role) => role.id) ?? [],
    });

    useEffect(() => {
        profileForm.setData({
            name: selected?.name ?? '',
            email: selected?.email ?? '',
        });
        roleForm.setData({
            role_ids: selected?.roles.map((role) => role.id) ?? [],
        });
    }, [selected?.id]);

    const columns: Array<DataTableColumn<UserListItem>> = [
        { key: 'name', header: 'Name', className: 'font-medium text-ink', render: (row) => row.name },
        { key: 'email', header: 'Email', render: (row) => row.email },
        {
            key: 'account_type',
            header: 'Account',
            render: (row) => <StatusBadge status={accountLabel(row.account_type)} />,
        },
        {
            key: 'roles',
            header: 'Roles',
            render: (row) => (row.roles.length ? row.roles.map((role) => role.name).join(', ') : '—'),
        },
        {
            key: 'status',
            header: 'Status',
            render: (row) => <StatusBadge status={row.is_active ? 'Active' : 'Inactive'} />,
        },
    ];

    const toggleRole = (ids: number[], roleId: number): number[] =>
        ids.includes(roleId) ? ids.filter((id) => id !== roleId) : [...ids, roleId];

    return (
        <AdminLayout title="Users">
            <div className="mb-6 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight text-ink">Users</h1>
                    <p className="mt-1 text-sm text-muted">
                        Account identity stays on the user record. Staff access is granted through roles and permissions.
                    </p>
                </div>
                {can_manage ? <Button onClick={() => setCreateOpen(true)}>New staff user</Button> : null}
            </div>

            <FilterBar onSubmit={filter}>
                <SearchBar name="search" defaultValue={filters.search} placeholder="Search name or email" />
                <Select name="account_type" defaultValue={filters.account_type} aria-label="Account type">
                    <option value="">All accounts</option>
                    {options.account_types.map((type) => (
                        <option key={type.value} value={type.value}>
                            {type.label}
                        </option>
                    ))}
                </Select>
                <Select name="status" defaultValue={filters.status} aria-label="Status">
                    <option value="">All statuses</option>
                    {options.statuses.map((status) => (
                        <option key={status.value} value={status.value}>
                            {status.label}
                        </option>
                    ))}
                </Select>
                <Select name="role_id" defaultValue={filters.role_id} aria-label="Staff role">
                    <option value="">All roles</option>
                    {options.roles.map((role) => (
                        <option key={role.id} value={role.id}>
                            {role.name}
                        </option>
                    ))}
                </Select>
                <Button type="submit">Filter</Button>
            </FilterBar>

            <DataTable
                columns={columns}
                rows={users.data}
                rowKey={(row) => row.id}
                emptyTitle="No users match these filters"
                onRowClick={(row) => visit({ user: String(row.id) })}
                selectedKey={selected?.id ?? null}
            />

            <Pagination links={users.links} />

            <Drawer open={selected !== null} title={selected?.name ?? 'User'} onClose={closeDrawer}>
                {selected ? (
                    <div className="space-y-6 text-sm">
                        <div className="flex flex-wrap gap-2">
                            <StatusBadge status={selected.is_active ? 'Active' : 'Inactive'} />
                            {selected.roles.map((role) => (
                                <StatusBadge key={role.id} status={role.name} />
                            ))}
                        </div>
                        <p className="text-muted">{selected.email}</p>

                        {can_manage && selected.account_type === 'admin' ? (
                            <>
                                <form
                                    className="space-y-3"
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        profileForm.put(`/admin/users/${selected.id}`, { preserveScroll: true });
                                    }}
                                >
                                    <div>
                                        <Label htmlFor="user-name">Name</Label>
                                        <Input
                                            id="user-name"
                                            value={profileForm.data.name}
                                            onChange={(event) => profileForm.setData('name', event.target.value)}
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="user-email">Email</Label>
                                        <Input
                                            id="user-email"
                                            type="email"
                                            value={profileForm.data.email}
                                            onChange={(event) => profileForm.setData('email', event.target.value)}
                                        />
                                    </div>
                                    <Button type="submit" disabled={profileForm.processing}>
                                        Save details
                                    </Button>
                                </form>

                                <form
                                    className="space-y-2"
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        roleForm.put(`/admin/users/${selected.id}/roles`, { preserveScroll: true });
                                    }}
                                >
                                    <p className="font-medium text-ink">Staff roles</p>
                                    {assignableRoles.map((role) => (
                                        <label key={role.id} className="flex items-center gap-2">
                                            <input
                                                type="checkbox"
                                                checked={roleForm.data.role_ids.includes(role.id)}
                                                onChange={() =>
                                                    roleForm.setData('role_ids', toggleRole(roleForm.data.role_ids, role.id))
                                                }
                                            />
                                            {role.name}
                                        </label>
                                    ))}
                                    <Button type="submit" disabled={roleForm.processing}>
                                        Save roles
                                    </Button>
                                </form>

                                <div className="flex flex-wrap gap-2">
                                    <Button
                                        variant="secondary"
                                        onClick={() =>
                                            router.patch(
                                                `/admin/users/${selected.id}/status`,
                                                { is_active: !selected.is_active },
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        {selected.is_active ? 'Deactivate' : 'Activate'}
                                    </Button>
                                    <Button
                                        variant="secondary"
                                        onClick={() =>
                                            router.post(`/admin/users/${selected.id}/password-reset`, {}, { preserveScroll: true })
                                        }
                                    >
                                        Send password reset
                                    </Button>
                                </div>
                            </>
                        ) : (
                            <p className="text-muted">
                                Candidate and employer accounts keep their portal identity. Staff roles are assigned only to Admin
                                accounts.
                            </p>
                        )}
                    </div>
                ) : null}
            </Drawer>

            <Modal open={createOpen} title="New staff user" onClose={() => setCreateOpen(false)}>
                <form
                    className="space-y-3 text-ink"
                    onSubmit={(event) => {
                        event.preventDefault();
                        createForm.post('/admin/users', {
                            preserveScroll: true,
                            onSuccess: () => {
                                setCreateOpen(false);
                                createForm.reset();
                            },
                        });
                    }}
                >
                    <div>
                        <Label htmlFor="create-name">Name</Label>
                        <Input
                            id="create-name"
                            value={createForm.data.name}
                            onChange={(event) => createForm.setData('name', event.target.value)}
                        />
                    </div>
                    <div>
                        <Label htmlFor="create-email">Email</Label>
                        <Input
                            id="create-email"
                            type="email"
                            value={createForm.data.email}
                            onChange={(event) => createForm.setData('email', event.target.value)}
                        />
                    </div>
                    <div className="space-y-2">
                        <p className="text-sm font-medium text-ink">Roles</p>
                        {assignableRoles.map((role) => (
                            <label key={role.id} className="flex items-center gap-2 text-sm text-ink">
                                <input
                                    type="checkbox"
                                    checked={createForm.data.role_ids.includes(role.id)}
                                    onChange={() =>
                                        createForm.setData('role_ids', toggleRole(createForm.data.role_ids, role.id))
                                    }
                                />
                                {role.name}
                            </label>
                        ))}
                    </div>
                    <p className="text-xs text-muted">A password reset email is sent. Passwords are never displayed.</p>
                    <Button type="submit" disabled={createForm.processing}>
                        Create user
                    </Button>
                </form>
            </Modal>
        </AdminLayout>
    );
}
