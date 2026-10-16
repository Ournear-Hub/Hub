import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import FormField from '@/Components/Public/FormField';
import Alert from '@/Components/molecules/Alert';
import BackNavLink from '@/Components/molecules/BackNavLink';
import Button from '@/Components/atoms/Button';
import PageContainer from '@/Components/Public/PageContainer';

type ForgotPasswordProps = {
    status?: string | null;
};

export default function ForgotPassword({ status }: ForgotPasswordProps) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post('/forgot-password');
    };

    return (
        <PublicLayout title="Forgot password">
            <Head title="Forgot password" />
            <section className="bg-brand py-14 text-white">
                <PageContainer>
                    <h1 className="text-4xl font-semibold">Forgot password</h1>
                    <p className="mt-3 max-w-2xl text-white/80">
                        Enter your account email. If an account exists, a reset link will be sent.
                    </p>
                </PageContainer>
            </section>
            <section className="py-12">
                <PageContainer className="max-w-md">
                    <BackNavLink href="/login" label="Back to login" className="mb-4" />
                    {status ? (
                        <Alert variant="success" className="mb-4">
                            {status}
                        </Alert>
                    ) : null}
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
                        <Button type="submit" surface="public" variant="primary" fullWidth loading={processing} disabled={processing}>
                            Send reset link
                        </Button>
                    </form>
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
