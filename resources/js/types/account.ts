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
    is_internship?: boolean;
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
    willing_to_relocate?: boolean;
    availability?: string | null;
    relocation_summary?: string | null;
    public_share?: AccountPublicShare;
    certifications?: AccountCertification[];
    languages?: AccountProfessionalLanguage[];
    educations?: AccountEducation[];
    projects?: AccountProject[];
};

export type AccountPublicShare = {
    enabled: boolean;
    bio: boolean;
    headline: boolean;
    location: boolean;
    current_role: boolean;
    skills: boolean;
    history: boolean;
    education: boolean;
    projects: boolean;
    certifications: boolean;
    languages: boolean;
};

export type AccountCompanyShare = {
    enabled: boolean;
    about: boolean;
    details: boolean;
    website: boolean;
    industry_size: boolean;
    locations: boolean;
    benefits: boolean;
    socials: boolean;
};

export type AccountCompanySocials = {
    linkedin: string | null;
    facebook: string | null;
    twitter: string | null;
    instagram: string | null;
    youtube: string | null;
};

export type AccountCertification = {
    id: number;
    name: string;
    issuer: string | null;
    issued_on: string | null;
    expires_on: string | null;
    candidate_document_id: number | null;
};

export type AccountProfessionalLanguage = {
    id: number;
    name: string;
    proficiency: string | null;
    proficiency_label: string | null;
};

export type AccountEducation = {
    id: number;
    institution: string;
    qualification: string | null;
    field: string | null;
    started_on: string | null;
    ended_on: string | null;
};

export type AccountProject = {
    id: number;
    name: string;
    role: string | null;
    company: string | null;
    description: string | null;
    started_on: string | null;
    ended_on: string | null;
};

export type AccountProfileLookups = {
    industries: AccountLookupOption[];
    categories: AccountLookupOption[];
    locations: AccountLookupOption[];
    employment_types: AccountLookupOption[];
    experience_ranges: AccountLookupOption[];
    qualifications: AccountLookupOption[];
    notice_periods: AccountNoticePeriodOption[];
    language_proficiencies?: AccountNoticePeriodOption[];
    public_share_fields?: Array<{ key: string; label: string }>;
    social_fields?: Array<{ key: string; label: string }>;
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

export type AccountEmployerDocument = {
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
    expiry_state?: string | null;
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
        designation?: string | null;
        photo_url?: string | null;
        user_business_id?: string | null;
        candidate_business_id?: string | null;
        ournear_id?: string | null;
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

export type AccountDashboardApplicationStatusSummary = {
    label: string;
    count: number;
};

export type AccountDashboardApplication = {
    title: string;
    slug: string | null;
    company?: string | null;
    status: string;
    status_detail?: string;
    applied_at: string | null;
    href?: string | null;
};

export type AccountCandidateDashboard = {
    header: {
        name: string;
        username: string | null;
        business_id?: string | null;
        ournear_id?: string | null;
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
        status_summary: AccountDashboardApplicationStatusSummary[];
        recent: AccountDashboardApplication[];
    };
};

export type AccountEmployerVisiblePerson = {
    id: number;
    application_id?: number | null;
    submission_id?: number;
    business_id: string | null;
    name: string;
    photo_url: string | null;
    username?: string | null;
    designation?: string | null;
    location?: string | null;
    experience?: string | null;
    qualification?: string | null;
    status: string;
    applied_at?: string | null;
    submitted_at?: string | null;
    has_resume?: boolean;
    resume_url?: string | null;
    public_profile_url?: string | null;
    job?: {
        title: string;
        business_id: string | null;
        slug: string;
    } | null;
};

export type AccountEmployerNextAction = {
    label: string;
    href: string;
    kind: 'pending' | 'info' | string;
};

export type AccountEmployerActiveJob = {
    title: string;
    location: string | null;
    applications_count: number;
    status_label: string;
    href: string;
};

export type AccountEmployerDashboard = {
    company_name: string;
    business_id: string | null;
    user_business_id?: string | null;
    ournear_id?: string | null;
    username?: string | null;
    photo_url: string | null;
    industry: string | null;
    jobs_count: number;
    active_jobs_count: number;
    applicants_count: number;
    submissions_count: number;
    enquiries_count: number;
    email_verified: boolean;
    mobile_verified: boolean;
    ournear_verified: boolean;
    posting_restricted: boolean;
    eligibility: {
        eligible: boolean;
        email_verified: boolean;
        phone_verified: boolean;
        admin_approved: boolean;
        restricted?: boolean;
        reason: string | null;
        next_action: string | null;
    };
    recent_applicants: AccountEmployerVisiblePerson[];
    recent_submissions: AccountEmployerVisiblePerson[];
    next_actions: AccountEmployerNextAction[];
};

export type AccountEmployerHiringWorkspace = AccountEmployerDashboard & {
    user_name: string;
    actions_required: AccountEmployerNextAction[];
    active_jobs: AccountEmployerActiveJob[];
    can_post_job: boolean;
};

export type AccountProfile = {
    name: string;
    username: string | null;
    email: string;
    pending_email?: string | null;
    mobile: string | null;
    location: string | null;
    bio: string | null;
    professional_headline?: string | null;
    headline?: string | null;
    preferred_language: string;
    photo_url: string | null;
    email_verified: boolean;
    mobile_verified: boolean;
    whatsapp_number: string | null;
    whatsapp_verified: boolean;
    account_status: string;
    account_status_label: string;
    profile_completion: number;
    completion_items: AccountCompletionItem[];
    business_id: string | null;
    experience?: string | null;
    qualification?: string | null;
    employer_profile_id?: number;
    company_business_id?: string | null;
    company_name?: string | null;
    contact_person?: string | null;
    mobile_number?: string | null;
    industry?: string | null;
    about_company?: string | null;
    company_size?: string | null;
    company_website?: string | null;
    company_details?: string | null;
    location_ids?: number[];
    locations?: Array<{ id: number; name: string }>;
    benefits?: string[];
    socials?: AccountCompanySocials;
    public_share?: AccountPublicShare | AccountCompanyShare;
    ournear_verified?: boolean;
    posting_restricted?: boolean;
    professional?: AccountProfessionalProfile | null;
    verifications?: {
        email: boolean;
        mobile: boolean;
        whatsapp: boolean;
    };
    verification_context?: {
        mobile_required: boolean;
    };
    public_profile_url?: string | null;
    modules?: {
        identity: string | null;
        candidate: string | null;
        company: string | null;
        company_name: string | null;
    };
    hiring_links?: Array<{ label: string; href: string }>;
    ournear_id?: string | null;
};
