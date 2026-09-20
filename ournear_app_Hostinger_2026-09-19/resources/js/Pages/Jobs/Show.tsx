import { Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import Button from '@/Components/Public/Button';
import PageContainer from '@/Components/Public/PageContainer';
import type { PublicJobDetail } from '@/types/jobs';
import type { SiteContent } from '@/types/public';

type JobShowProps = {
    content: SiteContent;
    job: PublicJobDetail;
};

function Block({ title, body }: { title: string; body: string | null }) {
    if (!body) {
        return null;
    }

    return (
        <section className="mt-8">
            <h2 className="text-lg font-semibold text-ink">{title}</h2>
            <p className="mt-2 whitespace-pre-line text-sm leading-6 text-muted">{body}</p>
        </section>
    );
}

export default function JobShow({ content, job }: JobShowProps) {
    return (
        <PublicLayout title={job.title}>
            <section className="bg-brand py-14 text-white">
                <PageContainer>
                    <p className="text-sm font-medium text-accent">{content.tagline}</p>
                    {job.featured && (
                        <p className="mt-2 text-xs uppercase tracking-wide text-white/70">Featured role</p>
                    )}
                    <h1 className="mt-3 text-4xl font-semibold">{job.title}</h1>
                    <p className="mt-2 text-white/80">{job.project}</p>
                </PageContainer>
            </section>
            <section className="py-12">
                <PageContainer className="grid gap-8 lg:grid-cols-3">
                    <div className="lg:col-span-2 rounded-xl border border-line bg-white p-6">
                        <h2 className="text-xl font-semibold text-ink">About this role</h2>
                        <p className="mt-3 whitespace-pre-line text-sm leading-6 text-muted">{job.description}</p>
                        <Block title="Responsibilities" body={job.responsibilities} />
                        <Block title="Requirements" body={job.requirements} />
                        <Block title="What we offer" body={job.what_we_offer} />
                    </div>
                    <aside className="rounded-xl border border-line bg-white p-6">
                        <h2 className="text-lg font-semibold text-ink">Job information</h2>
                        <dl className="mt-4 space-y-2 text-sm text-muted">
                            <div>
                                <dt className="font-medium text-ink">Industry</dt>
                                <dd>{job.industry}</dd>
                            </div>
                            <div>
                                <dt className="font-medium text-ink">Category</dt>
                                <dd>{job.category}</dd>
                            </div>
                            <div>
                                <dt className="font-medium text-ink">Location</dt>
                                <dd>{job.location}</dd>
                            </div>
                            <div>
                                <dt className="font-medium text-ink">Employment type</dt>
                                <dd>{job.employment_type}</dd>
                            </div>
                            <div>
                                <dt className="font-medium text-ink">Experience</dt>
                                <dd>{job.experience}</dd>
                            </div>
                            <div>
                                <dt className="font-medium text-ink">Salary</dt>
                                <dd>{job.salary ?? 'Competitive'}</dd>
                            </div>
                            <div>
                                <dt className="font-medium text-ink">Vacancies</dt>
                                <dd>{job.vacancy}</dd>
                            </div>
                            <div>
                                <dt className="font-medium text-ink">Posted</dt>
                                <dd>{job.posted_date ?? '—'}</dd>
                            </div>
                            <div>
                                <dt className="font-medium text-ink">Closing date</dt>
                                <dd>{job.closing_date ?? 'Open'}</dd>
                            </div>
                        </dl>
                        {job.accepts_applications ? (
                            <div className="mt-6">
                                <Button href={`/jobs/${job.slug}/apply`}>Apply now</Button>
                            </div>
                        ) : (
                            <p className="mt-6 text-sm text-danger">This role is not accepting applications.</p>
                        )}
                        <Link href="/jobs" className="mt-4 inline-block text-sm font-medium text-brand">
                            Back to jobs
                        </Link>
                    </aside>
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
