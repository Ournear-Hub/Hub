import { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import BackNavLink from '@/Components/molecules/BackNavLink';
import ApplicationConfirmation from '@/Components/Public/ApplicationConfirmation';
import CompanyVerificationIndicator from '@/Components/Public/CompanyVerificationIndicator';
import JobActions from '@/Components/Public/JobActions';
import JobCompanyTabPanel from '@/Components/Public/JobCompanyTabPanel';
import Alert from '@/Components/UI/Alert';
import Tabs from '@/Components/molecules/Tabs';
import CompanyLogo from '@/Components/Public/CompanyLogo';
import OurNearIcon from '@/icons/OurNearIcon';
import type { PublicJobDetail } from '@/types/jobs';
import type { SharedPageProps } from '@/types/page';

function Block({ title, body }: { title: string; body: string | null | undefined }) {
    if (!body) {
        return null;
    }

    return (
        <section className="mt-6">
            <h2 className="text-base font-semibold text-ink">{title}</h2>
            <p className="mt-2 whitespace-pre-line text-sm leading-6 text-muted">{body}</p>
        </section>
    );
}

function MetaChip({ icon, text }: { icon: string; text: string }) {
    return (
        <span className="inline-flex min-w-0 items-center gap-1.5 text-sm text-muted">
            <OurNearIcon name={icon} size="sm" className="shrink-0 text-muted" />
            <span>{text}</span>
        </span>
    );
}

type JobDetailProps = {
    job: PublicJobDetail;
    backHref?: string;
    layout?: 'standalone' | 'pane' | 'page';
    openJobsLinkMode?: 'job_page' | 'jobs_index';
};

export default function JobDetail({ job, backHref, layout = 'standalone', openJobsLinkMode = 'job_page' }: JobDetailProps) {
    const { flash, errors } = usePage<SharedPageProps>().props;
    const confirmation = flash.application_confirmation;
    const success = flash.success;
    const isPane = layout === 'pane';
    const isPage = layout === 'page' || Boolean(backHref);
    const [activeTab, setActiveTab] = useState('details');

    const companyLabel = job.company_name?.trim() || 'Company';
    const showCompanyTab = () => {
        setActiveTab('company');
    };

    const logoSize = isPane ? 'h-12 w-12' : 'h-14 w-14';
    const titleClass = isPane ? 'text-lg font-semibold text-ink sm:text-xl' : 'text-xl font-semibold text-ink sm:text-2xl';

    const logoVisual = <CompanyLogo name={companyLabel} src={job.company_logo_url} sizeClass={logoSize} shape="rounded-lg" />;

    const logoNode =
        isPane ? (
            <button
                type="button"
                onClick={showCompanyTab}
                className="shrink-0 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/40"
                aria-label={`${companyLabel} — show company tab`}
            >
                {logoVisual}
            </button>
        ) : job.company_logo_url && job.company_url ? (
            <Link href={job.company_url} className="shrink-0">
                {logoVisual}
            </Link>
        ) : (
            logoVisual
        );

    const companyNameNode =
        isPane ? (
            <button
                type="button"
                onClick={showCompanyTab}
                className="text-sm font-medium text-ink hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/40"
            >
                {companyLabel}
            </button>
        ) : job.company_url ? (
            <Link href={job.company_url} className="text-sm font-medium text-brand hover:underline">
                {companyLabel}
            </Link>
        ) : (
            <p className="text-sm font-medium text-ink">{companyLabel}</p>
        );

    const metaRow = (
        <div className={`mt-2 flex min-w-0 flex-wrap gap-x-4 gap-y-1 ${isPane ? '' : 'hidden'}`}>
            {job.location ? <MetaChip icon="building" text={job.location} /> : null}
            {job.experience ? <MetaChip icon="briefcase" text={job.experience} /> : null}
            {job.employment_type ? <MetaChip icon="clipboard" text={job.employment_type} /> : null}
            {job.salary ? <MetaChip icon="file" text={job.salary} /> : null}
        </div>
    );

    const metaLine = !isPane ? (
        <p className="mt-1 text-sm text-muted">
            {[job.location, job.employment_type, job.experience, job.salary].filter(Boolean).join(' · ')}
        </p>
    ) : null;

    const alerts = (
        <>
            {errors.job ? (
                <Alert tone="error" title="Application" className="mt-3">
                    {Array.isArray(errors.job) ? errors.job[0] : errors.job}
                </Alert>
            ) : null}
            {success && !confirmation ? (
                <Alert tone="success" className="mt-3">
                    {success}
                </Alert>
            ) : null}
            {confirmation ? <ApplicationConfirmation confirmation={confirmation} className="mt-3" /> : null}
        </>
    );

    const header = (
        <div className={`min-w-0 shrink-0 ${isPane ? 'pb-3' : 'border-b border-line pb-4'}`}>
            <div
                className={`flex min-w-0 gap-3 ${isPane ? 'flex-col gap-4 sm:flex-row sm:items-start sm:justify-between' : 'items-start'}`}
            >
                <div className="flex min-w-0 flex-1 items-start gap-3">
                    {logoNode}
                    <div className="min-w-0 flex-1">
                        <h1 className={titleClass}>{job.title}</h1>
                        <div className="mt-1 flex flex-wrap items-center gap-2">
                            {companyNameNode}
                            <CompanyVerificationIndicator verified={job.company_verified === true} />
                        </div>
                        {metaRow}
                        {metaLine}
                    </div>
                </div>
                {isPane ? (
                    <div className="flex shrink-0 flex-wrap items-center justify-end gap-2 sm:max-w-[20rem] sm:pt-0.5">
                        <JobActions job={job} variant="detail" />
                    </div>
                ) : null}
            </div>
            {alerts}
        </div>
    );

    const primaryActions = isPane ? null : (
        <div className="mt-4 border-b border-line pb-4">
            <JobActions job={job} variant="detail" />
        </div>
    );

    const jobDetailsPanel = (
        <>
            <dl className="mt-2 grid gap-3 text-sm sm:grid-cols-2">
                {job.industry ? (
                    <div>
                        <dt className="font-medium text-ink">Industry</dt>
                        <dd className="mt-0.5 text-muted">{job.industry}</dd>
                    </div>
                ) : null}
                {job.category ? (
                    <div>
                        <dt className="font-medium text-ink">Category</dt>
                        <dd className="mt-0.5 text-muted">{job.category}</dd>
                    </div>
                ) : null}
                <div>
                    <dt className="font-medium text-ink">Vacancies</dt>
                    <dd className="mt-0.5 text-muted">{job.vacancy}</dd>
                </div>
                <div>
                    <dt className="font-medium text-ink">Posted</dt>
                    <dd className="mt-0.5 text-muted">{job.posted_label ?? '—'}</dd>
                </div>
                <div>
                    <dt className="font-medium text-ink">Closing</dt>
                    <dd className="mt-0.5 text-muted">{job.closing_label ?? (job.is_closed ? 'Closed' : 'Open')}</dd>
                </div>
                {job.joining_date ? (
                    <div>
                        <dt className="font-medium text-ink">Joining date</dt>
                        <dd className="mt-0.5 text-muted">{job.joining_date}</dd>
                    </div>
                ) : null}
                {job.site ? (
                    <div>
                        <dt className="font-medium text-ink">Site</dt>
                        <dd className="mt-0.5 text-muted">{job.site}</dd>
                    </div>
                ) : null}
            </dl>
            {(job.qualifications?.length ?? 0) > 0 || (job.skills?.length ?? 0) > 0 ? (
                <section className="mt-6 space-y-4">
                    {(job.qualifications?.length ?? 0) > 0 ? (
                        <div>
                            <h2 className="text-base font-semibold text-ink">Qualifications</h2>
                            <ul className="mt-2 flex flex-wrap gap-2">
                                {job.qualifications?.map((item) => (
                                    <li
                                        key={item}
                                        className="rounded-md border border-line bg-surface px-2.5 py-1 text-xs font-medium text-ink"
                                    >
                                        {item}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ) : null}
                    {(job.skills?.length ?? 0) > 0 ? (
                        <div>
                            <h2 className="text-base font-semibold text-ink">Skills</h2>
                            <ul className="mt-2 flex flex-wrap gap-2">
                                {job.skills?.map((item) => (
                                    <li
                                        key={item}
                                        className="rounded-md border border-line bg-surface px-2.5 py-1 text-xs font-medium text-ink"
                                    >
                                        {item}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ) : null}
                </section>
            ) : null}
            <section className="mt-6">
                <h2 className="text-base font-semibold text-ink">Job description</h2>
                <p className="mt-2 whitespace-pre-line text-sm leading-6 text-muted">{job.description}</p>
            </section>
            <Block title="Responsibilities" body={job.responsibilities} />
            <Block title="Requirements" body={job.requirements} />
            <Block title="Benefits / What we offer" body={job.what_we_offer} />
            <Block title="Accommodation" body={job.accommodation} />
        </>
    );

    const tabs = (
        <Tabs
            aria-label="Job content"
            tabs={[
                { id: 'details', label: 'Job Details' },
                { id: 'company', label: 'Company' },
            ]}
            active={activeTab}
            onChange={setActiveTab}
            panels={{
                details: jobDetailsPanel,
                company: <JobCompanyTabPanel company={job.company_profile} openJobsLinkMode={openJobsLinkMode} />,
            }}
            className={
                isPane
                    ? 'mt-0 flex min-h-0 flex-1 flex-col border-t border-line pt-3 [&_[role=tablist]]:shrink-0'
                    : 'mt-4'
            }
            panelsClassName={isPane ? 'min-h-0 flex-1 overflow-y-auto overscroll-y-contain pt-4' : ''}
        />
    );

    const shellClass = isPage
        ? ''
        : isPane
          ? 'flex h-full min-h-0 flex-col'
          : 'min-w-0 rounded-xl border border-line bg-white p-4 sm:p-6';

    return (
        <div className={shellClass}>
            {backHref ? <BackNavLink href={backHref} label="Back to jobs" className="mb-4" /> : null}
            {header}
            {primaryActions}
            {tabs}
        </div>
    );
}
