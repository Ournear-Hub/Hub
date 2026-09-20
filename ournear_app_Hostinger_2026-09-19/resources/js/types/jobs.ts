export type PublicJobCard = {
    title: string;
    slug: string;
    project: string;
    industry: string;
    category: string;
    location: string;
    employment_type: string;
    experience: string;
    salary: string | null;
    vacancy: number;
    posted_date: string | null;
    closing_date: string | null;
    featured: boolean;
};

export type PublicJobDetail = PublicJobCard & {
    description: string;
    responsibilities: string | null;
    requirements: string | null;
    what_we_offer: string | null;
    accepts_applications: boolean;
};

export type AdminJob = PublicJobCard & {
    id: number;
    description: string;
    responsibilities: string | null;
    requirements: string | null;
    what_we_offer: string | null;
    publishing_status: 'draft' | 'published';
    active: boolean;
    applications_count: number;
};

export type AdminApplicationListItem = {
    id: number;
    full_name: string;
    mobile: string;
    email: string;
    current_location: string;
    status: string;
    applied_at: string | null;
    job: {
        id: number;
        title: string;
        industry: string;
        location: string;
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
        title: string;
        slug: string;
        industry: string;
        location: string;
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
