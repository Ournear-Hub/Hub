export type HiringMetricCard = {
    label: string;
    value: number | null;
    href: string | null;
    empty: string;
};

export type HiringQueueItem = {
    title: string;
    subtitle: string | null;
    href: string;
    origin?: 'guest' | 'account';
};

export type HiringAttentionGroup = {
    key: string;
    label: string;
    href: string;
    count: number;
    empty: string;
    items: HiringQueueItem[];
};

export type HiringCommandCenterPermissions = {
    ats: boolean;
    partnership: boolean;
    staffing: boolean;
    jobs: boolean;
    finance: boolean;
    automation: boolean;
};

export type HiringCommandCenterProps = {
    generated_at: string;
    filters: {
        line: string;
        job_id: string;
    };
    job_options: Array<{ id: number; title: string }>;
    permissions: HiringCommandCenterPermissions;
    ownership_note: string;
    ats: {
        metrics: HiringMetricCard[];
        queue: {
            screening: HiringQueueItem[];
            interviews: HiringQueueItem[];
            scorecards: HiringQueueItem[];
        };
    } | null;
    partnership: {
        metrics: HiringMetricCard[];
        queue: HiringQueueItem[];
    } | null;
    staffing: {
        metrics: HiringMetricCard[];
        queue: {
            placements: HiringQueueItem[];
            replacements: HiringQueueItem[];
            invoices: HiringQueueItem[];
        };
        assignment_end_note: string;
    } | null;
    jobs_board: {
        metrics: HiringMetricCard[];
        recent: HiringQueueItem[];
    } | null;
    attention: HiringAttentionGroup[];
    activity: HiringQueueItem[];
    automation: HiringQueueItem[];
    workload: {
        open_tasks: number | null;
        overdue_tasks: number | null;
    };
    links: Record<string, string>;
};
