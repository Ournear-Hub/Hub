import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import FormField from '@/Components/Public/FormField';
import PageContainer from '@/Components/Public/PageContainer';

export default function Register() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: 'candidate',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post('/register');
    };

    return (
        <PublicLayout title="Register">
            <Head title="Register" />
            <section className="bg-brand py-14 text-white">
                <PageContainer>
                    <h1 className="text-4xl font-semibold">Register</h1>
                    <p className="mt-3 max-w-2xl text-white/80">Create a candidate or employer account. You can still apply for jobs without an account.</p>
                </PageContainer>
            </section>
            <section className="py-12">
                <PageContainer className="max-w-md">
                    <form onSubmit={submit} className="space-y-4 rounded-xl border border-line bg-white p-6">
                        <fieldset>
                            <legend className="mb-2 text-sm font-medium text-ink">Account type</legend>
                            <div className="flex gap-4">
                                <label className="flex items-center gap-2 text-sm text-ink">
                                    <input
                                        type="radio"
                                        name="role"
                                        value="candidate"
                                        checked={data.role === 'candidate'}
                                        onChange={() => setData('role', 'candidate')}
                                    />
                                    Candidate
                                </label>
                                <label className="flex items-center gap-2 text-sm text-ink">
                                    <input
                                        type="radio"
                                        name="role"
                                        value="employer"
                                        checked={data.role === 'employer'}
                                        onChange={() => setData('role', 'employer')}
                                    />
                                    Employer
                                </label>
                            </div>
                            {errors.role && <p className="mt-1 text-sm text-danger">{errors.role}</p>}
                        </fieldset>
                        <FormField
                            id="name"
                            label="Name"
                            value={data.name}
                            autoComplete="name"
                            onChange={(event) => setData('name', event.target.value)}
                        />
                        {errors.name && <p className="-mt-2 text-sm text-danger">{errors.name}</p>}
                        <FormField
                            id="email"
                            label="Email"
                            type="email"
                            value={data.email}
                            autoComplete="username"
                            onChange={(event) => setData('email', event.target.value)}
                        />
                        {errors.email && <p className="-mt-2 text-sm text-danger">{errors.email}</p>}
                        <FormField
                            id="password"
                            label="Password"
                            type="password"
                            value={data.password}
                            autoComplete="new-password"
                            onChange={(event) => setData('password', event.target.value)}
                        />
                        {errors.password && <p className="-mt-2 text-sm text-danger">{errors.password}</p>}
                        <FormField
                            id="password_confirmation"
                            label="Confirm password"
                            type="password"
                            value={data.password_confirmation}
                            autoComplete="new-password"
                            onChange={(event) => setData('password_confirmation', event.target.value)}
                        />
                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white hover:opacity-95 disabled:bg-disabled"
                        >
                            Create account
                        </button>
                    </form>
                    <p className="mt-6 text-center text-sm text-muted">
                        Already registered?{' '}
                        <Link href="/login" className="font-medium text-brand hover:underline">
                            Log in
                        </Link>
                    </p>
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
