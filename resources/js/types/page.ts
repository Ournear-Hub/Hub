import type { SiteContent, SeoProps, PageSeoProps, AnalyticsProps } from '@/types/public';
import type { PublicSocialLink } from '@/types/media';
import type { CmsPage } from '@/types/cms';
import type { AdminAlertItem } from '@/types/communication';
import type { NavSection } from '@/types/layout';

export type HeaderEmployerIdentity = {
    name: string;
    logo_url: string | null;
    href: string;
    industry?: string | null;
    location?: string | null;
    company_website?: string | null;
    ournear_verified?: boolean;
    profile_completion?: number | null;
};

export type PortalNavigationChip = {
    href: string;
    label: string;
    icon: string;
};

export type PortalNavigation = {
    candidate: PortalNavigationChip;
    company: PortalNavigationChip & {
        has_company_profile: boolean;
        logo_url: string | null;
    };
};

export type AuthUser = {
    id: number;
    name: string;
    display_name?: string;
    employer_identity?: HeaderEmployerIdentity | null;
    portal_navigation?: PortalNavigation | null;
    email: string;
    username?: string | null;
    business_id?: string | null;
    photo_url?: string | null;
    must_change_password?: boolean;
    preferred_language?: 'en' | 'te' | 'hi';
    email_verified?: boolean;
    mobile_verified?: boolean;
    whatsapp_verified?: boolean;
    ournear_id?: string | null;
    profile_completion?: number;
    role?: 'admin' | 'candidate' | 'employer';
    permissions?: string[];
    can_view_analytics?: boolean;
    can_view_hiring?: boolean;
};

export type FlashProps = {
    success: string | null;
    error: string | null;
    warning?: string | null;
    status: string | null;
    masterDataImportPreview?: {
        token: string;
        summary: Record<string, number>;
        rows: Array<{
            row: number;
            action: string;
            intent?: string;
            errors?: string[];
        }>;
    } | null;
    masterDataImportOutcome?: Record<string, unknown> | null;
    registered_email?: string | null;
    freelancer_confirmation?: {
        business_id: string;
        name: string;
        email: string;
        preferred_location: string;
        primary_skill: string;
        submitted_at: string | null;
    } | null;
    application_confirmation?: {
        business_id: string | null;
        job_title: string;
        company: string | null;
        location: string | null;
        href: string | null;
    } | null;
    duplicate_matches?: Array<{
        type: string;
        id: number;
        company_name: string | null;
        email: string | null;
        workspace_url?: string;
        reasons: string[];
    }> | null;
    mobile_otp_retry_after?: number | null;
    whatsapp_otp_retry_after?: number | null;
};

export type EmailVerificationReminderProps = {
    email: string;
} | null;

export type SharedPageProps = {
    auth: {
        user: AuthUser | null;
        admin?: AuthUser | null;
        account?: AuthUser | null;
    };
    flash: FlashProps;
    emailVerificationReminder?: EmailVerificationReminderProps;
    adminAlerts?: {
        unread: number;
        recent: AdminAlertItem[];
    } | null;
    accountAlerts?: {
        unread: number;
        recent?: Array<{
            id: string;
            title: string;
            body?: string | null;
            url?: string | null;
            is_unread: boolean;
        }>;
    } | null;
    candidateNav?: Array<{
        href: string;
        label: string;
        group?: string;
        icon?: string;
        logo_url?: string | null;
        nav_kind?: 'company';
    }> | null;
    candidateSidebarProfile?: {
        name: string;
        username: string | null;
        photo_url: string | null;
        designation: string | null;
        professional_headline: string | null;
        location: string | null;
        organization: string | null;
    } | null;
    employerNav?: Array<{
        href: string;
        label: string;
        group?: string;
        icon?: string;
    }> | null;
    headerAccountNav?: Array<{
        href: string;
        label: string;
        group?: string;
        icon?: string;
    }> | null;
    companyHeaderNav?: Array<{
        href: string;
        label: string;
        group?: string;
        icon?: string;
    }> | null;
    adminNav?: NavSection[] | null;
    errors?: Record<string, string>;
    publicContent?: SiteContent;
    socialLinks?: PublicSocialLink[];
    seo?: SeoProps;
    pageSeo?: PageSeoProps | null;
    analytics?: AnalyticsProps;
    sharing?: {
        public_base_url: string;
    };
    cms?: CmsPage;
    turnstileSiteKey?: string | null;
    ui?: {
        mobile_profile_view_only?: boolean;
    };
    branding?: {
        website_name: string;
        brand_mark?: string;
        logo_url: string | null;
        wordmark_url?: string | null;
        icon_url: string | null;
        favicon_url: string | null;
        hire_wordmark_url?: string | null;
        auth_symbol_url?: string | null;
        auth_wordmark_url?: string | null;
        auth_splash_video_url?: string | null;
        auth_splash_poster_url?: string | null;
    };
};

export type DashboardMetric = {
    value: number | null;
    href?: string | null;
};

export type DashboardPipelineStage = {
    key: string;
    label: string;
    value: number | null;
    href?: string | null;
};

export type DashboardStats = {
    jobs: number | null;
    active_jobs: number | null;
    applications: number | null;
    employer_enquiries: number | null;
    contact_enquiries: number | null;
    pipeline?: {
        new: number | null;
        reviewing: number | null;
        shortlisted: number | null;
        interview: number | null;
        selected: number | null;
        placed: number | null;
        published_open_jobs: number | null;
        employer_enquiries_new: number | null;
        employer_enquiries_contacted: number | null;
        employer_enquiries_in_progress: number | null;
        jobs_closing_soon: number | null;
    };
    platform?: {
        total_users: DashboardMetric;
        new_users: DashboardMetric;
        active_users: DashboardMetric;
        verified_users: DashboardMetric;
        blocked_users: DashboardMetric;
    };
    recruitment?: {
        active_jobs: DashboardMetric;
        applications: DashboardMetric;
        candidates: DashboardMetric;
        screening_pending: DashboardMetric;
        shortlisted: DashboardMetric;
        employer_submissions: DashboardMetric;
        selected: DashboardMetric;
        placements: DashboardMetric;
        joined_employees: DashboardMetric;
        replacement_cases: DashboardMetric;
    };
    recruitment_pipeline?: DashboardPipelineStage[];
    pending_actions?: DashboardPipelineStage[];
};
