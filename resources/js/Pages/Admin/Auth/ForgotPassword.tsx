import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AdminAuthLayout from '@/Layouts/AdminAuthLayout';

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
            <p className="mb-4 text-sm text-muted">
                Enter the administrator email address. If an account exists, a reset link will be sent.
            </p>
            {status && (
                <p className="mb-4 rounded-md border border-success/20 bg-success/10 px-3 py-2 text-sm text-success">
                    {status}
                </p>
            )}
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
                <button
                    type="submit"
                    disabled={processing}
                    className="w-full rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white hover:opacity-95 disabled:bg-disabled"
                >
                    Send reset link
                </button>
            </form>
            <p className="mt-6 text-center text-sm">
                <Link href="/admin/login" className="font-medium text-brand hover:underline">
                    Back to login
                </Link>
            </p>
        </AdminAuthLayout>
    );
}
