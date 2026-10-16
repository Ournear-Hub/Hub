import { Head, useForm } from '@inertiajs/react';
import BackNavLink from '@/Components/molecules/BackNavLink';
import type { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import AdminAuthLayout from '@/Layouts/AdminAuthLayout';
import { Button, Input, Label } from '@/Components/Admin/ui';

type Props = {
    must_change?: boolean;
};

function PasswordForm({ mustChange }: { mustChange: boolean }) {
    const form = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put('/admin/profile/password');
    };

    return (
        <form onSubmit={submit} className="space-y-4">
            {mustChange ? (
                <p className="rounded-md border border-warning/30 bg-warning/10 px-3 py-2 text-sm text-ink">
                    You must set a new password before using Admin. The previous password is never displayed.
                </p>
            ) : (
                <p className="text-sm text-muted">Choose a new password. It is stored hashed and is never shown to other users.</p>
            )}
            {mustChange ? null : (
                <div>
                    <Label htmlFor="current_password">Current password</Label>
                    <Input
                        id="current_password"
                        type="password"
                        autoComplete="current-password"
                        value={form.data.current_password}
                        onChange={(event) => form.setData('current_password', event.target.value)}
                    />
                    {form.errors.current_password ? <p className="mt-1 text-sm text-danger">{form.errors.current_password}</p> : null}
                </div>
            )}
            <div>
                <Label htmlFor="password">New password</Label>
                <Input
                    id="password"
                    type="password"
                    autoComplete="new-password"
                    value={form.data.password}
                    onChange={(event) => form.setData('password', event.target.value)}
                />
                {form.errors.password ? <p className="mt-1 text-sm text-danger">{form.errors.password}</p> : null}
            </div>
            <div>
                <Label htmlFor="password_confirmation">Confirm new password</Label>
                <Input
                    id="password_confirmation"
                    type="password"
                    autoComplete="new-password"
                    value={form.data.password_confirmation}
                    onChange={(event) => form.setData('password_confirmation', event.target.value)}
                />
            </div>
            <Button type="submit" disabled={form.processing}>
                Save password
            </Button>
        </form>
    );
}

export default function AdminProfilePassword({ must_change = false }: Props) {
    if (must_change) {
        return (
            <AdminAuthLayout title="Set a new password">
                <Head title="Set a new password" />
                <PasswordForm mustChange />
            </AdminAuthLayout>
        );
    }

    return (
        <AdminLayout title="Change password">
            <div className="mb-6 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight text-ink">Change password</h1>
                    <p className="mt-1 text-sm text-muted">Admin profile security. Website Settings are unchanged.</p>
                </div>
                <BackNavLink href="/admin/profile" label="Back to profile" />
            </div>
            <div className="max-w-md rounded-xl border border-line bg-white p-5">
                <PasswordForm mustChange={false} />
            </div>
        </AdminLayout>
    );
}
