import { usePage } from '@inertiajs/react';
import Button from '@/Components/atoms/Button';
import PageContainer from '@/Components/Public/PageContainer';
import PublicPipelineStrip from '@/Components/Public/PublicPipelineStrip';
import type { SharedPageProps } from '@/types/page';
import { resolveHiringWorkspaceHref } from '@/utils/hiringWorkspaceEntry';

export default function HomeRecruitmentProcess() {
    const user = usePage<SharedPageProps>().props.auth.user;
    const knowMoreHref = resolveHiringWorkspaceHref(user);

    return (
        <section className="border-y border-line bg-white py-8 md:py-10">
            <PageContainer className="min-w-0">
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div className="min-w-0">
                        <h2 className="text-lg font-semibold text-ink sm:text-xl">OurNear recruitment process with ATS</h2>
                        <p className="mt-1 max-w-3xl text-sm text-muted">
                            Applications progress on one record—from apply and structured screening through interviews, offer and
                            joining. Status changes in the hiring workspace trigger in-app and email notifications where configured.
                        </p>
                    </div>
                    <Button surface="public" href={knowMoreHref} variant="secondary" size="sm" className="shrink-0">
                        Know more
                    </Button>
                </div>
                <PublicPipelineStrip tone="light" className="mt-6" />
                <p className="mt-4 text-xs text-muted">
                    Automated screening uses structured profile and job requirements—not AI scoring. Employers manage transitions in
                    the hiring workspace; candidates see updates on their applications.
                </p>
            </PageContainer>
        </section>
    );
}
