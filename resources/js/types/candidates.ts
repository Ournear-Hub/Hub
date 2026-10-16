import type { Paginator } from '@/types/jobs';
import type { JobFormLookups } from '@/types/masterData';
import type { StaffUserOption } from '@/types/applications';

export type AdminCandidate = {
    id: number;
    business_id: string | null;
    user_id?: number | null;
    user_business_id?: string | null;
    ournear_id?: string | null;
    profile_url?: string | null;
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
    willing_to_relocate?: boolean;
    availability?: string | null;
    public_share?: {
        enabled: boolean;
        bio: boolean;
        current_role: boolean;
        skills: boolean;
        history: boolean;
        certifications: boolean;
        languages: boolean;
    };
    certifications?: Array<{
        id: number;
        name: string;
        issuer: string | null;
        issued_on: string | null;
        expires_on: string | null;
        candidate_document_id: number | null;
    }>;
    languages?: Array<{
        id: number;
        name: string;
        proficiency: string | null;
        proficiency_label: string | null;
    }>;
    tags?: Array<{ id: number; name: string; slug: string; is_active: boolean }>;
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
    id: number | string;
    title: string;
    meta: string;
};

export type SavedTalentSearch = {
    id: number;
    name: string;
    filters: Record<string, string | number | boolean | null>;
};

export type CandidateRecruitment = {
    jobs: Array<{ id: number; business_id: string | null; title: string; slug: string; workspace_url: string }>;
    applications: CandidateApplication[];
    screening: Array<{ id: number; business_id: string | null; job_title?: string | null; decision: string; href: string }>;
    shortlisted: Array<{
        id: number;
        application_id: number;
        job_id: number | null;
        business_id: string | null;
        job_title?: string | null;
        href: string;
        default_recruiter_user_id: number | null;
        can_assign: boolean;
    }>;
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
        industry_id?: string;
        skill_id?: string;
        tag_id?: string;
        notice_period?: string;
        expected_salary_min?: string;
        expected_salary_max?: string;
        willing_to_relocate?: string;
        availability?: string;
        language?: string;
        candidate: string;
    };
    lookups: JobFormLookups & {
        skills?: Array<{ id: number; name: string }>;
        tags?: Array<{ id: number; name: string; slug: string; is_active: boolean }>;
        language_proficiencies?: Array<{ value: string; label: string }>;
    };
    jobs?: Array<{ id: number; title: string; business_id: string | null }>;
    saved_searches?: SavedTalentSearch[];
    can_manage?: boolean;
    can_assign_talent_pool?: boolean;
    talent_pool_recruiters?: Array<{ id: number; name: string }>;
    can_create_task?: boolean;
    staffUsers?: StaffUserOption[];
};
