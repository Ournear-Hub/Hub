import type { Paginator } from '@/types/jobs';
import type { JobFormLookups } from '@/types/masterData';

export type AdminCandidate = {
    id: number;
    business_id: string | null;
    user_id?: number | null;
    user_business_id?: string | null;
    user_360_url?: string | null;
    name: string | null;
    email: string | null;
    mobile: string | null;
    photo_url?: string | null;
    location: string | null;
    experience: string | null;
    qualification: string | null;
    location_id: number | null;
    experience_range_id: number | null;
    qualification_id: number | null;
    account_status?: string | null;
    account_status_label?: string | null;
    email_verified?: boolean;
    current_company?: string | null;
    current_designation?: string | null;
    current_salary?: string | null;
    expected_salary?: string | null;
    skills?: string | null;
    notice_period?: string | null;
    present_position?: string | null;
    notice_period_value?: string | null;
    industry_id?: number | null;
    industry?: string | null;
    job_category_id?: number | null;
    functional_area?: string | null;
    employment_preference?: string | null;
    skills_list?: Array<{ id: number; name: string }>;
    employment_histories?: Array<{
        id: number;
        company: string;
        designation: string;
        industry?: string | null;
        started_on?: string | null;
        ended_on?: string | null;
        is_current?: boolean;
        description?: string | null;
    }>;
    preferred_locations?: string[];
    preferred_employment_types?: string[];
    applications_count: number;
    created_at: string | null;
    updated_at: string | null;
    links?: {
        applications?: string | null;
        jobs?: string | null;
        screening?: string | null;
        user?: string | null;
    };
};

export type CandidateApplication = {
    id: number;
    business_id?: string | null;
    full_name: string;
    status: string;
    applied_at: string | null;
    updated_at?: string | null;
    screening_decision_label?: string;
    has_resume?: boolean;
    resume_url?: string;
    resume_view_url?: string | null;
    job: {
        id: number;
        title: string;
        industry?: string | null;
        location?: string | null;
        slug?: string;
        business_id?: string | null;
    } | null;
};

export type CandidateActivity = {
    id: number;
    title: string;
    meta: string;
};

export type CandidateRecruitment = {
    jobs: Array<{ id: number; business_id: string | null; title: string; slug: string; workspace_url: string }>;
    applications: CandidateApplication[];
    screening: Array<{ id: number; business_id: string | null; job_title?: string | null; decision: string; href: string }>;
    shortlisted: Array<{ id: number; business_id: string | null; job_title?: string | null; href: string }>;
    interviews: Array<{
        id: number | null;
        application_id: number;
        job_title?: string | null;
        scheduled_at?: string | null;
        outcome?: string | null;
        href: string;
    }>;
    submissions: Array<Record<string, unknown>>;
    selections: Array<Record<string, unknown>>;
    placements: Array<Record<string, unknown>>;
};

export type CandidateLibraryFile = {
    id: number;
    business_id?: string | null;
    title?: string;
    label?: string;
    original_filename?: string;
    document_type?: string;
    issue_date?: string | null;
    expiry_date?: string | null;
    status?: string | null;
    visibility?: string | null;
    verification_status?: string | null;
    is_primary?: boolean;
    view_url: string;
    download_url: string;
};

export type CandidateDocuments = {
    resumes: Array<{
        id: number;
        label: string;
        application_id: string | null;
        view_url: string;
        download_url: string;
    }>;
    library_resumes?: CandidateLibraryFile[];
    certificates: CandidateLibraryFile[];
    licences: CandidateLibraryFile[];
    qualifications?: CandidateLibraryFile[];
    payslips: CandidateLibraryFile[];
    experience: CandidateLibraryFile[];
    other: CandidateLibraryFile[];
};

export type CandidateWorkspaceProps = {
    candidates: Paginator<AdminCandidate>;
    selected: AdminCandidate | null;
    applications: CandidateApplication[];
    recruitment?: CandidateRecruitment | null;
    documents?: CandidateDocuments | null;
    activities: CandidateActivity[];
    filters: {
        search: string;
        location_id: string;
        experience_range_id: string;
        qualification_id: string;
        date_from: string;
        date_to: string;
        job_id: string;
        status: string;
        verification: string;
        candidate: string;
    };
    lookups: JobFormLookups;
    jobs?: Array<{ id: number; title: string; business_id: string | null }>;
};
