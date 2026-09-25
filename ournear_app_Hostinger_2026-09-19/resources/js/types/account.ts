export type AccountLanguageOption = {
    value: string;
    label: string;
};

export type AccountCompletionItem = {
    key: string;
    label: string;
    complete: boolean;
    points: number;
};

export type AccountSkill = {
    id: number;
    name: string;
};

export type AccountEmploymentHistory = {
    id: number;
    company: string;
    designation: string;
    industry_id: number | null;
    industry?: string | null;
    started_on: string | null;
    ended_on: string | null;
    is_current: boolean;
    description: string | null;
};

export type AccountLookupOption = {
    id: number;
    name: string;
    is_active?: boolean;
    selectable?: boolean;
    industry_id?: number | null;
};

export type AccountNoticePeriodOption = {
    value: string;
    label: string;
};

export type AccountProfessionalProfile = {
    current_designation: string | null;
    current_company: string | null;
    industry_id: number | null;
    industry: string | null;
    job_category_id: number | null;
    functional_area: string | null;
    experience: string | null;
    experience_range_id: number | null;
    qualification: string | null;
    qualification_id: number | null;
    current_salary: string | number | null;
    expected_salary: string | number | null;
    notice_period: string | null;
    notice_period_label: string | null;
    employment_preference: string | null;
    skills: AccountSkill[];
    employment_histories: AccountEmploymentHistory[];
    preferred_location_ids: number[];
    preferred_locations: Array<{ id: number; name: string }>;
    preferred_employment_type_ids: number[];
    preferred_employment_types: Array<{ id: number; name: string }>;
};

export type AccountProfileLookups = {
    industries: AccountLookupOption[];
    categories: AccountLookupOption[];
    locations: AccountLookupOption[];
    employment_types: AccountLookupOption[];
    experience_ranges: AccountLookupOption[];
    qualifications: AccountLookupOption[];
    notice_periods: AccountNoticePeriodOption[];
};

export type AccountFileOption = {
    value: string;
    label: string;
};

export type AccountResume = {
    id: number;
    business_id: string | null;
    title: string;
    original_filename: string;
    extension: string;
    file_size: number;
    is_primary: boolean;
    visibility: string | null;
    visibility_label?: string | null;
    status: string | null;
    status_label?: string | null;
    created_at: string | null;
    download_url: string;
    view_url: string;
};

export type AccountDocument = {
    id: number;
    business_id: string | null;
    document_type: string | null;
    document_type_label?: string | null;
    title: string;
    original_filename: string;
    extension: string;
    file_size: number;
    issue_date: string | null;
    expiry_date: string | null;
    visibility: string | null;
    visibility_label?: string | null;
    status: string | null;
    status_label?: string | null;
    verification_status: string | null;
    verification_status_label?: string | null;
    created_at: string | null;
    download_url: string;
    view_url: string;
};

export type ResumeBuilderPreview = {
    personal: {
        name: string | null;
        email: string | null;
        mobile: string | null;
        location: string | null;
        bio: string | null;
        username: string | null;
    };
    professional: AccountProfessionalProfile | Record<string, unknown>;
    primary_resume?: AccountResume | null;
    note: string;
};

export type AccountDashboardResume = {
    title: string;
    status: string | null;
    status_label: string | null;
    is_primary: boolean;
    updated_at: string | null;
};

export type AccountDashboardDocumentCategory = {
    label: string;
    count: number;
};

export type AccountDashboardSavedJob = {
    title: string;
    slug: string | null;
    project: string | null;
    location: string | null;
    employment_type: string | null;
    status: 'open' | 'closed';
    status_label: string;
    saved_at: string | null;
    can_view: boolean;
};

export type AccountNotification = {
    id: string;
    event: string;
    event_label: string;
    title: string;
    body: string;
    url: string;
    read_at: string | null;
    created_at: string | null;
    is_unread: boolean;
};

export type AccountCommunicationChannel = {
    value: string;
    label: string;
    opted_in: boolean;
};

export type AccountDashboardApplication = {
    title: string;
    slug: string | null;
    status: string;
    applied_at: string | null;
    href?: string | null;
};

export type AccountCandidateDashboard = {
    header: {
        name: string;
        username: string | null;
        photo_url: string | null;
        designation: string | null;
        location: string | null;
        profile_completion: number;
        email_verified: boolean;
    };
    resume: {
        count: number;
        primary: AccountDashboardResume | null;
    };
    documents: {
        count: number;
        categories: AccountDashboardDocumentCategory[];
        verified_count: number;
    };
    saved_jobs: {
        count: number;
        recent: AccountDashboardSavedJob[];
    };
    applications: {
        count: number;
        recent: AccountDashboardApplication[];
    };
};

export type AccountEmployerDashboard = {
    company_name: string;
    business_id: string | null;
    photo_url: string | null;
    industry: string | null;
    jobs_count: number;
    active_jobs_count: number;
    applicants_count: number;
    eligibility: {
        eligible: boolean;
        email_verified: boolean;
        phone_verified: boolean;
        admin_approved: boolean;
        reason: string | null;
        next_action: string | null;
    };
};

export type AccountProfile = {
    name: string;
    username: string | null;
    email: string;
    mobile: string | null;
    location: string | null;
    bio: string | null;
    preferred_language: string;
    photo_url: string | null;
    email_verified: boolean;
    account_status: string;
    account_status_label: string;
    profile_completion: number;
    completion_items: AccountCompletionItem[];
    business_id: string | null;
    experience?: string | null;
    qualification?: string | null;
    company_name?: string | null;
    contact_person?: string | null;
    mobile_number?: string | null;
    industry?: string | null;
    about_company?: string | null;
    company_size?: string | null;
    company_website?: string | null;
    company_details?: string | null;
    professional?: AccountProfessionalProfile | null;
};
