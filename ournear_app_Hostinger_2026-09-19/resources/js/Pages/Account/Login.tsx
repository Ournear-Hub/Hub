import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import FormField from '@/Components/Public/FormField';
import PageContainer from '@/Components/Public/PageContainer';
import type { SharedPageProps } from '@/types/page';

export default function Login() {
    const { flash } = usePage<SharedPageProps>().props;
    const { data, setData, post, processing, errors } = useForm({
        login: '',
        password: '',
        remember: false,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post('/login');
    };

    return (
        <PublicLayout title="Log in">
            <Head title="Log in" />
            <section className="bg-brand py-14 text-white">
                <PageContainer>
                    <h1 className="text-4xl font-semibold">Log in</h1>
                    <p className="mt-3 max-w-2xl text-white/80">Access your candidate or employer account.</p>
                </PageContainer>
            </section>
            <section className="py-12">
                <PageContainer className="max-w-md">
                    {flash.status && (
                        <p className="mb-4 rounded-md border border-success/20 bg-success/10 px-3 py-2 text-sm text-success">
                            {flash.status}
                        </p>
                    )}
                    <form onSubmit={submit} className="space-y-4 rounded-xl border border-line bg-white p-6">
                        <FormField
                            id="login"
                            label="Username or Email"
                            value={data.login}
                            autoComplete="username"
                            onChange={(event) => setData('login', event.target.value)}
                        />
                        {errors.login && <p className="-mt-2 text-sm text-danger">{errors.login}</p>}
                        <FormField
                            id="password"
                            label="Password"
                            type="password"
                            value={data.password}
                            autoComplete="current-password"
                            onChange={(event) => setData('password', event.target.value)}
                        />
                        {errors.password && <p className="-mt-2 text-sm text-danger">{errors.password}</p>}
                        <div className="flex items-center justify-between gap-3">
                            <label className="flex items-center gap-2 text-sm text-muted">
                                <input
                                    type="checkbox"
                                    checked={data.remember}
                                    onChange={(event) => setData('remember', event.target.checked)}
                                />
                                Remember me
                            </label>
                            <Link href="/forgot-password" className="text-sm font-medium text-accent hover:underline">
                                Forgot password?
                            </Link>
                        </div>
                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white hover:opacity-95 disabled:bg-disabled"
                        >
                            Log in
                        </button>
                    </form>
                    <p className="mt-6 text-center text-sm text-muted">
                        Need an account?{' '}
                        <Link href="/register" className="font-medium text-brand hover:underline">
                            Register
                        </Link>
                    </p>
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
