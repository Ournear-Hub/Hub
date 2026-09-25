import type { Paginator } from '@/types/jobs';

export type ScreeningMatchFactor = {
    key: string;
    label: string;
    state: 'compared' | 'unavailable' | 'missing' | string;
    matched: boolean | null;
    contributed: boolean;
    candidate_value: string | null;
    job_value: string | null;
    note: string;
};

export type ScreeningMatch = {
    percent: number | null;
    state: 'scored' | 'not_enough_data' | string;
    label: string;
    disclaimer: string;
    comparable_count: number;
    matched_count: number;
    factors: ScreeningMatchFactor[];
};

export type ScreeningJobSummary = {
    id: number;
    business_id: string | null;
    title: string;
    slug?: string;
    employer?: {
        id: number;
        business_id: string | null;
        company_name: string;
        contact_person?: string | null;
        workspace_url?: string | null;
    } | null;
    location: string | null;
    vacancy: number | null;
    applications_count: number;
    pending_screening_count: number;
    matched_count: number;
    shortlisted_count: number;
    rejected_count: number;
    on_hold_count: number;
    pipeline: Array<{
        key: string;
        label: string;
        value: number;
        href: string;
    }>;
};

export type ScreeningStageRow = {
    key: string;
    label: string;
    available: boolean;
    completed: boolean;
    status: string;
    occurred_at: string | null;
    performed_by: string | null;
    remarks: string | null;
    evidence: string | null;
};

export type ScreeningTimelineItem = {
    id: string | number;
    occurred_at: string | null;
    action: string;
    status: string | null;
    actor_name: string | null;
    remarks: string | null;
};

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
    resume_view_url?: string | null;
    screening_decision: string;
    screening_decision_label: string;
    shortlisted_at: string | null;
    can_submit_to_employer?: boolean;
    submit_blocked_reason?: string | null;
    match?: ScreeningMatch;
    match_percent?: number | null;
    match_label?: string;
    screening_stage?: string;
    screening_stage_label?: string;
    last_screening_activity?: {
        at: string | null;
        action: string;
        actor_name: string | null;
    } | null;
    skills?: string | null;
    current_company?: string | null;
    designation?: string | null;
    current_salary?: string | null;
    expected_salary?: string | null;
    notice_period?: string | null;
    industry_experience?: string | null;
    industry?: string | null;
    screening_notes?: string | null;
    verification_status?: string;
    verification_label?: string;
    employer_submission_state?: string;
    employer_submission_label?: string;
    can_start_screening?: boolean;
    can_contact?: boolean;
    can_mark_called?: boolean;
    can_mark_interviewed?: boolean;
    can_hold?: boolean;
    candidate: ApplicationCandidateSummary;
    job: ApplicationJobSummary | null;
    employer?: {
        id: number;
        business_id: string | null;
        company_name: string;
        contact_person?: string | null;
        email?: string | null;
        workspace_url?: string | null;
    } | null;
};

export type ApplicationScreening = {
    decision: string;
    decision_label: string;
    stage?: string;
    stage_label?: string;
    notes: string | null;
    rejection_reason_id: number | null;
    rejection_reason: string | null;
    screened_by: string | null;
    screened_at: string | null;
    shortlisted_by: string | null;
    shortlisted_at: string | null;
    held_by?: string | null;
    held_at?: string | null;
};

export type AdminApplication = AdminApplicationListItem & {
    additional_information: string | null;
    consent: boolean;
    can_shortlist: boolean;
    can_mark_suitable: boolean;
    can_mark_not_suitable: boolean;
    can_start_screening?: boolean;
    can_contact?: boolean;
    can_mark_called?: boolean;
    can_mark_interviewed?: boolean;
    can_hold?: boolean;
    screening_stages?: ScreeningStageRow[];
    screening_timeline?: ScreeningTimelineItem[];
    document_verifications?: Array<{ key: string; label: string; status: string }>;
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
    employer?: AdminApplicationListItem['employer'];
    submissions?: Array<{
        id: number;
        business_id: string | null;
        status: string;
        submitted_at?: string | null;
        employer?: { id: number; company_name: string; workspace_url?: string | null } | null;
        placement?: { id: number; business_id: string | null; status: string } | null;
    }>;
    placements?: Array<{
        id: number;
        business_id: string | null;
        status: string;
        expected_joining_date?: string | null;
        actual_joining_date?: string | null;
        employee?: { id: number; business_id: string | null } | null;
    }>;
    links?: {
        candidate?: string | null;
        job?: string | null;
        employer?: string | null;
        screening?: string | null;
        shortlist?: string | null;
        submissions?: string | null;
        placements?: string | null;
    };
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
    screening_stage: string;
    match_range: string;
    verification_state: string;
    candidate: string;
    employer_id: string;
    application: string;
};

export type ApplicationFilterOptions = {
    jobs: Array<{ id: number; title: string; slug: string; business_id: string | null }>;
    locations: Array<{ id: number; name: string }>;
    experiences: Array<{ id: number; name: string }>;
    qualifications: Array<{ id: number; name: string }>;
    screening_decisions: Array<{ value: string; label: string }>;
    screening_stages?: Array<{ value: string; label: string }>;
    employers?: Array<{ id: number; business_id: string | null; company_name?: string | null; label: string }>;
    candidates?: Array<{ id: number; business_id: string | null; name?: string | null; label: string }>;
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
    jobSummary?: ScreeningJobSummary | null;
};

export { type Paginator };
