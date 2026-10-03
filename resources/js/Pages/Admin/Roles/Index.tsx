import { router, useForm } from '@inertiajs/react';
import { useEffect, useMemo } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Button, DataTable, Input, Label, StatusBadge, Textarea } from '@/Components/Admin/ui';
import type { DataTableColumn } from '@/Components/Admin/ui';
import type { PermissionGroup, RoleDetail } from '@/types/security';

type Props = {
    roles: RoleDetail[];
    selected: RoleDetail | null;
    permission_groups: PermissionGroup[];
    filters: {
        role: string;
    };
};

export default function RolesIndex({ roles, selected, permission_groups }: Props) {
    const createForm = useForm({
        name: '',
        description: '',
    });

    const form = useForm({
        permission_ids: selected?.permissions.map((permission) => permission.id) ?? [],
    });

    const selectedIds = useMemo(() => new Set(form.data.permission_ids), [form.data.permission_ids]);

    useEffect(() => {
        form.setData(
            'permission_ids',
            selected?.permissions.map((permission) => permission.id) ?? [],
        );
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [selected?.id]);

    const columns: Array<DataTableColumn<RoleDetail>> = [
        { key: 'name', header: 'Role', className: 'font-medium text-ink', render: (row) => row.name },
        { key: 'users', header: 'Users', render: (row) => String(row.users_count) },
        {
            key: 'permissions',
            header: 'Permissions',
            render: (row) => String(row.permissions.length),
        },
    ];

    const visit = (roleId: number) => {
        router.get('/admin/roles', { role: roleId }, { preserveState: true, preserveScroll: true });
        form.setData(
            'permission_ids',
            roles.find((role) => role.id === roleId)?.permissions.map((permission) => permission.id) ?? [],
        );
    };

    const toggle = (permissionId: number) => {
        const next = selectedIds.has(permissionId)
            ? form.data.permission_ids.filter((id) => id !== permissionId)
            : [...form.data.permission_ids, permissionId];
        form.setData('permission_ids', next);
    };

    return (
        <AdminLayout title="Roles & Permissions">
            <div className="mb-6">
                <h1 className="text-2xl font-semibold tracking-tight text-ink">Roles & Permissions</h1>
                <p className="mt-1 text-sm text-muted">
                    Create staff roles here, then assign existing permissions. Users receive access through role membership, not page-level flags.
                </p>
            </div>

            <div className="grid gap-6 lg:grid-cols-[minmax(0,320px)_minmax(0,1fr)]">
                <div className="space-y-6">
                    <form
                        className="rounded-xl border border-line bg-white p-5"
                        onSubmit={(event) => {
                            event.preventDefault();
                            createForm.post('/admin/roles', {
                                preserveScroll: true,
                                preserveState: false,
                                onSuccess: () => createForm.reset(),
                            });
                        }}
                    >
                        <h2 className="text-sm font-semibold text-ink">Create staff role</h2>
                        <p className="mt-1 text-xs text-muted">Unlocked roles can receive any existing permission. Super Admin stays locked.</p>
                        <div className="mt-4 space-y-3">
                            <div>
                                <Label htmlFor="role-name">Role name</Label>
                                <Input
                                    id="role-name"
                                    value={createForm.data.name}
                                    onChange={(event) => createForm.setData('name', event.target.value)}
                                    placeholder="Example: Talent Sourcer"
                                />
                                {createForm.errors.name ? <p className="mt-1 text-sm text-danger">{createForm.errors.name}</p> : null}
                            </div>
                            <div>
                                <Label htmlFor="role-description">Description</Label>
                                <Textarea
                                    id="role-description"
                                    value={createForm.data.description}
                                    onChange={(event) => createForm.setData('description', event.target.value)}
                                    placeholder="What this role is allowed to do"
                                />
                                {createForm.errors.description ? (
                                    <p className="mt-1 text-sm text-danger">{createForm.errors.description}</p>
                                ) : null}
                            </div>
                            <Button type="submit" disabled={createForm.processing}>
                                Create role
                            </Button>
                        </div>
                    </form>

                    <DataTable
                        columns={columns}
                        rows={roles}
                        rowKey={(row) => row.id}
                        emptyTitle="No roles"
                        onRowClick={(row) => visit(row.id)}
                        selectedKey={selected?.id ?? null}
                    />
                </div>

                {selected ? (
                    <div className="rounded-xl border border-line bg-white p-5">
                        <div className="mb-4 flex flex-wrap items-center gap-2">
                            <h2 className="text-lg font-semibold text-ink">{selected.name}</h2>
                            {selected.is_locked ? <StatusBadge status="Locked" /> : null}
                        </div>
                        <p className="mb-4 text-sm text-muted">{selected.description}</p>
                        {selected.is_locked ? (
                            <p className="text-sm text-muted">
                                Super Admin always receives every permission. This role cannot be edited.
                            </p>
                        ) : (
                            <form
                                className="space-y-5"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    form.put(`/admin/roles/${selected.id}/permissions`, { preserveScroll: true });
                                }}
                            >
                                {permission_groups.map((group) => (
                                    <fieldset key={group.group} className="space-y-2">
                                        <Label>{group.group}</Label>
                                        {group.permissions.map((permission) => (
                                            <label key={permission.id} className="flex items-start gap-2 text-sm text-ink">
                                                <input
                                                    type="checkbox"
                                                    className="mt-1"
                                                    checked={selectedIds.has(permission.id)}
                                                    onChange={() => toggle(permission.id)}
                                                />
                                                <span>
                                                    <span className="font-medium">{permission.label}</span>
                                                    <span className="ml-2 text-muted">{permission.name}</span>
                                                </span>
                                            </label>
                                        ))}
                                    </fieldset>
                                ))}
                                <Button type="submit" disabled={form.processing}>
                                    Save permissions
                                </Button>
                            </form>
                        )}
                    </div>
                ) : null}
            </div>
        </AdminLayout>
    );
}
