import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AdminAuthLayout from '@/Layouts/AdminAuthLayout';

type ResetPasswordProps = {
    email: string;
    token: string;
};

export default function ResetPassword({ email, token }: ResetPasswordProps) {
    const { data, setData, post, processing, errors } = useForm({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post('/admin/reset-password');
    };

    return (
        <AdminAuthLayout title="Reset password">
            <Head title="Reset password" />
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
                <div>
                    <label htmlFor="password" className="mb-1 block text-sm font-medium text-ink">
                        New password
                    </label>
                    <input
                        id="password"
                        type="password"
                        value={data.password}
                        autoComplete="new-password"
                        className="w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand"
                        onChange={(event) => setData('password', event.target.value)}
                    />
                    {errors.password && <p className="mt-1 text-sm text-danger">{errors.password}</p>}
                </div>
                <div>
                    <label htmlFor="password_confirmation" className="mb-1 block text-sm font-medium text-ink">
                        Confirm password
                    </label>
                    <input
                        id="password_confirmation"
                        type="password"
                        value={data.password_confirmation}
                        autoComplete="new-password"
                        className="w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand"
                        onChange={(event) => setData('password_confirmation', event.target.value)}
                    />
                </div>
                <button
                    type="submit"
                    disabled={processing}
                    className="w-full rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white hover:opacity-95 disabled:bg-disabled"
                >
                    Reset password
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
