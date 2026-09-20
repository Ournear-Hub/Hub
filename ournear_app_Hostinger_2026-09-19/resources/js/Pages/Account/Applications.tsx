import { Head, Link, router, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import PageContainer from '@/Components/Public/PageContainer';
import Pagination from '@/Components/Pagination';
import type { Paginator } from '@/types/jobs';
import type { SharedPageProps } from '@/types/page';

type AccountApplication = {
    id: number;
    status: string;
    applied_at: string | null;
    has_resume: boolean;
    resume_url: string;
    can_withdraw: boolean;
    withdraw_requires_confirmation: boolean;
    job: {
        title: string;
        slug: string;
        location: string;
    } | null;
};

type ApplicationsProps = {
    applications: Paginator<AccountApplication>;
};

export default function Applications({ applications }: ApplicationsProps) {
    const flash = usePage<SharedPageProps>().props.flash;

    const withdraw = (application: AccountApplication) => {
        const message = application.withdraw_requires_confirmation
            ? 'You have been selected. Are you sure you want to withdraw this application?'
            : 'Withdraw this application?';

        if (!window.confirm(message)) {
            return;
        }

        router.patch(`/account/applications/${application.id}/withdraw`);
    };

    const replaceResume = (application: AccountApplication, event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const form = event.currentTarget;
        const data = new FormData(form);
        router.post(`/account/applications/${application.id}/resume`, data, {
            forceFormData: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <PublicLayout title="Applications">
            <Head title="Applications" />
            <section className="bg-brand py-14 text-white">
                <PageContainer>
                    <h1 className="text-4xl font-semibold">Applications</h1>
                    <p className="mt-3 max-w-2xl text-white/80">Applications submitted while you were logged in.</p>
                </PageContainer>
            </section>
            <section className="py-12">
                <PageContainer>
                    {flash.success ? <p className="mb-4 text-sm text-success">{flash.success}</p> : null}
                    {applications.data.length === 0 ? (
                        <p className="text-sm text-muted">No applications are linked to this account yet.</p>
                    ) : (
                        <>
                            <ul className="space-y-4">
                                {applications.data.map((application) => (
                                    <li key={application.id} className="rounded-xl border border-line bg-white p-6">
                                        <h2 className="text-lg font-semibold text-ink">
                                            {application.job?.title ?? 'Role'}
                                        </h2>
                                        <p className="mt-1 text-sm text-muted">
                                            {application.job?.location} · Status: {application.status}
                                        </p>
                                        {application.applied_at && (
                                            <p className="mt-1 text-sm text-muted">Applied {application.applied_at}</p>
                                        )}
                                        <div className="mt-4 flex flex-wrap items-center gap-4 text-sm">
                                            {application.job?.slug ? (
                                                <Link
                                                    href={`/jobs/${application.job.slug}`}
                                                    className="font-medium text-brand"
                                                >
                                                    View job
                                                </Link>
                                            ) : null}
                                            {application.has_resume ? (
                                                <a href={application.resume_url} className="font-medium text-accent">
                                                    Download resume
                                                </a>
                                            ) : null}
                                            {application.can_withdraw ? (
                                                <button
                                                    type="button"
                                                    className="font-medium text-danger"
                                                    onClick={() => withdraw(application)}
                                                >
                                                    Withdraw
                                                </button>
                                            ) : null}
                                        </div>
                                        <form
                                            className="mt-4 space-y-2"
                                            onSubmit={(event) => replaceResume(application, event)}
                                        >
                                            <label className="block text-sm font-medium text-ink" htmlFor={`resume-${application.id}`}>
                                                Replace resume (PDF, DOC, or DOCX, max 5 MB)
                                            </label>
                                            <input
                                                id={`resume-${application.id}`}
                                                name="resume"
                                                type="file"
                                                required
                                                accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                                className="w-full text-sm text-ink"
                                            />
                                            <button
                                                type="submit"
                                                className="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white"
                                            >
                                                Upload resume
                                            </button>
                                        </form>
                                    </li>
                                ))}
                            </ul>
                            <Pagination links={applications.links} />
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
