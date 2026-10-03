export type StaffRoleOption = {
    id: number;
    name: string;
    slug: string;
    is_locked?: boolean;
    assignable?: boolean;
};

export type UserRoleSummary = {
    id: number;
    name: string;
    slug: string;
};

export type BrandSummary = {
    id: number;
    name: string;
    slug?: string;
    is_primary?: boolean;
};

export type TagOption = {
    id: number;
    name: string;
    slug: string;
    is_active: boolean;
};

export type UserListItem = {
    id: number;
    business_id?: string | null;
    username?: string | null;
    name: string;
    email: string;
    mobile?: string | null;
    photo_url?: string | null;
    account_type: string;
    account_status?: string;
    account_status_label?: string;
    registration_source?: string | null;
    registration_source_label?: string | null;
    is_active: boolean;
    email_verified?: boolean;
    mobile_verified?: boolean;
    preferred_language?: string;
    location?: string | null;
    profile_completion?: number;
    last_login_at?: string | null;
    last_activity_at?: string | null;
    tags?: TagOption[];
    must_change_password?: boolean;
    brand?: BrandSummary | null;
    roles: UserRoleSummary[];
    created_at: string | null;
    updated_at: string | null;
};

export type UserDetail = UserListItem & {
    permissions: string[];
    is_super_admin: boolean;
    admin_profile?: {
        department: string | null;
        designation: string | null;
        joined_at: string | null;
        professional_details: string | null;
        qualifications: string | null;
        skills: string | null;
        employment_information: string | null;
    } | null;
};

export type UserSegmentOption = {
    slug: string;
    name: string;
    description?: string | null;
    is_navigation?: boolean;
};

export type UserActivityItem = {
    id: number;
    user_id: number;
    user_name?: string | null;
    actor_id?: number | null;
    actor_name?: string | null;
    type: string;
    type_label: string;
    description: string;
    metadata?: Record<string, unknown>;
    created_at: string | null;
};

export type CommunicationPreferenceItem = {
    id: number;
    channel: string;
    channel_label: string;
    opted_in: boolean;
    consented_at: string | null;
    source: string | null;
    opted_out_at: string | null;
};

export type User360RelatedJob = {
    id: number;
    business_id?: string | null;
    title: string;
    active: boolean;
    href: string;
};

export type User360Document = {
    id: number;
    type: string;
    label: string;
    href: string;
    view_href?: string;
    download_href?: string;
};

export type User360Notification = {
    id: string | number;
    title: string;
    body?: string | null;
    read_at: string | null;
    created_at: string | null;
};

export type User360Message = {
    id: number;
    subject?: string | null;
    href: string;
    updated_at: string | null;
};

export type User360Application = {
    id: number;
    business_id?: string | null;
    full_name: string;
    status: string;
    applied_at: string | null;
    has_resume: boolean;
    resume_url?: string | null;
    job?: { id: number; business_id?: string | null; title: string } | null;
};

export type User360Payment = {
    id: number;
    business_id?: string | null;
    status: string;
    total_amount: string;
    billed_at: string | null;
};

export type User360Candidate = {
    id: number;
    business_id?: string | null;
    name?: string | null;
    mobile?: string | null;
    location?: string | null;
    experience?: string | null;
    qualification?: string | null;
    workspace_url?: string | null;
};

export type User360Employer = {
    id: number;
    business_id?: string | null;
    company_name: string;
    contact_person?: string | null;
    mobile_number?: string | null;
    industry?: string;
    workspace_url?: string;
};

export type User360Payload = {
    user: UserDetail;
    candidate: User360Candidate | null;
    employer: User360Employer | null;
    applications: User360Application[];
    jobs: User360RelatedJob[];
    documents: User360Document[];
    notifications: User360Notification[];
    messages: User360Message[];
    payments: User360Payment[];
    activities: UserActivityItem[];
    login_logs: LoginLogItem[];
    security_activities: ActivityLogItem[];
    communication_preferences: CommunicationPreferenceItem[];
};

export type PermissionItem = {
    id: number;
    name: string;
    group: string;
    label: string;
    group_label?: string;
};

export type PermissionGroup = {
    group: string;
    permissions: PermissionItem[];
};

export type RoleDetail = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    is_locked: boolean;
    users_count: number;
    permissions: PermissionItem[];
};

export type LoginLogItem = {
    id: number;
    user_id: number | null;
    user_name: string | null;
    email: string;
    event: string;
    event_label: string;
    ip_address: string | null;
    user_agent: string | null;
    succeeded: boolean;
    metadata: Record<string, unknown>;
    created_at: string | null;
};

export type ActivityLogItem = {
    id: number;
    actor_id: number | null;
    actor_name: string | null;
    subject_type: string | null;
    subject_id: number | null;
    action: string;
    action_label: string;
    description: string;
    ip_address: string | null;
    metadata: Record<string, unknown>;
    created_at: string | null;
};
