import { Link } from '@inertiajs/react';
import DetailHeader from '@/Components/organisms/DetailHeader';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, EmptyState } from '@/Components/Admin/ui';
import type { AdminInterview } from '@/types/applications';

type WorkTask = {
    id: number;
    title: string;
    status_label: string;
    due_at_label: string | null;
    application?: { full_name: string; workspace_url?: string | null } | null;
    job?: { title: string | null } | null;
    employer?: { company_name: string | null } | null;
    crm_lead?: { company_name: string | null } | null;
    partner?: { display_name: string | null } | null;
};

type WorkCrmLead = {
    id: number;
    company_name: string;
    assignee_name?: string | null;
};

type WorkCrmOpportunity = {
    id: number;
    title: string;
    status_label: string;
    lead?: { company_name?: string } | null;
};

type WorkActivity = {
    id: number;
    type: string;
    body: string | null;
    actor_name: string | null;
    created_at: string | null;
    application?: { full_name: string; workspace_url?: string | null } | null;
};

type ScreeningWorkItem = {
    id: number;
    business_id?: string | null;
    full_name: string;
    status?: string;
    applied_at?: string | null;
    job?: { title?: string | null } | null;
    workspace_url?: string | null;
};

type SubmissionWorkItem = {
    id: number;
    business_id?: string | null;
    status: string;
    response_status?: string;
    submitted_at?: string | null;
    candidate?: { name?: string | null } | null;
    job?: { title?: string | null } | null;
    employer?: { company_name?: string | null } | null;
    workspace_url?: string | null;
};

type WorkCounts = {
    overdue_tasks: number;
    open_tasks: number;
    todays_interviews: number;
    pending_screening: number;
    pending_employer_responses: number;
};

type Props = {
    tasks: WorkTask[];
    overdue_tasks?: WorkTask[];
    interviews: AdminInterview[];
    todays_interviews?: AdminInterview[];
    pending_screening?: ScreeningWorkItem[];
    pending_employer_responses?: SubmissionWorkItem[];
    activity: WorkActivity[];
    crm_leads?: WorkCrmLead[];
    crm_opportunities?: WorkCrmOpportunity[];
    pending_attributions?: Array<{
        id: number;
        partner?: { display_name?: string } | null;
        employer?: { company_name?: string | null } | null;
    }>;
    pending_interests?: Array<{
        id: number;
        investor?: { display_name?: string } | null;
        opportunity?: { title?: string } | null;
        status_label?: string;
    }>;
    recent_referrals?: Array<{
        id: number;
        code?: string | null;
        conversion_label?: string;
        referred_name?: string | null;
    }>;
    can_view_screening_queue?: boolean;
    can_view_submission_queue?: boolean;
    counts?: WorkCounts;
};

function CountCard({ label, value, href }: { label: string; value: number; href: string }) {
    return (
        <Card>
            <Link href={href} className="block">
                <p className="text-sm text-muted">{label}</p>
                <p className="mt-1 text-2xl font-semibold text-ink">{value}</p>
            </Link>
        </Card>
    );
}

function taskContext(task: WorkTask): string {
    return [
        task.status_label,
        task.due_at_label ? `due ${task.due_at_label}` : null,
        task.application?.full_name,
        task.job?.title,
        task.employer?.company_name,
        task.crm_lead?.company_name,
        task.partner?.display_name,
    ]
        .filter(Boolean)
        .join(' · ');
}

export default function WorkIndex({
    tasks,
    overdue_tasks = [],
    interviews,
    todays_interviews = [],
    pending_screening = [],
    pending_employer_responses = [],
    activity,
    crm_leads = [],
    crm_opportunities = [],
    pending_attributions = [],
    pending_interests = [],
    recent_referrals = [],
    can_view_screening_queue = false,
    can_view_submission_queue = false,
    counts = {
        overdue_tasks: overdue_tasks.length,
        open_tasks: tasks.length,
        todays_interviews: todays_interviews.length,
        pending_screening: pending_screening.length,
        pending_employer_responses: pending_employer_responses.length,
    },
}: Props) {
    return (
        <AdminLayout title="My Work" breadcrumbs={[{ label: 'Admin', href: '/admin' }, { label: 'My Work' }]}>
            <div className="space-y-6">
                <DetailHeader
                    headingLevel={2}
                    title="Your assigned recruitment work"
                    description="Your assigned tasks, interviews, CRM work, and recent activity you recorded."
                />

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <CountCard label="Overdue tasks" value={counts.overdue_tasks} href="/admin/tasks?due=overdue" />
                    <CountCard label="Today’s interviews" value={counts.todays_interviews} href="/admin/interviews?scope=today" />
                    {can_view_screening_queue ? (
                        <CountCard
                            label="Pending screening"
                            value={counts.pending_screening}
                            href="/admin/screening?scope=pending"
                        />
                    ) : null}
                    {can_view_submission_queue ? (
                        <CountCard
                            label="Pending employer responses"
                            value={counts.pending_employer_responses}
                            href="/admin/submissions?scope=pending"
                        />
                    ) : null}
                </div>

                <div className="grid gap-4 xl:grid-cols-2">
                    <Card>
                        <div className="mb-3 flex items-center justify-between">
                            <h2 className="text-base font-semibold text-ink">Overdue tasks</h2>
                            <Link href="/admin/tasks?due=overdue" className="text-sm font-medium text-brand">
                                Open overdue
                            </Link>
                        </div>
                        {overdue_tasks.length === 0 ? (
                            <EmptyState title="No overdue tasks" description="Open tasks assigned to you that are past due will appear here." />
                        ) : (
                            <ul className="space-y-3">
                                {overdue_tasks.map((task) => (
                                    <li key={task.id}>
                                        <p className="text-sm font-medium text-ink">{task.title}</p>
                                        <p className="text-xs text-muted">{taskContext(task)}</p>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card>

                    <Card>
                        <div className="mb-3 flex items-center justify-between">
                            <h2 className="text-base font-semibold text-ink">Today’s interviews</h2>
                            <Link href="/admin/interviews?scope=today" className="text-sm font-medium text-brand">
                                Today
                            </Link>
                        </div>
                        {todays_interviews.length === 0 ? (
                            <EmptyState title="No interviews today" description="Scheduled or confirmed interviews for you today will appear here." />
                        ) : (
                            <ul className="space-y-3">
                                {todays_interviews.map((interview) => (
                                    <li key={interview.id}>
                                        <p className="text-sm font-medium text-ink">
                                            {interview.application?.full_name ?? `Interview #${interview.sort_order ?? interview.id}`}
                                        </p>
                                        <p className="text-xs text-muted">
                                            {interview.scheduled_at_label ?? interview.scheduled_at} · {interview.lifecycle_label ?? interview.lifecycle_status}
                                        </p>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card>

                    {can_view_screening_queue ? (
                        <Card>
                            <div className="mb-3 flex items-center justify-between">
                                <h2 className="text-base font-semibold text-ink">Pending screening</h2>
                                <Link href="/admin/screening?scope=pending" className="text-sm font-medium text-brand">
                                    Screening queue
                                </Link>
                            </div>
                            {pending_screening.length === 0 ? (
                                <EmptyState title="No pending screening" description="Applications waiting for a screening decision will appear here." />
                            ) : (
                                <ul className="space-y-3">
                                    {pending_screening.map((application) => (
                                        <li key={application.id}>
                                            {application.workspace_url ? (
                                                <Link href={application.workspace_url} className="text-sm font-medium text-brand">
                                                    {application.full_name}
                                                </Link>
                                            ) : (
                                                <p className="text-sm font-medium text-ink">{application.full_name}</p>
                                            )}
                                            <p className="text-xs text-muted">
                                                {application.job?.title ?? 'Job'}
                                                {application.applied_at ? ` · ${application.applied_at}` : ''}
                                            </p>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </Card>
                    ) : null}

                    {can_view_submission_queue ? (
                        <Card>
                            <div className="mb-3 flex items-center justify-between">
                                <h2 className="text-base font-semibold text-ink">Pending employer responses</h2>
                                <Link href="/admin/submissions?scope=pending" className="text-sm font-medium text-brand">
                                    Submissions
                                </Link>
                            </div>
                            {pending_employer_responses.length === 0 ? (
                                <EmptyState
                                    title="No pending employer responses"
                                    description="Submitted and under-review employer submissions will appear here."
                                />
                            ) : (
                                <ul className="space-y-3">
                                    {pending_employer_responses.map((submission) => (
                                        <li key={submission.id}>
                                            {submission.workspace_url ? (
                                                <Link href={submission.workspace_url} className="text-sm font-medium text-brand">
                                                    {submission.candidate?.name ?? submission.business_id ?? `Submission #${submission.id}`}
                                                </Link>
                                            ) : (
                                                <p className="text-sm font-medium text-ink">
                                                    {submission.candidate?.name ?? submission.business_id ?? `Submission #${submission.id}`}
                                                </p>
                                            )}
                                            <p className="text-xs text-muted">
                                                {submission.job?.title ?? 'Job'}
                                                {submission.employer?.company_name ? ` · ${submission.employer.company_name}` : ''}
                                                {` · ${submission.response_status ?? submission.status}`}
                                                {submission.submitted_at ? ` · ${submission.submitted_at}` : ''}
                                            </p>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </Card>
                    ) : null}
                </div>

                <div className="grid gap-4 xl:grid-cols-3">
                    <Card>
                        <div className="mb-3 flex items-center justify-between">
                            <h2 className="text-base font-semibold text-ink">My tasks</h2>
                            <Link href="/admin/tasks" className="text-sm font-medium text-brand">
                                All tasks
                            </Link>
                        </div>
                        {tasks.length === 0 ? (
                            <EmptyState title="No open tasks assigned to you" description="New tasks assigned to you will appear here." />
                        ) : (
                            <ul className="space-y-3">
                                {tasks.map((task) => (
                                    <li key={task.id}>
                                        <p className="text-sm font-medium text-ink">{task.title}</p>
                                        <p className="text-xs text-muted">{taskContext(task)}</p>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card>

                    <Card>
                        <div className="mb-3 flex items-center justify-between">
                            <h2 className="text-base font-semibold text-ink">My interviews</h2>
                            <Link href="/admin/interviews?scope=mine" className="text-sm font-medium text-brand">
                                Mine
                            </Link>
                        </div>
                        {interviews.length === 0 ? (
                            <EmptyState title="No upcoming interviews assigned to you" description="Scheduled or confirmed interviews you created or host will appear here." />
                        ) : (
                            <ul className="space-y-3">
                                {interviews.map((interview) => (
                                    <li key={interview.id}>
                                        <p className="text-sm font-medium text-ink">
                                            {interview.application?.full_name ?? `Interview #${interview.sort_order ?? interview.id}`}
                                        </p>
                                        <p className="text-xs text-muted">
                                            {interview.scheduled_at_label ?? interview.scheduled_at} · {interview.lifecycle_label ?? interview.lifecycle_status}
                                        </p>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card>

                    <Card>
                        <h2 className="mb-3 text-base font-semibold text-ink">My recent activity</h2>
                        {activity.length === 0 ? (
                            <EmptyState title="No recent activity" description="Recruitment activity you record will appear here." />
                        ) : (
                            <ul className="space-y-3">
                                {activity.map((item) => (
                                    <li key={item.id}>
                                        <p className="text-sm font-medium text-ink">{item.body || item.type.replace(/_/g, ' ')}</p>
                                        <p className="text-xs text-muted">
                                            {item.created_at}
                                            {item.application?.full_name ? ` · ${item.application.full_name}` : ''}
                                        </p>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card>
                </div>

                <div className="grid gap-4 xl:grid-cols-2">
                    <Card>
                        <div className="mb-3 flex items-center justify-between">
                            <h2 className="text-base font-semibold text-ink">My CRM leads</h2>
                            <Link href="/admin/crm" className="text-sm font-medium text-brand">
                                All CRM
                            </Link>
                        </div>
                        {crm_leads.length === 0 ? (
                            <p className="text-sm text-muted">No CRM leads assigned to you.</p>
                        ) : (
                            <ul className="space-y-3">
                                {crm_leads.map((lead) => (
                                    <li key={lead.id}>
                                        <p className="text-sm font-medium text-ink">{lead.company_name}</p>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card>
                    <Card>
                        <h2 className="mb-3 text-base font-semibold text-ink">My open CRM opportunities</h2>
                        {crm_opportunities.length === 0 ? (
                            <p className="text-sm text-muted">No open CRM opportunities assigned to you.</p>
                        ) : (
                            <ul className="space-y-3">
                                {crm_opportunities.map((opportunity) => (
                                    <li key={opportunity.id}>
                                        <p className="text-sm font-medium text-ink">{opportunity.title}</p>
                                        <p className="text-xs text-muted">
                                            {opportunity.status_label}
                                            {opportunity.lead?.company_name ? ` · ${opportunity.lead.company_name}` : ''}
                                        </p>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card>
                    <Card>
                        <div className="mb-3 flex items-center justify-between">
                            <h2 className="text-base font-semibold text-ink">My pending investor interests</h2>
                            <Link href="/admin/investors?tab=interests" className="text-sm font-medium text-brand">
                                Investors
                            </Link>
                        </div>
                        {pending_interests.length === 0 ? (
                            <p className="text-sm text-muted">No open investor interests you recorded.</p>
                        ) : (
                            <ul className="space-y-3">
                                {pending_interests.map((interest) => (
                                    <li key={interest.id}>
                                        <p className="text-sm font-medium text-ink">
                                            {interest.investor?.display_name ?? 'Investor'} → {interest.opportunity?.title ?? 'Opportunity'}
                                        </p>
                                        <p className="text-xs text-muted">{interest.status_label}</p>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card>
                    <Card>
                        <div className="mb-3 flex items-center justify-between">
                            <h2 className="text-base font-semibold text-ink">My referral conversions</h2>
                            <Link href="/admin/campaigns?tab=conversions" className="text-sm font-medium text-brand">
                                Campaigns
                            </Link>
                        </div>
                        {recent_referrals.length === 0 ? (
                            <p className="text-sm text-muted">No referral conversions you recorded.</p>
                        ) : (
                            <ul className="space-y-3">
                                {recent_referrals.map((referral) => (
                                    <li key={referral.id}>
                                        <p className="text-sm font-medium text-ink">
                                            {referral.code ?? 'Referral'} · {referral.conversion_label}
                                        </p>
                                        <p className="text-xs text-muted">{referral.referred_name}</p>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card>
                    <Card>
                        <div className="mb-3 flex items-center justify-between">
                            <h2 className="text-base font-semibold text-ink">My pending attributions</h2>
                            <Link href="/admin/partners?tab=attributions" className="text-sm font-medium text-brand">
                                Partners
                            </Link>
                        </div>
                        {pending_attributions.length === 0 ? (
                            <p className="text-sm text-muted">No pending attributions you created.</p>
                        ) : (
                            <ul className="space-y-3">
                                {pending_attributions.map((attribution) => (
                                    <li key={attribution.id}>
                                        <p className="text-sm font-medium text-ink">
                                            {attribution.partner?.display_name ?? 'Partner'} → {attribution.employer?.company_name ?? 'Employer'}
                                        </p>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}
