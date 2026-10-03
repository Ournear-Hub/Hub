import type { Paginator } from '@/types/jobs';

export type MasterTab = {
    id: string;
    label: string;
};

export type MasterOption = {
    id: number;
    name: string;
    is_active: boolean;
    industry_id?: number;
    industry_name?: string;
    selectable?: boolean;
    job_category_id?: number;
};

export type JobFormLookups = {
    industries: MasterOption[];
    categories: MasterOption[];
    job_titles: MasterOption[];
    locations: MasterOption[];
    employment_types: MasterOption[];
    experience_ranges: MasterOption[];
    qualifications: MasterOption[];
    notice_periods?: Array<{ value: string; label: string }>;
    employers?: Array<{
        id: number;
        business_id: string | null;
        company_name: string | null;
        contact_person: string | null;
        email?: string | null;
        label: string;
    }>;
};

export type MasterChange = {
    id: number;
    title: string;
    meta: string;
};

export type MasterRecord = {
    id: number;
    name: string;
    is_active: boolean;
    is_system: boolean;
    sort_order: number;
    usage_count: number;
    can_edit_name: boolean;
    can_deactivate: boolean;
    can_activate: boolean;
    status_label: string;
    recent_changes: MasterChange[];
    description?: string | null;
    code?: string;
    industry_id?: number | null;
    industry_name?: string | null;
    job_category_id?: number | null;
    category_name?: string | null;
    parent_active?: boolean;
};

export type MasterWorkspaceMeta = {
    label: string;
    singular: string;
    allows_create: boolean;
    has_description: boolean;
    has_industry_parent: boolean;
    has_category_parent: boolean;
    has_code: boolean;
};

export type MasterWorkspaceProps = {
    tab: string;
    tabs: MasterTab[];
    meta: MasterWorkspaceMeta;
    records: Paginator<MasterRecord>;
    filters: {
        search: string;
        status: string;
        industry_id: string;
        job_category_id: string;
    };
    options: {
        industries: MasterOption[];
        categories: MasterOption[];
    };
};
