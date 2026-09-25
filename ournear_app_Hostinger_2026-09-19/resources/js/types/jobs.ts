export type PublicJobCard = {
    title: string;
    slug: string;
    project: string;
    company_name: string;
    company_logo_url: string | null;
    company_url: string | null;
    industry: string;
    category: string;
    location: string;
    employment_type: string;
    experience: string;
    salary: string | null;
    vacancy: number;
    posted_date: string | null;
    closing_date: string | null;
    posted_label?: string;
    closing_label?: string;
    is_closed?: boolean;
    featured: boolean;
    is_saved?: boolean | null;
    has_applied?: boolean;
    has_resume?: boolean;
    apply_state?: 'guest' | 'apply' | 'upload_resume' | 'applied' | 'closed' | 'hidden';
};

export type PublicJobDetail = PublicJobCard & {
    description: string;
    responsibilities: string | null;
    requirements: string | null;
    what_we_offer: string | null;
    accepts_applications: boolean;
};

export type AdminJob = {
    id: number;
    business_id: string | null;
    title: string;
    slug: string;
    project: string;
    industry: string;
    category: string | null;
    legacy_category: string | null;
    location: string;
    employment_type: string;
    experience: string;
    classified_title: string | null;
    industry_id: number | null;
    job_category_id: number | null;
    job_title_id: number | null;
    location_id: number | null;
    employment_type_id: number | null;
    experience_range_id: number | null;
    employer_id: number | null;
    employer: {
        id: number;
        business_id: string | null;
        company_name: string;
        contact_person: string | null;
        email: string | null;
        workspace_url?: string | null;
    } | null;
    salary: string | null;
    vacancy: number;
    posted_date: string | null;
    closing_date: string | null;
    featured: boolean;
    posting_tier?: 'trial' | 'basic';
    trial_expires_at?: string | null;
    active: boolean;
    publishing_status: 'draft' | 'published';
    status_label: string;
    description: string;
    responsibilities: string | null;
    requirements: string | null;
    what_we_offer: string | null;
    applications_count: number;
    created_at: string | null;
    updated_at: string | null;
    skills?: string | null;
    notice_period?: string | null;
    keywords?: string | null;
    qualification?: string | null;
    created_by?: string | null;
    source?: string | null;
    links?: {
        applications?: string;
        candidates?: string;
        screening?: string;
        shortlisted?: string;
        submissions?: string;
        placements?: string;
    };
};

export type AdminApplicationListItem = {
    id: number;
    business_id?: string | null;
    full_name: string;
    mobile?: string;
    email?: string;
    current_location?: string;
    status: string;
    applied_at: string | null;
    screening_decision_label?: string;
    candidate?: {
        id: number | null;
        business_id: string | null;
        name: string;
    };
    job: {
        id: number;
        business_id?: string | null;
        title: string;
        industry?: string | null;
        location?: string | null;
        slug?: string;
    } | null;
};

export type AdminApplication = AdminApplicationListItem & {
    experience: string;
    qualification: string;
    additional_information: string | null;
    consent: boolean;
    has_resume: boolean;
    resume_url: string;
    internal_notes: Array<{
        body: string;
        author_id: number | null;
        author_name: string;
        created_at: string;
    }>;
    job: {
        id: number;
        business_id?: string | null;
        title: string;
        slug?: string;
        industry?: string | null;
        location?: string | null;
        category?: string | null;
        employment_type?: string | null;
        experience?: string | null;
        status_label?: string;
        workspace_url?: string | null;
    } | null;
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

export type Paginator<T> = {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};
