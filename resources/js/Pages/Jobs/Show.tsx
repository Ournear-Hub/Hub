import PublicLayout from '@/Layouts/PublicLayout';
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
            <PageContainer className="min-w-0 max-w-4xl py-6 lg:py-8">
                <JobDetail job={job} backHref="/jobs" layout="page" />
                {similar_jobs.length > 0 ? (
                    <section className="mt-10">
                        <h2 className="text-lg font-semibold text-ink">Similar jobs</h2>
                        <div className="mt-4 flex flex-col gap-2">
                            {similar_jobs.map((similar) => (
                                <JobCard key={similar.slug} job={similar} />
                            ))}
                        </div>
                    </section>
                ) : null}
            </PageContainer>
        </PublicLayout>
    );
}
