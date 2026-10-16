import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AdminAuthLayout from '@/Layouts/AdminAuthLayout';
import BackNavLink from '@/Components/molecules/BackNavLink';
import Alert from '@/Components/molecules/Alert';
import Button from '@/Components/atoms/Button';
import type { SharedPageProps } from '@/types/page';

export default function Login() {
    const { flash } = usePage<SharedPageProps>().props;
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post('/admin/login');
    };

    return (
        <AdminAuthLayout title="Admin login">
            <Head title="Admin login" />
            <BackNavLink href="/" label="Back to website" className="mb-4" />
            {flash.status ? (
                <Alert variant="success" className="mb-4">
                    {flash.status}
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
                <div>
                    <label htmlFor="password" className="mb-1 block text-sm font-medium text-ink">
                        Password
                    </label>
                    <input
                        id="password"
                        type="password"
                        value={data.password}
                        autoComplete="current-password"
                        className="w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand"
                        onChange={(event) => setData('password', event.target.value)}
                    />
                    {errors.password && <p className="mt-1 text-sm text-danger">{errors.password}</p>}
                </div>
                <div className="flex items-center justify-between gap-3">
                    <label className="flex items-center gap-2 text-sm text-muted">
                        <input
                            type="checkbox"
                            checked={data.remember}
                            onChange={(event) => setData('remember', event.target.checked)}
                        />
                        Remember me
                    </label>
                    <Link href="/admin/forgot-password" className="text-sm font-medium text-accent hover:underline">
                        Forgot password?
                    </Link>
                </div>
                <Button type="submit" surface="admin" variant="primary" fullWidth loading={processing} disabled={processing}>
                    Log in
                </Button>
            </form>
        </AdminAuthLayout>
    );
}
