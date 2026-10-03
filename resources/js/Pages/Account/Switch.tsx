import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import PageContainer from '@/Components/Public/PageContainer';
import PublicLayout from '@/Layouts/PublicLayout';

type SwitchProps = {
    destination: 'login' | 'register';
    currentUserName: string;
};

export default function Switch({ destination, currentUserName }: SwitchProps) {
    const { data, post, processing } = useForm({ destination });
    const isRegistration = destination === 'register';

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post('/account/switch');
    };

    return (
        <PublicLayout title="Switch Account">
            <Head title="Switch Account" />
            <section className="bg-brand py-14 text-white">
                <PageContainer>
                    <h1 className="text-4xl font-semibold">Switch Account</h1>
                    <p className="mt-3 max-w-2xl text-white/80">
                        Continue to the Candidate and Employer {isRegistration ? 'registration' : 'login'}.
                    </p>
                </PageContainer>
            </section>
            <section className="py-12">
                <PageContainer className="max-w-lg">
                    <div className="rounded-xl border border-line bg-white p-6">
                        <p className="text-sm text-muted">Signed in as {currentUserName}</p>
                        <h2 className="mt-2 text-xl font-semibold text-ink">Admin session active</h2>
                        <p className="mt-3 text-sm leading-6 text-muted">
                            You are currently signed in as Admin. Continuing to Candidate/Employer{' '}
                            {isRegistration ? 'registration' : 'login'} will sign out the current Admin session.
                        </p>
                        <form onSubmit={submit} className="mt-6">
                            <input type="hidden" name="destination" value={data.destination} />
                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white hover:opacity-95 disabled:bg-disabled"
                            >
                                {isRegistration ? 'Continue to Account Registration' : 'Continue to Account Login'}
                            </button>
                        </form>
                        <Link
                            href="/admin"
                            className="mt-4 block text-center text-sm font-medium text-brand hover:underline"
                        >
                            Return to Admin
                        </Link>
                    </div>
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
