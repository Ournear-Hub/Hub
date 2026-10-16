export type HireLandingMode = 'live' | 'demonstration';

export type HireWorkspaceKpis = {
    active_jobs: number;
    applications: number;
    shortlisted: number;
    interviews: number;
    offers: number;
    hired: number;
};

export type HireAtsStageMetric = {
    key: string;
    label: string;
    count: number;
};

export type HireReportsMetrics = {
    total_applications: number;
    shortlisted: number;
    interviews: number;
    offers: number;
    hired: number;
};

export type HireChartSeries = {
    label: string;
    value: number;
};

export type HireCompanyPreview = {
    company_name: string;
    industry: string | null;
    location: string | null;
    about: string | null;
    logo_url: string | null;
    open_jobs: number;
    public_url: string | null;
    company_size: string | null;
};

export type HireRecentActivity = {
    label: string;
    detail: string | null;
    at: string | null;
};

export type HirePresentation = {
    mode: HireLandingMode;
    workspace: HireWorkspaceKpis | null;
    ats_stages: HireAtsStageMetric[] | null;
    reports: HireReportsMetrics | null;
    chart: {
        title: string;
        subtitle: string;
        series: HireChartSeries[];
    } | null;
    company: HireCompanyPreview | null;
    recent_activity: HireRecentActivity[] | null;
};
