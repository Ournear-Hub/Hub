export type IconCategory =
    | 'navigation'
    | 'actions'
    | 'status'
    | 'communication'
    | 'jobs'
    | 'recruitment'
    | 'documents'
    | 'users'
    | 'organizations'
    | 'analytics'
    | 'social'
    | 'settings'
    | 'security'
    | 'system';

export type IconSize = 'xs' | 'sm' | 'md' | 'lg' | 'xl';

export type IconRegistryStatus = 'active' | 'deprecated';

export type IconSource = 'tabler' | 'inline';

export type IconRegistryEntry = {
    name: string;
    source: IconSource;
    category: IconCategory;
    defaultSize: IconSize;
    stroke: number;
    status: IconRegistryStatus;
    /** Tabler React component export name, e.g. IconBell */
    tablerName?: string;
    /** Inline legacy key in `legacyInlineIcons` */
    inlineKey?: 'spinner';
    aliases?: string[];
};
