import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import PageContainer from '@/Components/Public/PageContainer';
import Alert from '@/Components/molecules/Alert';
import BackNavLink from '@/Components/molecules/BackNavLink';

type Props = {
    pageTitle: string;
    heading: string;
    description: string;
    statusMessage?: string | null;
    children: ReactNode;
};

export default function AccountAuthShell({ pageTitle, heading, description, statusMessage, children }: Props) {
    return (
        <PublicLayout title={pageTitle}>
            <Head title={pageTitle} />
            <section className="bg-brand py-10 text-white sm:py-12">
                <PageContainer className="max-w-lg">
                    <h1 className="text-3xl font-semibold sm:text-4xl">{heading}</h1>
                    <p className="mt-2 text-sm text-white/85 sm:text-base">{description}</p>
                </PageContainer>
            </section>
            <section className="py-10 sm:py-12">
                <PageContainer className="max-w-md">
                    <BackNavLink href="/" label="Back to home" className="mb-4" />
                    {statusMessage ? (
                        <Alert variant="success" className="mb-4">
                            {statusMessage}
                        </Alert>
                    ) : null}
                    {children}
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
