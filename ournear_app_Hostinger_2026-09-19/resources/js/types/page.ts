import type { SiteContent, SeoProps, PageSeoProps, AnalyticsProps } from '@/types/public';
import type { PublicSocialLink } from '@/types/media';
import type { CmsPage } from '@/types/cms';
import type { AdminAlertItem } from '@/types/communication';

export type AuthUser = {
    id: number;
    name: string;
    email: string;
    username?: string | null;
    photo_url?: string | null;
    must_change_password?: boolean;
    preferred_language?: 'en' | 'te' | 'hi';
    email_verified?: boolean;
    profile_completion?: number;
    role?: 'admin' | 'candidate' | 'employer';
    permissions?: string[];
};

export type FlashProps = {
    success: string | null;
    error: string | null;
    status: string | null;
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
};

export type SharedPageProps = {
    auth: {
        user: AuthUser | null;
        admin?: AuthUser | null;
        account?: AuthUser | null;
    };
    flash: FlashProps;
    adminAlerts?: {
        unread: number;
        recent: AdminAlertItem[];
    } | null;
    accountAlerts?: {
        unread: number;
    } | null;
    candidateNav?: Array<{
        href: string;
        label: string;
    }> | null;
    errors?: Record<string, string>;
    publicContent?: SiteContent;
    socialLinks?: PublicSocialLink[];
    seo?: SeoProps;
    pageSeo?: PageSeoProps | null;
    analytics?: AnalyticsProps;
    cms?: CmsPage;
    turnstileSiteKey?: string | null;
    branding?: {
        website_name: string;
        logo_url: string | null;
        icon_url: string | null;
        favicon_url: string | null;
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
