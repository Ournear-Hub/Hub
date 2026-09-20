import type { Paginator } from '@/types/jobs';

export type ApplicationJobSummary = {
    id: number;
    business_id: string | null;
    title: string;
    slug?: string;
    industry?: string | null;
    category?: string | null;
    location?: string | null;
    employment_type?: string | null;
    experience?: string | null;
    status_label?: string;
    employer?: {
        id: number;
        business_id: string | null;
        company_name: string;
        contact_person?: string | null;
        workspace_url?: string | null;
    } | null;
    workspace_url?: string | null;
};

export type ApplicationCandidateSummary = {
    id: number | null;
    business_id: string | null;
    name: string;
    mobile?: string | null;
    email?: string | null;
    location?: string | null;
    experience?: string | null;
    qualification?: string | null;
    workspace_url?: string | null;
};

export type AdminApplicationListItem = {
    id: number;
    business_id: string | null;
    full_name: string;
    mobile: string;
    email: string;
    current_location: string;
    experience: string;
    qualification: string;
    status: string;
    applied_at: string | null;
    updated_at: string | null;
    has_resume: boolean;
    resume_url: string;
    screening_decision: string;
    screening_decision_label: string;
    shortlisted_at: string | null;
    can_submit_to_employer?: boolean;
    submit_blocked_reason?: string | null;
    candidate: ApplicationCandidateSummary;
    job: ApplicationJobSummary | null;
};

export type ApplicationScreening = {
    decision: string;
    decision_label: string;
    notes: string | null;
    rejection_reason_id: number | null;
    rejection_reason: string | null;
    screened_by: string | null;
    screened_at: string | null;
    shortlisted_by: string | null;
    shortlisted_at: string | null;
};

export type AdminApplication = AdminApplicationListItem & {
    additional_information: string | null;
    consent: boolean;
    can_shortlist: boolean;
    can_mark_suitable: boolean;
    can_mark_not_suitable: boolean;
    internal_notes: Array<{
        body: string;
        author_id: number | null;
        author_name: string;
        created_at: string;
    }>;
    screening: ApplicationScreening;
    interview?: {
        id: number;
        scheduled_at: string | null;
        mode: string;
        location_or_link: string | null;
        interviewer_name: string;
        outcome: string;
        notes: string | null;
    } | null;
    activities?: Array<{
        id: number;
        type: string;
        body: string | null;
        from_status: string | null;
        to_status: string | null;
        actor_name: string | null;
        created_at: string | null;
    }>;
};

export type ApplicationFilters = {
    search: string;
    status: string;
    job_id: string;
    location_id: string;
    experience_range_id: string;
    qualification_id: string;
    location: string;
    experience: string;
    qualification: string;
    industry: string;
    date_from: string;
    date_to: string;
    shortlisted_from: string;
    shortlisted_to: string;
    screening_decision: string;
    candidate: string;
    application: string;
};

export type ApplicationFilterOptions = {
    jobs: Array<{ id: number; title: string; slug: string; business_id: string | null }>;
    locations: Array<{ id: number; name: string }>;
    experiences: Array<{ id: number; name: string }>;
    qualifications: Array<{ id: number; name: string }>;
    screening_decisions: Array<{ value: string; label: string }>;
};

export type RejectionReasonOption = {
    id: number;
    name: string;
    selectable?: boolean;
};

export type ApplicationWorkspaceProps = {
    applications: Paginator<AdminApplicationListItem>;
    selected: AdminApplication | null;
    filters: ApplicationFilters;
    options: ApplicationFilterOptions;
    statuses: string[];
    interviewModes: string[];
    interviewOutcomes: string[];
    rejectionReasons: RejectionReasonOption[];
};

export { type Paginator };
