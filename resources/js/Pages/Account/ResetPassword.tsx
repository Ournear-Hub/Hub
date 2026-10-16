import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import PasswordStrength from '@/Components/Account/PasswordStrength';
import FormField from '@/Components/Public/FormField';
import BackNavLink from '@/Components/molecules/BackNavLink';
import Button from '@/Components/atoms/Button';
import PageContainer from '@/Components/Public/PageContainer';

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
        post('/reset-password');
    };

    return (
        <PublicLayout title="Reset password">
            <Head title="Reset password" />
            <section className="bg-brand py-14 text-white">
                <PageContainer>
                    <h1 className="text-4xl font-semibold">Reset password</h1>
                </PageContainer>
            </section>
            <section className="py-12">
                <PageContainer className="max-w-md">
                    <BackNavLink href="/login" label="Back to login" className="mb-4" />
                    <form onSubmit={submit} className="space-y-4 rounded-xl border border-line bg-white p-6">
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
                            label="New password"
                            type="password"
                            value={data.password}
                            autoComplete="new-password"
                            onChange={(event) => setData('password', event.target.value)}
                        />
                        <PasswordStrength password={data.password} />
                        {errors.password && <p className="-mt-2 text-sm text-danger">{errors.password}</p>}
                        <FormField
                            id="password_confirmation"
                            label="Confirm password"
                            type="password"
                            value={data.password_confirmation}
                            autoComplete="new-password"
                            onChange={(event) => setData('password_confirmation', event.target.value)}
                        />
                        <Button type="submit" surface="public" variant="primary" fullWidth loading={processing} disabled={processing}>
                            Reset password
                        </Button>
                    </form>
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
