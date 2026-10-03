import PublicLayout from '@/Layouts/PublicLayout';
import PublicPageHero from '@/Components/Public/PublicPageHero';
import JobCard from '@/Components/Public/JobCard';
import JobDetail from '@/Components/Public/JobDetail';
import PageContainer from '@/Components/Public/PageContainer';
import type { PublicJobCard, PublicJobDetail } from '@/types/jobs';

type JobShowProps = {
    job: PublicJobDetail;
    similar_jobs?: PublicJobCard[];
};

export default function JobShow({ job, similar_jobs = [] }: JobShowProps) {
    return (
        <PublicLayout title={job.title}>
            <PublicPageHero
                eyebrow="OurNear Jobs"
                title={job.title}
                description={[job.company_name, job.location].filter(Boolean).join(' · ')}
                breadcrumbs={[
                    { label: 'Home', href: '/' },
                    { label: 'Jobs', href: '/jobs' },
                    { label: job.title },
                ]}
            />
            <section className="py-6 md:py-8">
                <PageContainer className="min-w-0 max-w-4xl">
                    <JobDetail job={job} backHref="/jobs" layout="page" />
                    {similar_jobs.length > 0 ? (
                        <section className="mt-10">
                            <h2 className="text-lg font-semibold text-ink">Similar jobs</h2>
                            <div className="mt-4 grid gap-3 sm:grid-cols-2">
                                {similar_jobs.map((similar) => (
                                    <JobCard key={similar.slug} job={similar} list />
                                ))}
                            </div>
                        </section>
                    ) : null}
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
