import { Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import PageContainer from '@/Components/Public/PageContainer';
import type { SiteContent } from '@/types/public';

type Confirmation = {
    business_id: string;
    name: string;
    email: string;
    preferred_location: string;
    primary_skill: string;
    submitted_at: string | null;
};

type ReceivedProps = {
    content: SiteContent;
    confirmation: Confirmation;
    message?: string | null;
};

export default function FreelancersReceived({ content, confirmation, message }: ReceivedProps) {
    return (
        <PublicLayout title="Freelancer partnership received">
            <section className="bg-brand py-14 text-white">
                <PageContainer>
                    <p className="text-sm font-medium text-accent">{content.tagline}</p>
                    <h1 className="mt-3 text-4xl font-semibold">Partnership registration received</h1>
                    <p className="mt-3 max-w-2xl text-white/80">
                        Your freelancer partnership registration has been submitted. No login or candidate account is required.
                    </p>
                </PageContainer>
            </section>
            <section className="py-12">
                <PageContainer className="max-w-2xl">
                    <div className="rounded-xl border border-success/20 bg-white p-6">
                        <h2 className="text-xl font-semibold text-ink">Partnership received</h2>
                        {message ? <p className="mt-3 text-sm text-muted">{message}</p> : null}
                        <dl className="mt-6 space-y-3 text-sm">
                            <div>
                                <dt className="text-muted">Freelancer ID</dt>
                                <dd className="mt-1 text-lg font-semibold text-ink">{confirmation.business_id}</dd>
                            </div>
                            <div>
                                <dt className="text-muted">Name</dt>
                                <dd className="mt-1 text-ink">{confirmation.name}</dd>
                            </div>
                            <div>
                                <dt className="text-muted">Email</dt>
                                <dd className="mt-1 text-ink">{confirmation.email}</dd>
                            </div>
                            <div>
                                <dt className="text-muted">Preferred location</dt>
                                <dd className="mt-1 text-ink">{confirmation.preferred_location}</dd>
                            </div>
                            <div>
                                <dt className="text-muted">Primary skill / profession</dt>
                                <dd className="mt-1 text-ink">{confirmation.primary_skill}</dd>
                            </div>
                            <div>
                                <dt className="text-muted">Submitted</dt>
                                <dd className="mt-1 text-ink">{confirmation.submitted_at ?? '—'}</dd>
                            </div>
                        </dl>
                        <p className="mt-6 rounded-md border border-line bg-surface px-3 py-2 text-sm text-ink">
                            Save your Freelancer ID for future reference.
                        </p>
                        <div className="mt-6 flex flex-wrap gap-4">
                            <Link href="/freelancers" className="text-sm font-medium text-brand">
                                Back to freelancers
                            </Link>
                            <Link href="/jobs" className="text-sm font-medium text-brand">
                                Browse jobs
                            </Link>
                        </div>
                    </div>
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
