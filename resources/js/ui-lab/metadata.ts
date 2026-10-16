export type UiLabSectionId =
    | 'overview'
    | 'foundation'
    | 'icons'
    | 'atoms-button'
    | 'atoms-icon-button'
    | 'atoms-input'
    | 'atoms-textarea'
    | 'atoms-select'
    | 'atoms-checkbox'
    | 'atoms-radio'
    | 'atoms-switch'
    | 'atoms-skeleton'
    | 'atoms-spinner'
    | 'atoms-progress'
    | 'atoms-divider'
    | 'atoms-link'
    | 'atoms-visually-hidden'
    | 'atoms-avatar'
    | 'atoms-chip'
    | 'atoms-image'
    | 'atoms-link-button'
    | 'atoms-card'
    | 'atoms-container'
    | 'atoms-stack'
    | 'atoms-cluster'
    | 'atoms-aspect-ratio'
    | 'molecules-form-field'
    | 'molecules-search-field'
    | 'molecules-password-field'
    | 'molecules-file-field'
    | 'molecules-date-field'
    | 'molecules-time-field'
    | 'molecules-date-time-field'
    | 'molecules-number-field'
    | 'molecules-email-field'
    | 'molecules-url-field'
    | 'molecules-field-group'
    | 'molecules-input-group'
    | 'molecules-field-set'
    | 'molecules-combobox'
    | 'molecules-multi-select'
    | 'molecules-segmented-control'
    | 'molecules-accordion'
    | 'molecules-tooltip'
    | 'molecules-popover'
    | 'molecules-dropdown-menu'
    | 'molecules-alert'
    | 'molecules-toast'
    | 'molecules-empty-state'
    | 'molecules-data-table'
    | 'molecules-filter-bar'
    | 'molecules-breadcrumb'
    | 'molecules-pagination'
    | 'molecules-tabs'
    | 'molecules-timeline'
    | 'molecules-dialog'
    | 'molecules-drawer'
    | 'organisms-detail-header'
    | 'organisms-detail-section'
    | 'organisms-document-group-list'
    | 'organisms-match-comparison-table'
    | 'organisms-screening-stages-table'
    | 'organisms-applicant-summary-card'
    | 'organisms-applicant-resume-overview'
    | 'organisms-application-timeline';

export type UiLabNavItem = {
    id:
        | UiLabSectionId
        | 'typography'
        | 'text'
        | 'heading'
        | 'label'
        | 'molecules'
        | 'components'
        | 'organisms'
        | 'patterns'
        | 'templates'
        | 'shells'
        | 'systems'
        | 'atoms';
    label: string;
    href?: string;
    comingSoon?: boolean;
    children?: UiLabNavItem[];
};

export type LibraryItemStatus = 'active' | 'coming-later';

export type LibraryStatusRow = {
    id: string;
    label: string;
    status: LibraryItemStatus;
};

/** Single source for overview status table and future lab expansion. */
export const libraryStatus: LibraryStatusRow[] = [
    { id: 'foundation', label: 'Foundation', status: 'active' },
    { id: 'tokens', label: 'Tokens', status: 'active' },
    { id: 'icons', label: 'Icons', status: 'active' },
    { id: 'button', label: 'Button', status: 'active' },
    { id: 'icon-button', label: 'IconButton', status: 'active' },
    { id: 'input', label: 'Input', status: 'active' },
    { id: 'textarea', label: 'Textarea', status: 'active' },
    { id: 'select', label: 'Select', status: 'active' },
    { id: 'checkbox', label: 'Checkbox', status: 'active' },
    { id: 'radio', label: 'Radio', status: 'active' },
    { id: 'switch', label: 'Switch', status: 'active' },
    { id: 'text', label: 'Text', status: 'active' },
    { id: 'heading', label: 'Heading', status: 'active' },
    { id: 'label', label: 'Label', status: 'active' },
    { id: 'link', label: 'Link', status: 'active' },
    { id: 'visually-hidden', label: 'VisuallyHidden', status: 'active' },
    { id: 'badge', label: 'Badge', status: 'active' },
    { id: 'status-badge', label: 'StatusBadge', status: 'active' },
    { id: 'avatar', label: 'Avatar', status: 'active' },
    { id: 'chip', label: 'Chip', status: 'active' },
    { id: 'image', label: 'Image', status: 'active' },
    { id: 'link-button', label: 'LinkButton', status: 'active' },
    { id: 'card', label: 'Card', status: 'active' },
    { id: 'container', label: 'Container', status: 'active' },
    { id: 'stack', label: 'Stack', status: 'active' },
    { id: 'cluster', label: 'Cluster', status: 'active' },
    { id: 'aspect-ratio', label: 'AspectRatio', status: 'active' },
    { id: 'divider', label: 'Divider', status: 'active' },
    { id: 'skeleton', label: 'Skeleton', status: 'active' },
    { id: 'spinner', label: 'Spinner', status: 'active' },
    { id: 'progress', label: 'Progress', status: 'active' },
    { id: 'form-field', label: 'FormField', status: 'active' },
    { id: 'search-field', label: 'SearchField', status: 'active' },
    { id: 'password-field', label: 'PasswordField', status: 'active' },
    { id: 'file-field', label: 'FileField', status: 'active' },
    { id: 'date-field', label: 'DateField', status: 'active' },
    { id: 'time-field', label: 'TimeField', status: 'active' },
    { id: 'date-time-field', label: 'DateTimeField', status: 'active' },
    { id: 'number-field', label: 'NumberField', status: 'active' },
    { id: 'email-field', label: 'EmailField', status: 'active' },
    { id: 'url-field', label: 'URLField', status: 'active' },
    { id: 'field-group', label: 'FieldGroup', status: 'active' },
    { id: 'input-group', label: 'InputGroup', status: 'active' },
    { id: 'field-set', label: 'FieldSet', status: 'active' },
    { id: 'combobox', label: 'Combobox', status: 'active' },
    { id: 'multi-select', label: 'MultiSelect', status: 'active' },
    { id: 'segmented-control', label: 'SegmentedControl', status: 'active' },
    { id: 'accordion', label: 'Accordion', status: 'active' },
    { id: 'tooltip', label: 'Tooltip', status: 'active' },
    { id: 'popover', label: 'Popover', status: 'active' },
    { id: 'dropdown-menu', label: 'DropdownMenu', status: 'active' },
    { id: 'alert', label: 'Alert', status: 'active' },
    { id: 'toast', label: 'Toast', status: 'active' },
    { id: 'empty-state', label: 'EmptyState', status: 'active' },
    { id: 'data-table', label: 'DataTable', status: 'active' },
    { id: 'filter-bar', label: 'FilterBar', status: 'active' },
    { id: 'breadcrumb', label: 'Breadcrumb', status: 'active' },
    { id: 'pagination', label: 'Pagination', status: 'active' },
    { id: 'tabs', label: 'Tabs', status: 'active' },
    { id: 'timeline', label: 'Timeline', status: 'active' },
    { id: 'dialog', label: 'Dialog', status: 'active' },
    { id: 'drawer', label: 'Drawer', status: 'active' },
    { id: 'molecules', label: 'Molecules', status: 'active' },
    { id: 'detail-header', label: 'DetailHeader', status: 'active' },
    { id: 'document-group-list', label: 'DocumentGroupList', status: 'active' },
    { id: 'application-timeline', label: 'ApplicationTimeline', status: 'active' },
    { id: 'components', label: 'Components', status: 'coming-later' },
];

export const uiLabNav: UiLabNavItem[] = [
    { id: 'overview', label: 'Overview', href: '/admin/design-system' },
    { id: 'foundation', label: 'Foundation', href: '/admin/design-system/foundation' },
    { id: 'typography', label: 'Typography', href: '/admin/design-system/foundation#typography-atoms' },
    { id: 'icons', label: 'Icons', href: '/admin/design-system/icons' },
    {
        id: 'atoms',
        label: 'Atoms',
        children: [
            { id: 'atoms-button', label: 'Button', href: '/admin/design-system/atoms/button' },
            { id: 'atoms-icon-button', label: 'IconButton', href: '/admin/design-system/atoms/icon-button' },
            { id: 'atoms-input', label: 'Input', href: '/admin/design-system/atoms/input' },
            { id: 'atoms-textarea', label: 'Textarea', href: '/admin/design-system/atoms/textarea' },
            { id: 'atoms-select', label: 'Select', href: '/admin/design-system/atoms/select' },
            { id: 'atoms-checkbox', label: 'Checkbox', href: '/admin/design-system/atoms/checkbox' },
            { id: 'atoms-radio', label: 'Radio', href: '/admin/design-system/atoms/radio' },
            { id: 'atoms-switch', label: 'Switch', href: '/admin/design-system/atoms/switch' },
            { id: 'atoms-skeleton', label: 'Skeleton', href: '/admin/design-system/atoms/skeleton' },
            { id: 'atoms-spinner', label: 'Spinner', href: '/admin/design-system/atoms/spinner' },
            { id: 'atoms-progress', label: 'Progress', href: '/admin/design-system/atoms/progress' },
            { id: 'atoms-divider', label: 'Divider', href: '/admin/design-system/atoms/divider' },
            { id: 'atoms-link', label: 'Link', href: '/admin/design-system/atoms/link' },
            {
                id: 'atoms-visually-hidden',
                label: 'VisuallyHidden',
                href: '/admin/design-system/atoms/visually-hidden',
            },
            { id: 'atoms-avatar', label: 'Avatar', href: '/admin/design-system/atoms/avatar' },
            { id: 'atoms-chip', label: 'Chip', href: '/admin/design-system/atoms/chip' },
            { id: 'atoms-image', label: 'Image', href: '/admin/design-system/atoms/image' },
            {
                id: 'atoms-link-button',
                label: 'LinkButton',
                href: '/admin/design-system/atoms/link-button',
            },
            { id: 'atoms-card', label: 'Card', href: '/admin/design-system/atoms/card' },
            { id: 'atoms-container', label: 'Container', href: '/admin/design-system/atoms/container' },
            { id: 'atoms-stack', label: 'Stack', href: '/admin/design-system/atoms/stack' },
            { id: 'atoms-cluster', label: 'Cluster', href: '/admin/design-system/atoms/cluster' },
            {
                id: 'atoms-aspect-ratio',
                label: 'AspectRatio',
                href: '/admin/design-system/atoms/aspect-ratio',
            },
            { id: 'text', label: 'Text', href: '/admin/design-system/foundation#typography-atoms' },
            { id: 'heading', label: 'Heading', href: '/admin/design-system/foundation#typography-atoms' },
            { id: 'label', label: 'Label', href: '/admin/design-system/foundation#typography-atoms' },
        ],
    },
    {
        id: 'molecules',
        label: 'Molecules',
        children: [
            {
                id: 'molecules-form-field',
                label: 'FormField',
                href: '/admin/design-system/molecules/form-field',
            },
            {
                id: 'molecules-search-field',
                label: 'SearchField',
                href: '/admin/design-system/molecules/search-field',
            },
            {
                id: 'molecules-password-field',
                label: 'PasswordField',
                href: '/admin/design-system/molecules/password-field',
            },
            {
                id: 'molecules-file-field',
                label: 'FileField',
                href: '/admin/design-system/molecules/file-field',
            },
            {
                id: 'molecules-date-field',
                label: 'DateField',
                href: '/admin/design-system/molecules/date-field',
            },
            {
                id: 'molecules-time-field',
                label: 'TimeField',
                href: '/admin/design-system/molecules/time-field',
            },
            {
                id: 'molecules-date-time-field',
                label: 'DateTimeField',
                href: '/admin/design-system/molecules/date-time-field',
            },
            {
                id: 'molecules-number-field',
                label: 'NumberField',
                href: '/admin/design-system/molecules/number-field',
            },
            {
                id: 'molecules-email-field',
                label: 'EmailField',
                href: '/admin/design-system/molecules/email-field',
            },
            {
                id: 'molecules-url-field',
                label: 'URLField',
                href: '/admin/design-system/molecules/url-field',
            },
            {
                id: 'molecules-field-group',
                label: 'FieldGroup',
                href: '/admin/design-system/molecules/field-group',
            },
            {
                id: 'molecules-input-group',
                label: 'InputGroup',
                href: '/admin/design-system/molecules/input-group',
            },
            {
                id: 'molecules-field-set',
                label: 'FieldSet',
                href: '/admin/design-system/molecules/field-set',
            },
            {
                id: 'molecules-combobox',
                label: 'Combobox',
                href: '/admin/design-system/molecules/combobox',
            },
            {
                id: 'molecules-multi-select',
                label: 'MultiSelect',
                href: '/admin/design-system/molecules/multi-select',
            },
            {
                id: 'molecules-segmented-control',
                label: 'SegmentedControl',
                href: '/admin/design-system/molecules/segmented-control',
            },
            {
                id: 'molecules-accordion',
                label: 'Accordion',
                href: '/admin/design-system/molecules/accordion',
            },
            {
                id: 'molecules-tooltip',
                label: 'Tooltip',
                href: '/admin/design-system/molecules/tooltip',
            },
            {
                id: 'molecules-popover',
                label: 'Popover',
                href: '/admin/design-system/molecules/popover',
            },
            {
                id: 'molecules-dropdown-menu',
                label: 'DropdownMenu',
                href: '/admin/design-system/molecules/dropdown-menu',
            },
            {
                id: 'molecules-alert',
                label: 'Alert',
                href: '/admin/design-system/molecules/alert',
            },
            {
                id: 'molecules-toast',
                label: 'Toast',
                href: '/admin/design-system/molecules/toast',
            },
            {
                id: 'molecules-empty-state',
                label: 'EmptyState',
                href: '/admin/design-system/molecules/empty-state',
            },
            {
                id: 'molecules-data-table',
                label: 'DataTable',
                href: '/admin/design-system/molecules/data-table',
            },
            {
                id: 'molecules-filter-bar',
                label: 'FilterBar',
                href: '/admin/design-system/molecules/filter-bar',
            },
            {
                id: 'molecules-breadcrumb',
                label: 'Breadcrumb',
                href: '/admin/design-system/molecules/breadcrumb',
            },
            {
                id: 'molecules-pagination',
                label: 'Pagination',
                href: '/admin/design-system/molecules/pagination',
            },
            {
                id: 'molecules-tabs',
                label: 'Tabs',
                href: '/admin/design-system/molecules/tabs',
            },
            {
                id: 'molecules-timeline',
                label: 'Timeline',
                href: '/admin/design-system/molecules/timeline',
            },
            {
                id: 'molecules-dialog',
                label: 'Dialog',
                href: '/admin/design-system/molecules/dialog',
            },
            {
                id: 'molecules-drawer',
                label: 'Drawer',
                href: '/admin/design-system/molecules/drawer',
            },
        ],
    },
    { id: 'components', label: 'Components', comingSoon: true },
    {
        id: 'organisms',
        label: 'Organisms',
        children: [
            {
                id: 'organisms-detail-header',
                label: 'DetailHeader',
                href: '/admin/design-system/organisms/detail-header',
            },
            {
                id: 'organisms-detail-section',
                label: 'DetailSection',
                href: '/admin/design-system/organisms/detail-section',
            },
            {
                id: 'organisms-document-group-list',
                label: 'DocumentGroupList',
                href: '/admin/design-system/organisms/document-group-list',
            },
            {
                id: 'organisms-match-comparison-table',
                label: 'MatchComparisonTable',
                href: '/admin/design-system/organisms/match-comparison-table',
            },
            {
                id: 'organisms-screening-stages-table',
                label: 'ScreeningStagesTable',
                href: '/admin/design-system/organisms/screening-stages-table',
            },
            {
                id: 'organisms-applicant-summary-card',
                label: 'ApplicantSummaryCard',
                href: '/admin/design-system/organisms/applicant-summary-card',
            },
            {
                id: 'organisms-applicant-resume-overview',
                label: 'ApplicantResumeOverview',
                href: '/admin/design-system/organisms/applicant-resume-overview',
            },
            {
                id: 'organisms-application-timeline',
                label: 'ApplicationTimeline',
                href: '/admin/design-system/organisms/application-timeline',
            },
        ],
    },
    { id: 'patterns', label: 'Patterns', comingSoon: true },
    { id: 'templates', label: 'Templates', comingSoon: true },
    { id: 'shells', label: 'Shells', comingSoon: true },
    { id: 'systems', label: 'Systems', comingSoon: true },
];

export const architectureStack = [
    'Foundation',
    'Tokens',
    'Icons',
    'Atoms',
    'Molecules',
    'Components',
    'Organisms',
    'Patterns',
    'Templates',
    'Product Shells',
    'UX Systems',
] as const;

/** Production import lanes (audit Finding G-1 / Layer H). UI Lab is canonical; product still uses legacy paths where noted. */
export type ProductionImportLaneRow = {
    lane: string;
    importRoots: string;
    role: string;
    guidance: string;
};

export const productionImportLanes: ProductionImportLaneRow[] = [
    {
        lane: 'Canonical',
        importRoots: '@/Components/atoms · molecules · organisms',
        role: 'Design-system source of truth; all UI Lab sections demo this lane.',
        guidance:
            'Default for new UI. Use surface on controls (e.g. Button/Input surface="public" | "admin"). Card uses padding (not legacy density).',
    },
    {
        lane: 'Legacy V2 (Public / UI / Account)',
        importRoots: '@/Components/Public · UI · Account/*',
        role: 'Pre-canonical adapters and domain-specific wrappers.',
        guidance:
            'Do not treat as canonical in new pages. FE-301: Button → atoms/Button. FE-302: Card → atoms/Card (account 2026-10-10; public Apply + TalentPool 2026-10-12). Account/StatusBadge → atoms/StatusBadge (FE-303). UI/Card adapter retained for CommandCenter + barrel; Public/Button alias optional rollback.',
    },
    {
        lane: 'Admin corporate (Admin/ui)',
        importRoots: '@/Components/Admin/ui',
        role: 'Admin barrel adapters and intentional surface split.',
        guidance:
            'Barrel re-exports may differ from molecules (e.g. FilterBar duplicate — FE-201). Prefer direct molecules/atoms imports when adding admin list UI; use Admin/ui adapters only when matching existing barrel consumers.',
    },
];

export const foundationStateVocabulary = [
    'Idle',
    'Loading',
    'Success',
    'Error',
    'Disabled',
    'Readonly',
    'Processing',
    'Permission Denied',
    'Not Found',
    'Offline',
    'Stale',
] as const;

/** Tailwind spacing steps used across OurNear layouts (visual lab reference). */
export const spacingScalePx = [
    { token: '1', px: 4 },
    { token: '2', px: 8 },
    { token: '3', px: 12 },
    { token: '4', px: 16 },
    { token: '5', px: 20 },
    { token: '6', px: 24 },
    { token: '8', px: 32 },
    { token: '10', px: 40 },
    { token: '12', px: 48 },
] as const;
