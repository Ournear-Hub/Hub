import { Head, Link, usePage } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import PageContainer from '@/Components/Public/PageContainer';
import type { SharedPageProps } from '@/types/page';

type AccountHomeProps = {
    role: 'candidate' | 'employer';
};

export default function AccountHome({ role }: AccountHomeProps) {
    const name = usePage<SharedPageProps>().props.auth.user?.name;

    return (
        <PublicLayout title="Account">
            <Head title="Account" />
            <section className="bg-brand py-14 text-white">
                <PageContainer>
                    <h1 className="text-4xl font-semibold">Account</h1>
                    <p className="mt-3 max-w-2xl text-white/80">Welcome{name ? `, ${name}` : ''}.</p>
                </PageContainer>
            </section>
            <section className="py-12">
                <PageContainer className="grid gap-4 sm:grid-cols-2">
                    <Link href="/account/profile" className="rounded-xl border border-line bg-white p-6 hover:border-brand">
                        <h2 className="text-lg font-semibold text-ink">Profile</h2>
                        <p className="mt-2 text-sm text-muted">Update your account details.</p>
                    </Link>
                    {role === 'candidate' ? (
                        <Link
                            href="/account/applications"
                            className="rounded-xl border border-line bg-white p-6 hover:border-brand"
                        >
                            <h2 className="text-lg font-semibold text-ink">Applications</h2>
                            <p className="mt-2 text-sm text-muted">View applications linked to this account.</p>
                        </Link>
                    ) : (
                        <Link
                            href="/account/enquiries"
                            className="rounded-xl border border-line bg-white p-6 hover:border-brand"
                        >
                            <h2 className="text-lg font-semibold text-ink">Enquiries</h2>
                            <p className="mt-2 text-sm text-muted">View employer enquiries linked to this account.</p>
                        </Link>
                    )}
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
