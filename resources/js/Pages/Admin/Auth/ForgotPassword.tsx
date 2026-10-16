import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import BackNavLink from '@/Components/molecules/BackNavLink';
import AdminAuthLayout from '@/Layouts/AdminAuthLayout';
import Alert from '@/Components/molecules/Alert';
import Button from '@/Components/atoms/Button';

type ForgotPasswordProps = {
    status?: string | null;
};

export default function ForgotPassword({ status }: ForgotPasswordProps) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post('/admin/forgot-password');
    };

    return (
        <AdminAuthLayout title="Forgot password">
            <Head title="Forgot password" />
            <BackNavLink href="/admin/login" label="Back to admin login" className="mb-4" />
            <p className="mb-4 text-sm text-muted">
                Enter the administrator email address. If an account exists, a reset link will be sent.
            </p>
            {status ? (
                <Alert variant="success" className="mb-4">
                    {status}
                </Alert>
            ) : null}
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <label htmlFor="email" className="mb-1 block text-sm font-medium text-ink">
                        Email
                    </label>
                    <input
                        id="email"
                        type="email"
                        value={data.email}
                        autoComplete="username"
                        className="w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand"
                        onChange={(event) => setData('email', event.target.value)}
                    />
                    {errors.email && <p className="mt-1 text-sm text-danger">{errors.email}</p>}
                </div>
                <Button type="submit" surface="admin" variant="primary" fullWidth loading={processing} disabled={processing}>
                    Send reset link
                </Button>
            </form>
        </AdminAuthLayout>
    );
}
