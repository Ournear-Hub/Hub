import type { Paginator } from '@/types/jobs';
import type { JobFormLookups } from '@/types/masterData';

export type AdminCandidate = {
    id: number;
    business_id: string | null;
    name: string | null;
    email: string | null;
    mobile: string | null;
    location: string | null;
    experience: string | null;
    qualification: string | null;
    location_id: number | null;
    experience_range_id: number | null;
    qualification_id: number | null;
    applications_count: number;
    created_at: string | null;
    updated_at: string | null;
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
    job: {
        id: number;
        title: string;
        industry?: string | null;
        location?: string | null;
    } | null;
};

export type CandidateActivity = {
    id: number;
    title: string;
    meta: string;
};

export type CandidateWorkspaceProps = {
    candidates: Paginator<AdminCandidate>;
    selected: AdminCandidate | null;
    applications: CandidateApplication[];
    activities: CandidateActivity[];
    filters: {
        search: string;
        location_id: string;
        experience_range_id: string;
        qualification_id: string;
        date_from: string;
        date_to: string;
        candidate: string;
    };
    lookups: JobFormLookups;
};
