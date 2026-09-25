import PublicLayout from '@/Layouts/PublicLayout';
import JobDetail from '@/Components/Public/JobDetail';
import PageContainer from '@/Components/Public/PageContainer';
import type { PublicJobDetail } from '@/types/jobs';
import type { SiteContent } from '@/types/public';

type JobShowProps = {
    content: SiteContent;
    job: PublicJobDetail;
};

export default function JobShow({ content, job }: JobShowProps) {
    return (
        <PublicLayout title={job.title}>
            <section className="bg-brand py-10 text-white">
                <PageContainer>
                    <p className="text-sm font-medium text-accent">{content.tagline}</p>
                    <h1 className="mt-3 text-3xl font-semibold">{job.title}</h1>
                </PageContainer>
            </section>
            <section className="py-8">
                <PageContainer>
                    <JobDetail job={job} backHref="/jobs" />
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
