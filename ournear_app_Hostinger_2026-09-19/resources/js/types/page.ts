import type { SiteContent, SeoProps, PageSeoProps, AnalyticsProps } from '@/types/public';
import type { PublicSocialLink } from '@/types/media';
import type { CmsPage } from '@/types/cms';
import type { AdminAlertItem } from '@/types/communication';

export type AuthUser = {
    id: number;
    name: string;
    email: string;
    role?: 'admin' | 'candidate' | 'employer';
    permissions?: string[];
};

export type FlashProps = {
    success: string | null;
    error: string | null;
    status: string | null;
};

export type SharedPageProps = {
    auth: {
        user: AuthUser | null;
    };
    flash: FlashProps;
    adminAlerts?: {
        unread: number;
        recent: AdminAlertItem[];
    } | null;
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
};
