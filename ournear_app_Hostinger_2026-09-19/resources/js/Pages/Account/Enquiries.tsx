import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import PageContainer from '@/Components/Public/PageContainer';
import Pagination from '@/Components/Pagination';
import type { Paginator } from '@/types/jobs';

type AccountEnquiry = {
    id: number;
    company_name: string;
    status: string;
    project_location: string;
    workforce_type: string;
    created_at: string | null;
};

type EnquiriesProps = {
    enquiries: Paginator<AccountEnquiry>;
};

export default function Enquiries({ enquiries }: EnquiriesProps) {
    return (
        <PublicLayout title="Enquiries">
            <Head title="Enquiries" />
            <section className="bg-brand py-14 text-white">
                <PageContainer>
                    <h1 className="text-4xl font-semibold">Employer enquiries</h1>
                    <p className="mt-3 max-w-2xl text-white/80">Enquiries submitted while you were logged in.</p>
                </PageContainer>
            </section>
            <section className="py-12">
                <PageContainer>
                    {enquiries.data.length === 0 ? (
                        <p className="text-sm text-muted">No enquiries are linked to this account yet.</p>
                    ) : (
                        <>
                            <ul className="space-y-4">
                                {enquiries.data.map((enquiry) => (
                                    <li key={enquiry.id} className="rounded-xl border border-line bg-white p-6">
                                        <h2 className="text-lg font-semibold text-ink">{enquiry.company_name}</h2>
                                        <p className="mt-1 text-sm text-muted">
                                            {enquiry.project_location} · {enquiry.workforce_type} · Status:{' '}
                                            {enquiry.status}
                                        </p>
                                        {enquiry.created_at && (
                                            <p className="mt-1 text-sm text-muted">Submitted {enquiry.created_at}</p>
                                        )}
                                    </li>
                                ))}
                            </ul>
                            <Pagination links={enquiries.links} />
                        </>
                    )}
                    <p className="mt-6 text-sm">
                        <Link href="/account" className="font-medium text-brand hover:underline">
                            Back to account
                        </Link>
                    </p>
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
