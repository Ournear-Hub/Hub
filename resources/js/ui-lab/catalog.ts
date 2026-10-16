export type ComponentCatalogEntry = {
    name: string;
    category: string;
    status: 'active' | 'coming-later';
    source: string;
    variants: string[];
    sizes: string[];
    dependencies: string[];
    accessibility: string[];
    compatibility: string[];
};

export const ourNearIconCatalog: ComponentCatalogEntry = {
    name: 'OurNearIcon',
    category: 'Foundation',
    status: 'active',
    source: 'resources/js/icons/OurNearIcon.tsx',
    variants: ['registry name or alias', 'inline spinner', 'Tabler-backed entries'],
    sizes: ['xs (16px)', 'sm (20px)', 'md (24px)', 'lg (32px)', 'xl (40px)'],
    dependencies: [
        'icons/registry.ts',
        'icons/tablerComponents.ts',
        'icons/sizes.ts',
        'foundation/tokens iconDefaults.stroke',
    ],
    accessibility: [
        'decorative={true} by default → aria-hidden',
        'decorative={false} requires aria-label (dev warn if missing)',
        'Icon-only controls: label on button/link, icon stays decorative',
    ],
    compatibility: [
        'V2 legacy UI/icons unchanged — not migrated in PR-4M',
        'No second Icon component or registry',
        'Direct Tabler imports prohibited outside tablerComponents.ts',
    ],
};

export const buttonCatalog: ComponentCatalogEntry = {
    name: 'Button',
    category: 'Atom',
    status: 'active',
    source: 'Components/atoms/Button.tsx',
    variants: ['primary', 'secondary', 'outline', 'ghost', 'destructive', 'link', 'accent (alias)', 'brand (alias)', 'danger (alias)'],
    sizes: ['sm', 'md', 'lg'],
    dependencies: ['Foundation tokens', 'OurNearIcon (loading / icons)', 'design-system/classNames focusRing'],
    accessibility: ['Native button or Inertia Link for internal href', 'disabled + aria-busy when loading', 'Visible label text'],
    compatibility: [
        'Production account/public consumers import atoms/Button with surface="public"',
        'Components/Public/Button.tsx — legacy V2 alias (delegates to atoms/Button)',
        'Components/Admin/ui/Button.tsx (surface admin adapter)',
    ],
};

export const textCatalog: ComponentCatalogEntry = {
    name: 'Text',
    category: 'Atom',
    status: 'active',
    source: 'Components/atoms/Text.tsx',
    variants: ['body', 'secondary', 'caption', 'eyebrow'],
    sizes: ['— (variant-driven scale)'],
    dependencies: ['design-system/classNames typography map'],
    accessibility: ['Semantic element via as prop (p, span, div)', 'Visible text carries meaning; tones supplement color'],
    compatibility: ['Existing raw typography classes unchanged across app'],
};

export const headingCatalog: ComponentCatalogEntry = {
    name: 'Heading',
    category: 'Atom',
    status: 'active',
    source: 'Components/atoms/Heading.tsx',
    variants: ['level 1–6 → pageTitle, sectionTitle, cardTitle, body/secondary/caption semibold'],
    sizes: ['— (level-driven scale)'],
    dependencies: ['design-system/classNames typography map'],
    accessibility: ['Renders native h1–h6 matching level prop', 'One h1 per page remains a page responsibility'],
    compatibility: ['Existing heading markup in pages not migrated in PR-3B'],
};

export const labelCatalog: ComponentCatalogEntry = {
    name: 'Label',
    category: 'Atom',
    status: 'active',
    source: 'Components/atoms/Label.tsx',
    variants: ['default', 'required indicator'],
    sizes: ['— (text-sm)'],
    dependencies: ['design-system/classNames labelBase'],
    accessibility: ['Native label + htmlFor', 'Required: visible asterisk + sr-only “(required)”'],
    compatibility: ['Components/Admin/ui/Label.tsx unchanged (duplicate styling, future alias)'],
};

export const fileFieldCatalog: ComponentCatalogEntry = {
    name: 'FileField',
    category: 'Molecule',
    status: 'active',
    source: 'Components/molecules/FileField.tsx',
    variants: ['surface: public | account | admin | corporate'],
    sizes: ['block w-full native file control'],
    dependencies: ['fileInputBase', 'fileInputAdminBase', 'controlError', 'focusRing'],
    accessibility: [
        'Native input type=file',
        'ref → HTMLInputElement',
        'Works with FormField label/htmlFor',
        'error → aria-invalid',
    ],
    compatibility: [
        'V2: native inputs only (w-full text-sm; some sr-only + custom button) — unchanged',
        'No reusable FileField adapter',
    ],
};

export const dateFieldCatalog: ComponentCatalogEntry = {
    name: 'DateField',
    category: 'Molecule',
    status: 'active',
    source: 'Components/molecules/DateField.tsx',
    variants: ['surface: public | account | admin | corporate'],
    sizes: ['inherits Input control scale (text-sm)'],
    dependencies: ['Input (type=date)', 'controlBase', 'adminControlBase', 'controlError', 'showPicker on click'],
    accessibility: [
        'Native input type=date',
        'ref → HTMLInputElement',
        'Works with FormField label/htmlFor',
        'error → aria-invalid',
        'min / max / required native semantics',
    ],
    compatibility: [
        'V2: canonical Input type=date in Admin filters, Analytics, Account documents — unchanged',
        'Components/Admin/ui/Input.tsx adapter unchanged',
        'No DateField adapter or mass migration in PR-3N',
    ],
};

export const timeFieldCatalog: ComponentCatalogEntry = {
    name: 'TimeField',
    category: 'Molecule',
    status: 'active',
    source: 'Components/molecules/TimeField.tsx',
    variants: ['surface: public | account | admin | corporate'],
    sizes: ['inherits Input control scale (text-sm)'],
    dependencies: ['Input (type=time)', 'controlBase', 'adminControlBase', 'controlError', 'showPicker on click'],
    accessibility: [
        'Native input type=time',
        'ref → HTMLInputElement',
        'Works with FormField label/htmlFor',
        'error → aria-invalid',
        'min / max / step / required native semantics',
    ],
    compatibility: [
        'V2: no type=time usage found; datetime-local on Input unchanged — outside TimeField',
        'No TimeField adapter or mass migration in PR-3O',
    ],
};

export const dateTimeFieldCatalog: ComponentCatalogEntry = {
    name: 'DateTimeField',
    category: 'Molecule',
    status: 'active',
    source: 'Components/molecules/DateTimeField.tsx',
    variants: ['surface: public | account | admin | corporate'],
    sizes: ['inherits Input control scale (text-sm)'],
    dependencies: ['Input (type=datetime-local)', 'controlBase', 'adminControlBase', 'controlError', 'showPicker on click'],
    accessibility: [
        'Native input type=datetime-local',
        'ref → HTMLInputElement',
        'Works with FormField label/htmlFor',
        'error → aria-invalid',
        'min / max / step / required native semantics',
    ],
    compatibility: [
        'V2: Input type=datetime-local in Tasks, Interviews, Campaigns, FollowUpTaskForm, ApplicationDrawer — unchanged',
        'No DateTimeField adapter or mass migration in PR-3P',
    ],
};

export const numberFieldCatalog: ComponentCatalogEntry = {
    name: 'NumberField',
    category: 'Molecule',
    status: 'active',
    source: 'Components/molecules/NumberField.tsx',
    variants: ['surface: public | account | admin | corporate'],
    sizes: ['inherits Input control scale (text-sm)'],
    dependencies: ['Input (type=number)', 'controlBase', 'adminControlBase', 'controlError'],
    accessibility: [
        'Native input type=number',
        'ref → HTMLInputElement',
        'Works with FormField label/htmlFor',
        'error → aria-invalid',
        'min / max / step native semantics',
    ],
    compatibility: [
        'V2: Input type=number (JobForm, Candidates, Automations, match filters, etc.) — unchanged',
        'No NumberField adapter or mass migration in PR-3Q',
    ],
};

export const emailFieldCatalog: ComponentCatalogEntry = {
    name: 'EmailField',
    category: 'Molecule',
    status: 'active',
    source: 'Components/molecules/EmailField.tsx',
    variants: ['surface: public | account | admin | corporate'],
    sizes: ['inherits Input control scale (text-sm)'],
    dependencies: ['Input (type=email)', 'controlBase', 'adminControlBase', 'controlError'],
    accessibility: [
        'Native input type=email',
        'ref → HTMLInputElement',
        'Works with FormField label/htmlFor',
        'error → aria-invalid',
        'autoComplete / inputMode from caller',
    ],
    compatibility: [
        'Architecture phase only — V2 Input type=email unchanged',
        'No adapters, migration, or auth/admin refactors in PR-3R',
    ],
};

export const urlFieldCatalog: ComponentCatalogEntry = {
    name: 'URLField',
    category: 'Molecule',
    status: 'active',
    source: 'Components/molecules/URLField.tsx',
    variants: ['surface: public | account | admin | corporate'],
    sizes: ['inherits Input control scale (text-sm)'],
    dependencies: ['Input (type=url)', 'controlBase', 'adminControlBase', 'controlError'],
    accessibility: [
        'Native input type=url',
        'ref → HTMLInputElement',
        'Works with FormField label/htmlFor',
        'error → aria-invalid',
        'autoComplete / inputMode from caller',
    ],
    compatibility: [
        'Architecture phase only — V2 Public FormField type=url (Profile) unchanged',
        'No adapters or migration in PR-3S',
    ],
};

export const inputGroupCatalog: ComponentCatalogEntry = {
    name: 'InputGroup',
    category: 'Molecule',
    status: 'active',
    source: 'Components/molecules/InputGroup.tsx',
    variants: ['leading addon', 'trailing addon', 'action slot'],
    sizes: ['— (inherits child control height)'],
    dependencies: [
        'inputGroupRoot',
        'inputGroupAddon',
        'inputGroupControlSlot join recipes',
        'Canonical Input / field molecules as child',
    ],
    accessibility: [
        'Decorative leading/trailing: aria-hidden on addon span',
        'Action remains independent button/control',
        'No role=group for styling',
        'FormField forwards id/ARIA to nested control',
    ],
    compatibility: [
        'Architecture phase only — V2 uses separate SearchBar, PasswordField toggle, plain URL placeholders',
        'No InputGroup in V2; V2 unchanged',
    ],
};

export const breadcrumbCatalog: ComponentCatalogEntry = {
    name: 'Breadcrumb',
    category: 'Molecule',
    status: 'active',
    source: 'Components/molecules/Breadcrumb.tsx',
    variants: ['items: label + optional href'],
    sizes: ['— (text-sm trail)'],
    dependencies: ['Link atom', 'breadcrumb* recipes in classNames.ts'],
    accessibility: [
        'nav aria-label (default "Breadcrumb")',
        'ol/li list semantics',
        'aria-current="page" on final item',
        'Decorative "/" separators (aria-hidden)',
    ],
    compatibility: [
        'V2 Navigation/Breadcrumbs.tsx unchanged (Inertia Link)',
        'Caller-owned hierarchy and hrefs',
    ],
};

export const paginationCatalog: ComponentCatalogEntry = {
    name: 'Pagination',
    category: 'Molecule',
    status: 'active',
    source: 'Components/molecules/Pagination.tsx',
    variants: ['Laravel-shaped links: url | null, label, active'],
    sizes: ['— (text-sm pill controls, flex-wrap row)'],
    dependencies: ['Link atom', 'pagination* recipes in classNames.ts'],
    accessibility: [
        'nav aria-label (default "Pagination")',
        'aria-current="page" on active item (non-link span)',
        'Previous/next: visible label or aria-label when glyph-only',
        'Ellipsis gaps: aria-hidden decorative span',
    ],
    compatibility: [
        'V2 Components/Pagination.tsx unchanged (~39 consumers, Inertia Link)',
        'Caller-owned links array; no paginator/Inertia coupling',
    ],
};

export const dataTableCatalog: ComponentCatalogEntry = {
    name: 'DataTable',
    category: 'Molecule',
    status: 'active',
    source: 'Components/molecules/DataTable.tsx',
    variants: ['Column-definition admin list table', 'Optional row click + selectedKey highlight'],
    sizes: ['— (text-sm table, horizontal scroll wrapper)'],
    dependencies: ['EmptyState molecule', 'dataTable* recipes in classNames.ts'],
    accessibility: [
        'Native table semantics (thead/tbody/th/td)',
        'Row click via tr onClick when onRowClick provided — same keyboard gap as V2',
    ],
    compatibility: [
        'PR-5M M-B: 29 admin list consumers import molecules/DataTable (2026-10-09)',
        'Legacy Admin/ui/DataTable.tsx retained; no re-export shim',
        'No pagination/filter/sort inside molecule; Pagination composed as sibling',
    ],
};

export const filterBarCatalog: ComponentCatalogEntry = {
    name: 'FilterBar',
    category: 'Molecule',
    status: 'active',
    source: 'Components/molecules/FilterBar.tsx',
    variants: ['Form grid shell with optional className column overrides'],
    sizes: ['— (default md:grid-cols-4)'],
    dependencies: ['filterBarRoot recipe in classNames.ts'],
    accessibility: ['Native form submit; field labels owned by children'],
    compatibility: [
        'PR-5O O-B: ~28 admin list consumers import molecules/FilterBar directly (2026-10-09)',
        'Legacy Admin/ui/FilterBar.tsx — separate implementation still in Admin/ui barrel until FE-201',
        'JobsSearchPanel and account searches not in scope',
    ],
};

export const detailSectionCatalog: ComponentCatalogEntry = {
    name: 'DetailSection',
    category: 'Organism',
    status: 'active',
    source: 'Components/organisms/DetailSection.tsx',
    variants: ['Bordered subsection', 'Default Edit/Add controls or actions slot', 'Optional description'],
    sizes: ['— (p-5 card surface)'],
    dependencies: ['detailSection* recipes in classNames.ts'],
    accessibility: [
        'Native section with aria-labelledby on title',
        'Actions are caller-owned buttons or links',
        'Heading level h2 for nested profile blocks',
    ],
    compatibility: [
        'Account candidate profile — About section (first production consumer)',
        'CandidateProfileOverview local Section helper retained for other blocks',
        'Complements DetailHeader (page/entity title) — not a replacement',
    ],
};

export const applicationTimelineCatalog: ComponentCatalogEntry = {
    name: 'ApplicationTimeline',
    category: 'Organism',
    status: 'active',
    source: 'Components/organisms/ApplicationTimeline.tsx',
    variants: ['Populated history', 'Empty message', 'hideWhenEmpty (employer)', 'loading', 'error'],
    sizes: ['- (DetailSection surface)'],
    dependencies: ['DetailSection organism', 'Timeline molecule', 'Spinner atom', 'Alert molecule', 'detailSection* tokens'],
    accessibility: [
        'DetailSection provides titled section landmark',
        'Timeline list exposes aria-label (default: Application timeline)',
        'Loading uses Spinner role=status; errors use Alert',
    ],
    compatibility: [
        'Account Applications/Show — candidate application detail (production)',
        'Account Employer Applications/Show — employer application detail (production, hideWhenEmpty)',
        'Account Employer Submissions/Show — submission timeline (production, hideWhenEmpty)',
        'Admin ApplicationDrawer — Timeline tab (production)',
    ],
};

export const applicantResumeOverviewCatalog: ComponentCatalogEntry = {
    name: 'ApplicantResumeOverview',
    category: 'Organism',
    status: 'active',
    source: 'Components/organisms/ApplicantResumeOverview.tsx',
    variants: ['Optional professional summary dl', 'Resume actions slot or empty message'],
    sizes: ['— (compact card surface)'],
    dependencies: ['cardDensity.compact', 'applicantResumeOverview* recipes in classNames.ts'],
    accessibility: [
        'section landmark with aria-label (default: Applicant resume overview)',
        'Resume heading uses h2; summary uses h3 + definition list',
        'Actions supplied by caller — no internal routing',
    ],
    compatibility: [
        'Employer Applications/Show — resume snapshot block (production consumer)',
        'Employer Submissions/Show — submission resume snapshot (production consumer)',
        'Complements ApplicantSummaryCard list rows — not a duplicate',
    ],
};

export const applicantSummaryCardCatalog: ComponentCatalogEntry = {
    name: 'ApplicantSummaryCard',
    category: 'Organism',
    status: 'active',
    source: 'Components/organisms/ApplicantSummaryCard.tsx',
    variants: ['Photo or initials avatar', 'Optional meta, notices, and actions slots'],
    sizes: ['— (p-4 sm:p-5 card)'],
    dependencies: ['Account StatusBadge', 'applicantSummaryCard* recipes in classNames.ts'],
    accessibility: [
        'article landmark with h2 candidate name',
        'Decorative avatar uses empty alt; initials avatar aria-hidden',
        'Actions and notices are caller-provided nodes',
    ],
    compatibility: [
        'RecruiterCandidateSummary feature wrapper — Applicants + Submissions indexes',
        'Application/submission mode, contact rules, and hrefs stay in feature layer',
        'Does not fetch data or decide contact visibility',
    ],
};

export const detailHeaderCatalog: ComponentCatalogEntry = {
    name: 'DetailHeader',
    category: 'Organism',
    status: 'active',
    source: 'Components/organisms/DetailHeader.tsx',
    variants: ['headingLevel 1–3', 'Optional eyebrow, description, meta, actions slots'],
    sizes: ['— (layout-driven)'],
    dependencies: ['Heading', 'Text', 'Stack', 'detailHeader* recipes in classNames.ts'],
    accessibility: [
        'Native header landmark',
        'Heading level from headingLevel prop (default h1)',
        'Actions and meta are caller-provided — labels on interactive controls',
    ],
    compatibility: [
        'Spec §11 DetailHeader — Detail pattern §12 (not List index PageHeader — PR-5P)',
        'Account Employer Submissions/Show — requirement context + candidate entity headers (production)',
        'Account Employer Applications/Show — applicant-for context + candidate entity headers (production)',
        'Account Applications/Show — candidate application + job headers (production)',
        'V2 Components/UI/SectionHeader.tsx unchanged on other surfaces until explicit adoption',
        'Does not embed FilterBar, DataTable, or tabs',
    ],
};

export const matchComparisonTableCatalog: ComponentCatalogEntry = {
    name: 'MatchComparisonTable',
    category: 'Organism',
    status: 'active',
    source: 'Components/organisms/MatchComparisonTable.tsx',
    variants: ['Bordered comparison table', 'Optional disclaimer and snapshot footnote'],
    sizes: ['— (responsive horizontal scroll)'],
    dependencies: ['Admin StatusBadge', 'matchComparisonTable* recipes in classNames.ts'],
    accessibility: [
        'Native table with column headers (scope="col")',
        'Factor cells use row headers (scope="row")',
        'Default aria-label on table; overridable',
        'State column uses StatusBadge — caller supplies display labels',
    ],
    compatibility: [
        'ApplicationDrawer Screening tab — Candidate vs Job block (production consumer)',
        'ScreeningMatchService / job_comparison payloads mapped on the page — not in organism',
        'Does not replace Timeline, ScreeningStagesTable, or screening action buttons',
    ],
};

export const screeningStagesTableCatalog: ComponentCatalogEntry = {
    name: 'ScreeningStagesTable',
    category: 'Organism',
    status: 'active',
    source: 'Components/organisms/ScreeningStagesTable.tsx',
    variants: ['Horizontal scroll audit table', 'Optional empty message when rows=[]'],
    sizes: ['— (text-sm table)'],
    dependencies: ['Admin StatusBadge', 'screeningStagesTable* recipes in classNames.ts'],
    accessibility: [
        'Native table with column headers (scope="col")',
        'Default aria-label on table; overridable',
        'Status column uses StatusBadge — caller supplies display labels',
    ],
    compatibility: [
        'ApplicationDrawer Screening tab — Screening stages block (production consumer)',
        'Server supplies ScreeningStageRow[] — mapping on the page',
        'Does not replace MatchComparisonTable, Timeline, or screening action buttons',
    ],
};

export const documentGroupListCatalog: ComponentCatalogEntry = {
    name: 'DocumentGroupList',
    category: 'Organism',
    status: 'active',
    source: 'Components/organisms/DocumentGroupList.tsx',
    variants: ['Grouped list from flat items[]', 'Optional identity, meta, actions, extra per row'],
    sizes: ['— (Card standard padding per row)'],
    dependencies: ['Card', 'Stack', 'Text', 'EmptyState', 'documentGroupList* recipes in classNames.ts'],
    accessibility: [
        'Group sections with h3 labels',
        'List semantics (ul/li) within each group',
        'Interactive controls only via caller actions/extra slots',
    ],
    compatibility: [
        'Spec §11 — upload/replace/delete on page + Form/File System (not in organism)',
        'V2 Components/Documents/DocumentGroupList.tsx unchanged — 2 account index consumers',
        'API mirrors V2 DocumentGroupItem shape for future adoption swap',
    ],
};

export const tabsCatalog: ComponentCatalogEntry = {
    name: 'Tabs',
    category: 'Molecule',
    status: 'active',
    source: 'Components/molecules/Tabs.tsx',
    variants: ['horizontal tablist', 'panels map or children slot', 'head-only tablist when no panels'],
    sizes: ['— (text-sm underlined row)'],
    dependencies: ['tabs* recipes in classNames.ts'],
    accessibility: [
        'tablist + tab + tabpanel roles',
        'Required aria-label on tablist',
        'aria-selected, aria-controls, id/aria-labelledby pairs when panels wired',
        'Roving tabindex; ArrowLeft/ArrowRight, Home, End',
        'Not radiogroup (see SegmentedControl)',
    ],
    compatibility: [
        'V2 Admin/ui/Tabs.tsx unchanged (~15 consumers)',
        'ProfileShell / SettingsNav / query pill rows remain navigation or ad-hoc — not migrated',
    ],
};

export const drawerCatalog: ComponentCatalogEntry = {
    name: 'Drawer',
    category: 'Molecule',
    status: 'active',
    source: 'Components/molecules/Drawer.tsx',
    variants: ['right full-height panel', 'size md | xl (sm+ max-width)'],
    sizes: ['md (sm:max-w-lg)', 'xl (sm:max-w-3xl)'],
    dependencies: ['drawer* recipes in classNames.ts', 'Button (admin surface)'],
    accessibility: [
        'aside role="dialog" aria-modal="true"',
        'aria-labelledby from title (useId per instance)',
        'Optional aria-describedby from description',
        'Backdrop + Close; Escape closes',
        'No focus trap (matches V2 Drawer)',
    ],
    compatibility: [
        'V2 Admin/ui/Drawer.tsx unchanged',
        'ApplicationDrawer.tsx is domain composition inside Drawer shell — not a DS primitive',
        'Mobile nav sheets remain page-local',
    ],
};

export const dialogCatalog: ComponentCatalogEntry = {
    name: 'Dialog',
    category: 'Molecule',
    status: 'active',
    source: 'Components/molecules/Dialog.tsx',
    variants: ['centered modal shell', 'optional description', 'optional footer actions'],
    sizes: ['max-w-md (default panel)'],
    dependencies: ['dialog* recipes in classNames.ts', 'Button (admin surface)'],
    accessibility: [
        'role="dialog" aria-modal="true"',
        'aria-labelledby from title (useId per instance)',
        'Optional aria-describedby from description',
        'Backdrop button + header Close; Escape closes',
        'No focus trap (matches V2 Modal)',
    ],
    compatibility: [
        'V2 Admin/ui/Modal.tsx unchanged',
        'V2 ConfirmDialog / Drawer not migrated',
        'Popover / Toast / mobile nav sheets remain distinct',
    ],
};

export const timelineCatalog: ComponentCatalogEntry = {
    name: 'Timeline',
    category: 'Molecule',
    status: 'active',
    source: 'Components/molecules/Timeline.tsx',
    variants: ['vertical ordered event list'],
    sizes: ['— (text-sm title + xs meta)'],
    dependencies: ['timeline* recipes in classNames.ts'],
    accessibility: [
        'Native ol/li (chronological order meaningful)',
        'Optional aria-label on list',
        'Decorative rail marker aria-hidden',
        'Not tablist, progressbar, or feed landmark',
    ],
    compatibility: [
        'V2 Admin/ui/Timeline.tsx unchanged (~10+ admin usages)',
        'ActivityFeed (card list) remains separate V2 component — not merged',
        'Account inline timelines not migrated in PR-5F',
    ],
};

export const emptyStateCatalog: ComponentCatalogEntry = {
    name: 'EmptyState',
    category: 'Molecule',
    status: 'active',
    source: 'Components/molecules/EmptyState.tsx',
    variants: ['align: center (default) | start', 'optional icon (registry name or node)'],
    sizes: ['sm', 'md (default)', 'lg'],
    dependencies: [
        'emptyStateRoot',
        'emptyStateAlign*',
        'emptyStateSize*',
        'emptyStateTitle*',
        'emptyStateDescription',
        'emptyStateActions',
        'OurNearIcon (optional)',
    ],
    accessibility: [
        'Static presentational block — no role=alert or live region by default',
        'Title as styled text (not heading level)',
        'Icon decorative when title/description present',
        'Actions are caller-provided controls with their own labels',
    ],
    compatibility: [
        'Architecture phase — V2 Components/UI/EmptyState.tsx and Admin/ui/EmptyState.tsx unchanged',
        'Not Alert, Toast, error state, 404, or loading skeleton',
    ],
};

export const toastCatalog: ComponentCatalogEntry = {
    name: 'Toast',
    category: 'Molecule',
    status: 'active',
    source: 'Components/molecules/Toast.tsx',
    variants: ['info', 'success', 'warning', 'error'],
    sizes: ['max-w-sm portaled surface'],
    dependencies: [
        'toastRoot',
        'toastPlacement*',
        'statusTone',
        'alert border/title/message recipes',
        'OurNearIcon',
        'IconButton',
        'createPortal',
    ],
    accessibility: [
        'error → role=alert',
        'info/success/warning → role=status',
        'Dismiss IconButton labeled Dismiss',
        'Does not trap focus',
    ],
    compatibility: [
        'Architecture phase — V2 FlashMessage is inline Inertia flash, not toast',
        'No toast library; no global queue in PR-4E',
    ],
};

export const alertCatalog: ComponentCatalogEntry = {
    name: 'Alert',
    category: 'Molecule',
    status: 'active',
    source: 'Components/molecules/Alert.tsx',
    variants: ['info', 'success', 'warning', 'error'],
    sizes: ['content-driven (text-sm)'],
    dependencies: [
        'statusTone',
        'alertRoot',
        'alertBorder*',
        'OurNearIcon',
        'IconButton (dismiss)',
    ],
    accessibility: [
        'error → role=alert',
        'info/success/warning → role=status',
        'Decorative variant icons; title as plain text (not heading level)',
        'Dismiss: IconButton label Dismiss',
    ],
    compatibility: [
        'Architecture phase — V2 Components/UI/Alert.tsx (tone-based) unchanged',
        'Not Toast, FormField error, or StatusBadge',
    ],
};

export const dropdownMenuCatalog: ComponentCatalogEntry = {
    name: 'DropdownMenu',
    category: 'Molecule',
    status: 'active',
    source: 'Components/molecules/DropdownMenu.tsx',
    variants: ['side: top | right | bottom | left', 'align: start | center | end', 'item: destructive'],
    sizes: ['min-w 10rem content panel'],
    dependencies: [
        'dropdownMenuContent',
        'dropdownMenuItem',
        'dropdownMenuSeparator',
        'react-dom createPortal',
        'focusRing',
    ],
    accessibility: [
        'Trigger: aria-haspopup=menu, aria-expanded, aria-controls',
        'Content: role=menu',
        'Item: button role=menuitem, roving tabIndex',
        'Arrow/Home/End keyboard; Escape; Tab closes without trap',
    ],
    compatibility: [
        'Architecture phase — V2 ad hoc role=menu panels (Header, TopBar), limited keyboard',
        'Not Popover/Select/Combobox; no submenus in PR-4C',
    ],
};

export const popoverCatalog: ComponentCatalogEntry = {
    name: 'Popover',
    category: 'Molecule',
    status: 'active',
    source: 'Components/molecules/Popover.tsx',
    variants: ['side: top | right | bottom | left', 'align: start | center | end'],
    sizes: ['default w-72 max width clamp'],
    dependencies: ['popoverContent', 'react-dom createPortal', 'compound context'],
    accessibility: [
        'Trigger: aria-expanded, aria-controls',
        'Content: stable id; no default dialog/menu roles',
        'Focus first focusable in content on open; return to trigger on close when applicable',
        'Escape + outside pointer dismiss',
        'No focus trap',
    ],
    compatibility: [
        'Architecture phase — V2 has no Popover primitive (Combobox listbox is in-flow)',
        'Distinct from Tooltip (non-interactive) and future Menu/Modal',
    ],
};

export const tooltipCatalog: ComponentCatalogEntry = {
    name: 'Tooltip',
    category: 'Molecule',
    status: 'active',
    source: 'Components/molecules/Tooltip.tsx',
    variants: ['side: top | right | bottom | left', 'align: start | center | end'],
    sizes: ['text-xs, max-w-xs'],
    dependencies: ['tooltipContent', 'tooltipDisabledWrap', 'react-dom createPortal', 'focusRing (disabled wrap)'],
    accessibility: [
        'Supplementary only — not primary label',
        'aria-describedby on trigger while visible (single element child)',
        'role=tooltip on portaled content',
        'Shows on hover and focus; tooltip not tabbable',
        'Icon-only triggers must still expose accessible name on control',
    ],
    compatibility: [
        'Architecture phase — V2 uses native title= on some disabled/placeholder controls',
        'No tooltip library in package.json',
    ],
};

export const accordionCatalog: ComponentCatalogEntry = {
    name: 'Accordion',
    category: 'Molecule',
    status: 'active',
    source: 'Components/molecules/Accordion.tsx',
    variants: ['type: single (default) | multiple', 'collapsible when single (default true)'],
    sizes: ['— (full-width disclosure stack)'],
    dependencies: [
        'accordionRoot',
        'accordionItem',
        'accordionSummary',
        'accordionContent',
        'accordionChevron',
        'OurNearIcon (chevron-down)',
        'focusRing on summary',
    ],
    accessibility: [
        'Native details/summary disclosure per item',
        'summary: aria-expanded, aria-controls → content id',
        'Closed content not keyboard-focusable (native)',
        'Enter/Space on summary (native)',
        'No tablist / dialog / menu roles',
    ],
    compatibility: [
        'Architecture phase — V2 uses standalone details/summary (filters, jobs panel)',
        'No FormField coupling; not tabs or navigation',
    ],
};

export const segmentedControlCatalog: ComponentCatalogEntry = {
    name: 'SegmentedControl',
    category: 'Molecule',
    status: 'active',
    source: 'Components/molecules/SegmentedControl.tsx',
    variants: ['size: sm | md (default md)'],
    sizes: ['sm (min-h-8)', 'md (min-h-9)'],
    dependencies: [
        'segmentedControlRoot',
        'segmentedControlSegmentSm/Md',
        'focusRing',
    ],
    accessibility: [
        'role=radiogroup + role=radio',
        'aria-label required on group',
        'aria-checked on segments',
        'Roving tabindex (selected segment tabIndex=0)',
        'Not tablist / not navigation',
    ],
    compatibility: [
        'Architecture phase — V2 uses ad hoc pill buttons (e.g. Admin Users filters)',
        'No FormField coupling by design',
    ],
};

export const multiSelectCatalog: ComponentCatalogEntry = {
    name: 'MultiSelect',
    category: 'Molecule',
    status: 'active',
    source: 'Components/molecules/MultiSelect.tsx',
    variants: ['surface: public | account | admin | corporate'],
    sizes: ['compound control min-h-10, wrapping chips'],
    dependencies: [
        'multiSelectControl',
        'multiSelectChip',
        'comboboxListbox',
        'OurNearIcon (chevron-down, close)',
        'focusRing',
    ],
    accessibility: [
        'combobox + listbox aria-multiselectable',
        'aria-selected per option',
        'Chip remove: accessible name Remove {label}',
        'ref → search input',
    ],
    compatibility: [
        'Architecture phase — V2 uses checkbox groups / native select multiple',
        'No new dependencies',
    ],
};

export const comboboxCatalog: ComponentCatalogEntry = {
    name: 'Combobox',
    category: 'Molecule',
    status: 'active',
    source: 'Components/molecules/Combobox.tsx',
    variants: ['surface: public | account | admin | corporate'],
    sizes: ['inherits controlBase scale (text-sm)'],
    dependencies: [
        'controlBase',
        'adminControlBase',
        'controlError',
        'comboboxListbox',
        'OurNearIcon (chevron-down)',
    ],
    accessibility: [
        'WAI-ARIA combobox + listbox + option',
        'aria-expanded, aria-controls, aria-activedescendant',
        'aria-autocomplete=list',
        'ref → focusable input',
        'FormField id / aria-describedby / error prop',
    ],
    compatibility: [
        'Architecture phase — V2 uses native Select and filter bars, no Combobox',
        'No Headless UI / Radix; no new dependencies',
    ],
};

export const fieldSetCatalog: ComponentCatalogEntry = {
    name: 'FieldSet',
    category: 'Molecule',
    status: 'active',
    source: 'Components/molecules/FieldSet.tsx',
    variants: ['legend + optional description / error / required indicator'],
    sizes: ['— (section container)'],
    dependencies: ['labelBase', 'helperText', 'errorText', 'native fieldset/legend'],
    accessibility: [
        'Always fieldset + legend',
        'aria-describedby for description or group error on fieldset',
        'Group error does not propagate aria-invalid to children',
        'Legend required indicator does not mark each child required',
    ],
    compatibility: [
        'Architecture phase — V2 ad hoc fieldsets unchanged',
        'Composes with FieldGroup + FormField; no migration in PR-3V',
    ],
};

export const fieldGroupCatalog: ComponentCatalogEntry = {
    name: 'FieldGroup',
    category: 'Molecule',
    status: 'active',
    source: 'Components/molecules/FieldGroup.tsx',
    variants: ['orientation: vertical | horizontal', 'gap: sm | md | lg'],
    sizes: ['— (layout spacing via gap)'],
    dependencies: ['labelBase', 'helperText', 'errorText', 'Tailwind flex layout'],
    accessibility: [
        'fieldset + legend when label set',
        'div wrapper when unlabeled',
        'Group aria-describedby for hint/error',
        'Does not propagate aria-invalid to children',
    ],
    compatibility: [
        'Architecture phase only — V2 ad hoc fieldsets unchanged',
        'Composes with FormField + field molecules; no migration in PR-3T',
    ],
};

export const passwordFieldCatalog: ComponentCatalogEntry = {
    name: 'PasswordField',
    category: 'Molecule',
    status: 'active',
    source: 'Components/molecules/PasswordField.tsx',
    variants: ['surface: public | account | admin | corporate', 'showToggle (default true)'],
    sizes: ['inherits Input; IconButton sm toggle'],
    dependencies: ['Input', 'IconButton', 'OurNearIcon (eye, eye-off)', 'pr-10 when toggle shown'],
    accessibility: [
        'ref → native input',
        'IconButton aria-label Show/Hide password',
        'type=button on toggle',
        'autoComplete from caller',
    ],
    compatibility: ['V2 auth uses plain type=password — no migration', 'PasswordStrength component unchanged'],
};

export const searchFieldCatalog: ComponentCatalogEntry = {
    name: 'SearchField',
    category: 'Molecule',
    status: 'active',
    source: 'Components/molecules/SearchField.tsx',
    variants: ['surface: public | account | admin | corporate'],
    sizes: ['inherits Input control height'],
    dependencies: ['Input', 'OurNearIcon (search)', 'pl-9 inset for icon'],
    accessibility: [
        'Decorative search icon (aria-hidden)',
        'Accessible name via label, aria-label, or FormField',
        'type=search native semantics',
        'ref forwarded to Input',
    ],
    compatibility: [
        'Components/Admin/ui/SearchBar.tsx unchanged (no icon, type=search only)',
        'No admin/job search migrations',
    ],
};

export const formFieldCatalog: ComponentCatalogEntry = {
    name: 'FormField',
    category: 'Molecule',
    status: 'active',
    source: 'Components/molecules/FormField.tsx',
    variants: ['label + control + hint | error'],
    sizes: ['— (field spacing via Label / helperText / errorText)'],
    dependencies: ['Label', 'Text', 'helperText', 'errorText', 'canonical control atoms'],
    accessibility: [
        'Label htmlFor ↔ control id',
        'aria-describedby → ${id}-hint | ${id}-error',
        'aria-invalid + error prop on supported controls when error set',
        'Required indicator on Label only (does not auto-inject required on child)',
    ],
    compatibility: [
        'Components/Public/FormField.tsx unchanged (monolithic input/textarea wrapper)',
        'No production form migrations in PR-3I',
    ],
};

export const switchCatalog: ComponentCatalogEntry = {
    name: 'Switch',
    category: 'Atom',
    status: 'active',
    source: 'Components/atoms/Switch.tsx',
    variants: ['surface: public | account | admin | corporate'],
    sizes: ['h-6 w-11 track (fixed)'],
    dependencies: ['switchInput', 'switchTrackBase', 'switchTrackAdmin', 'focusRing (via peer-focus-visible)'],
    accessibility: [
        'Native checkbox + role=switch',
        'checked = ON, unchecked = OFF',
        'ref forwarding',
        'Space toggles; Label via htmlFor',
    ],
    compatibility: [
        'No legacy Switch UI — Account communication prefs use square checkboxes (unchanged)',
    ],
};

export const radioCatalog: ComponentCatalogEntry = {
    name: 'Radio',
    category: 'Atom',
    status: 'active',
    source: 'Components/atoms/Radio.tsx',
    variants: ['surface: public | account | admin | corporate'],
    sizes: ['h-4 w-4 (fixed)'],
    dependencies: ['radioBase', 'radioAdminBase', 'radioError', 'focusRing'],
    accessibility: [
        'Native input type=radio',
        'ref forwarding',
        'error → aria-invalid',
        'Arrow keys / Space native behavior within name group',
        'Pair with Label via htmlFor',
    ],
    compatibility: ['No legacy Radio component — raw inputs in V2 unchanged (Account register/reviews)'],
};

export const checkboxCatalog: ComponentCatalogEntry = {
    name: 'Checkbox',
    category: 'Atom',
    status: 'active',
    source: 'Components/atoms/Checkbox.tsx',
    variants: ['surface: public | account | admin | corporate'],
    sizes: ['h-4 w-4 (fixed)'],
    dependencies: ['checkboxBase', 'checkboxAdminBase', 'checkboxError', 'focusRing'],
    accessibility: [
        'Native input type=checkbox',
        'ref forwarding',
        'error → aria-invalid',
        'Space / keyboard native behavior',
        'Pair with Label via htmlFor',
    ],
    compatibility: ['No legacy Checkbox component — raw inputs in V2 unchanged'],
};

export const selectCatalog: ComponentCatalogEntry = {
    name: 'Select',
    category: 'Atom',
    status: 'active',
    source: 'Components/atoms/Select.tsx',
    variants: ['surface: public | account | admin | corporate'],
    sizes: ['single control scale (matches Input)'],
    dependencies: ['controlBase', 'adminControlBase', 'controlError'],
    accessibility: [
        'Native select element',
        'ref forwarding',
        'error → aria-invalid',
        'Native keyboard and mobile picker behavior',
        'Works with canonical Label',
    ],
    compatibility: [
        'Production account/public: atoms/Select with surface="public" | "account"',
        'Components/Admin/ui/Select.tsx → thin adapter (surface admin)',
    ],
};

export const textareaCatalog: ComponentCatalogEntry = {
    name: 'Textarea',
    category: 'Atom',
    status: 'active',
    source: 'Components/atoms/Textarea.tsx',
    variants: ['surface: public | account | admin | corporate'],
    sizes: ['min-h-28 (public/account), min-h-24 (admin/corporate)'],
    dependencies: ['controlBase', 'adminControlBase', 'controlError'],
    accessibility: [
        'Native textarea',
        'ref forwarding',
        'error → aria-invalid',
        'aria-describedby from parent',
        'Works with canonical Label',
    ],
    compatibility: [
        'Production account/public: atoms/Textarea with surface="public" | "account"',
        'Components/Admin/ui/Textarea.tsx → thin adapter (surface admin)',
    ],
};

export const inputCatalog: ComponentCatalogEntry = {
    name: 'Input',
    category: 'Atom',
    status: 'active',
    source: 'Components/atoms/Input.tsx',
    variants: ['surface: public | account | admin | corporate'],
    sizes: ['single control scale (text-sm)'],
    dependencies: ['controlBase', 'adminControlBase', 'controlError', 'classNames focusRing'],
    accessibility: [
        'Native input element',
        'ref forwarding',
        'error → aria-invalid',
        'Supports aria-describedby from parent',
        'Date/time picker showPicker on admin-style click',
    ],
    compatibility: [
        'Production account/public: atoms/Input with surface="public" | "account"',
        'Components/Admin/ui/Input.tsx → thin adapter (surface admin) for barrel consumers',
        'adminControlClass re-exported from classNames.adminControlBase',
    ],
};

export const stackCatalog: ComponentCatalogEntry = {
    name: 'Stack',
    category: 'Atom',
    status: 'active',
    source: 'Components/atoms/Stack.tsx',
    variants: ['gap: xs | sm | md | lg | xl', 'align: stretch | start | center | end'],
    sizes: ['— (gap-driven vertical rhythm)'],
    dependencies: ['stackRoot', 'stackGap*', 'stackAlign*'],
    accessibility: ['Non-semantic div', 'No list/landmark roles'],
    compatibility: [
        'Distinct from FieldGroup (form legend + orientation)',
        'Distinct from Container width/gutters',
        'V2 space-y patterns unchanged',
    ],
};

export const aspectRatioCatalog: ComponentCatalogEntry = {
    name: 'AspectRatio',
    category: 'Atom',
    status: 'active',
    source: 'Components/atoms/AspectRatio.tsx',
    variants: ['ratio: square | video | portrait'],
    sizes: ['— (width from parent; height from CSS aspect-ratio)'],
    dependencies: ['aspectRatioRoot', 'aspectRatioSquare', 'aspectRatioVideo', 'aspectRatioPortrait'],
    accessibility: ['Non-semantic div', 'Children (e.g. Image) own media semantics'],
    compatibility: [
        'Image atom does not own aspect ratio (PR-4P)',
        'Card/Avatar unchanged',
        'V2 fixed h/w and single aspect-[4/5] pattern unchanged',
    ],
};

export const clusterCatalog: ComponentCatalogEntry = {
    name: 'Cluster',
    category: 'Atom',
    status: 'active',
    source: 'Components/atoms/Cluster.tsx',
    variants: [
        'gap: xs | sm | md | lg | xl',
        'align: stretch | start | center | end | baseline',
        'wrap: false (default) | true',
    ],
    sizes: ['— (gap-driven horizontal rhythm)'],
    dependencies: ['clusterRoot', 'clusterGap* (aliases stack gap scale)', 'clusterAlign*', 'clusterWrap'],
    accessibility: ['Non-semantic div', 'No toolbar/list/group roles'],
    compatibility: [
        'Complements Stack (vertical)',
        'Distinct from InputGroup join layout',
        'V2 flex row patterns unchanged',
    ],
};

export const containerCatalog: ComponentCatalogEntry = {
    name: 'Container',
    category: 'Atom',
    status: 'active',
    source: 'Components/atoms/Container.tsx',
    variants: ['size: sm | md | lg | xl | 2xl | full', 'gutter: none | sm | md | lg'],
    sizes: ['sm 768px', 'md 1152px (default)', 'lg 1280px', 'xl/2xl/full per Tailwind'],
    dependencies: ['containerRoot', 'containerSize*', 'containerGutter*'],
    accessibility: ['Non-semantic div', 'No auto landmarks or aria'],
    compatibility: [
        'V2 PageContainer / AdminContainer unchanged',
        'Distinct from Card visual surface',
    ],
};

export const cardCatalog: ComponentCatalogEntry = {
    name: 'Card',
    category: 'Atom',
    status: 'active',
    source: 'Components/atoms/Card.tsx',
    variants: ['padding: none | compact | standard (default) | feature'],
    sizes: ['— (density-driven padding + radius/shadow from cardDensity)'],
    dependencies: ['cardDensity recipes in design-system/classNames.ts'],
    accessibility: [
        'Non-semantic div container',
        'No auto role/aria — caller provides headings/landmarks when needed',
        'Not interactive',
    ],
    compatibility: [
        'FE-302: Account pages import atoms/Card (padding prop; optional as= section/article)',
        'Components/UI/Card.tsx — V2 adapter (barrel + Admin/Hiring/CommandCenter); public Pages use atoms/Card per FE-302 (2026-10-12)',
        'Admin/ui/Card unchanged (admin lane)',
        'No CardHeader/Body/Footer compound API',
    ],
};

export const linkButtonCatalog: ComponentCatalogEntry = {
    name: 'LinkButton',
    category: 'Atom',
    status: 'active',
    source: 'Components/atoms/LinkButton.tsx',
    variants: ['matches Button: primary, secondary, outline, ghost, destructive, link (+ legacy aliases)'],
    sizes: ['sm', 'md (default)', 'lg'],
    dependencies: [
        'design-system/buttonVisuals (shared class recipes with Button)',
        'focusRing',
        'Caller-owned leadingIcon/trailingIcon (e.g. OurNearIcon)',
    ],
    accessibility: [
        'Native <a> — not role=button',
        'disabled → aria-disabled, tabIndex -1, preventDefault on click',
        'Visible link text; no auto aria-label',
    ],
    compatibility: [
        'Distinct from Link (text) and Button (action / submit)',
        'No Inertia — unlike frozen Button href mode',
        'V2 CTAs unchanged in PR-4Q',
    ],
};

export const imageCatalog: ComponentCatalogEntry = {
    name: 'Image',
    category: 'Atom',
    status: 'active',
    source: 'Components/atoms/Image.tsx',
    variants: ['fit: cover (default) | contain | fill | none'],
    sizes: ['— (width/height via native attrs or caller className)'],
    dependencies: ['imageFitCover', 'imageFitContain', 'imageFitFill', 'imageFitNone'],
    accessibility: [
        'Required alt (empty string allowed for decorative)',
        'Native img semantics; no auto alt from src/filename',
        'ref forwarded to HTMLImageElement',
    ],
    compatibility: [
        'Distinct from Avatar (identity/initials/fallback)',
        'V2 raw img patterns unchanged — not migrated in PR-4P',
    ],
};

export const chipCatalog: ComponentCatalogEntry = {
    name: 'Chip',
    category: 'Atom',
    status: 'active',
    source: 'Components/atoms/Chip.tsx',
    variants: ['neutral (default)', 'accent', 'brand'],
    sizes: ['sm (default)', 'md'],
    dependencies: [
        'chipRoot / chipSize* / chipVariant*',
        'chipLabel / chipLeadingIcon / chipRemoveButton',
        'OurNearIcon close on remove control only',
    ],
    accessibility: [
        'Non-interactive span wrapper; text exposed to AT',
        'Remove: type=button + required removeLabel (aria-label)',
        'No role=option/listbox/checkbox on Chip',
    ],
    compatibility: [
        'Distinct from Badge (status pill) and StatusBadge adapter',
        'MultiSelect keeps inline chip markup — not migrated in PR-4O',
    ],
};

export const avatarCatalog: ComponentCatalogEntry = {
    name: 'Avatar',
    category: 'Atom',
    status: 'active',
    source: 'Components/atoms/Avatar.tsx',
    variants: ['image', 'initials (brand)', 'neutral icon fallback'],
    sizes: ['xs (24px)', 'sm (32px)', 'md (40px, default)', 'lg (48px)', 'xl (64px)'],
    dependencies: [
        'avatarRoot / avatarSize* / avatarShape*',
        'avatarImage / avatarInitials / avatarNeutral',
        'OurNearIcon user (neutral fallback only)',
    ],
    accessibility: [
        'Image: caller-owned alt (empty alt when decorative)',
        'Initials + neutral: aria-hidden; parent provides name/context',
        'No auto aria-label from name; not interactive',
    ],
    compatibility: [
        'Architecture phase — V2 ProfileAvatar in Public Header unchanged',
        'No upload, Gravatar, status badge, or profile business logic',
    ],
};

export const visuallyHiddenCatalog: ComponentCatalogEntry = {
    name: 'VisuallyHidden',
    category: 'Atom',
    status: 'active',
    source: 'Components/atoms/VisuallyHidden.tsx',
    variants: ['as: span (default) | div | p | label'],
    sizes: ['— (no visual box)'],
    dependencies: ['visuallyHidden class recipe (Tailwind sr-only)'],
    accessibility: [
        'Content remains available to assistive technologies',
        'Not aria-hidden — opposite use case',
        'Not display:none or visibility:hidden',
        'Polymorphic element via as prop',
    ],
    compatibility: [
        'Architecture phase — V2 uses raw sr-only spans; unchanged',
        'Distinct from decorative aria-hidden on icons',
    ],
};

export const linkCatalog: ComponentCatalogEntry = {
    name: 'Link',
    category: 'Atom',
    status: 'active',
    source: 'Components/atoms/Link.tsx',
    variants: ['default', 'muted', 'subtle'],
    sizes: ['— (inherits surrounding text scale)'],
    dependencies: ['linkBase', 'linkVariant*', 'linkUnderline*', 'focusRing'],
    accessibility: [
        'Native <a> only — not a button',
        'focusRing on interactive target',
        'target / rel / download — caller-provided via standard anchor attributes',
    ],
    compatibility: [
        'Architecture phase — V2 Inertia Link + ad hoc classes unchanged',
        'Distinct from Button link variant and URLField',
    ],
};

export const dividerCatalog: ComponentCatalogEntry = {
    name: 'Divider',
    category: 'Atom',
    status: 'active',
    source: 'Components/atoms/Divider.tsx',
    variants: ['orientation: horizontal (default) | vertical', 'decorative (default true) | semantic'],
    sizes: ['— (1px line; vertical min-h 1em)'],
    dependencies: ['dividerHorizontal', 'dividerVertical', 'border-line / bg-line tokens'],
    accessibility: [
        'decorative: aria-hidden (horizontal hr, vertical div presentation)',
        'semantic horizontal: native hr',
        'semantic vertical: role=separator + aria-orientation=vertical',
        'Not a focus target',
    ],
    compatibility: [
        'Architecture phase — V2 uses border-t border-line ad hoc; unchanged',
        'DropdownMenuSeparator molecule unchanged (menu inset recipe)',
    ],
};

export const progressCatalog: ComponentCatalogEntry = {
    name: 'Progress',
    category: 'Atom',
    status: 'active',
    source: 'Components/atoms/Progress.tsx',
    variants: ['brand fill on surface track'],
    sizes: ['sm (h-1.5)', 'md (h-2, default)', 'lg (h-3)'],
    dependencies: [
        'progressRoot',
        'progressTrack*',
        'progressFill',
        'progressHeader / label / value text',
    ],
    accessibility: [
        'role=progressbar',
        'aria-valuemin=0, aria-valuemax=max, aria-valuenow=clamped value',
        'aria-label from label or derived when showValue without label',
        'Visible percentage optional via showValue',
    ],
    compatibility: [
        'Architecture phase — V2 ProfileCompletionCenter uses ad hoc bar (unchanged)',
        'Not Spinner, Skeleton, or native progress element',
    ],
};

export const spinnerCatalog: ComponentCatalogEntry = {
    name: 'Spinner',
    category: 'Atom',
    status: 'active',
    source: 'Components/atoms/Spinner.tsx',
    variants: ['inherits currentColor from parent'],
    sizes: ['sm', 'md (default)', 'lg'],
    dependencies: ['spinnerRoot', 'spinnerIcon', 'OurNearIcon (registry inline spinner)', 'motion-reduce utilities'],
    accessibility: [
        'role=status with aria-label (default “Loading” or custom label prop)',
        'Icon decorative inside status',
        'motion-reduce: animation suppressed on icon',
        'Pair parent aria-busy for large loading regions when appropriate',
    ],
    compatibility: [
        'Architecture phase — V2 Button/IconButton use OurNearIcon spinner + aria-busy (unchanged)',
        'No standalone V2 Spinner component',
        'Distinct from Skeleton atom',
    ],
};

export const skeletonCatalog: ComponentCatalogEntry = {
    name: 'Skeleton',
    category: 'Atom',
    status: 'active',
    source: 'Components/atoms/Skeleton.tsx',
    variants: ['text', 'rect', 'circle'],
    sizes: ['intrinsic defaults per variant; width/height override via props'],
    dependencies: ['skeletonBase', 'skeletonVariant*', 'Tailwind animate-pulse', 'motion-reduce utilities'],
    accessibility: [
        'Decorative: aria-hidden + role=presentation on each shape',
        'Parent region should set aria-busy and expose loading text',
        'No role=status on skeleton itself',
        'motion-reduce: static surface (no pulse)',
    ],
    compatibility: [
        'Architecture phase — V2 has no Skeleton component or animate-pulse usage found',
        'Distinct from Button/IconButton spinner (aria-busy on control)',
        'Not EmptyState, Alert, or Spinner atom (future)',
    ],
};

export const iconButtonCatalog: ComponentCatalogEntry = {
    name: 'IconButton',
    category: 'Atom',
    status: 'active',
    source: 'Components/atoms/IconButton.tsx',
    variants: ['default', 'primary', 'secondary', 'ghost', 'destructive', 'outline'],
    sizes: ['sm', 'md', 'lg'],
    dependencies: ['Foundation tokens', 'OurNearIcon', 'Required accessible label prop'],
    accessibility: ['Required label prop (visually hidden or aria-label)', 'aria-busy when loading', 'focusRing on interactive target'],
    compatibility: [
        'Prefer atoms/IconButton with surface prop for new work',
        'Components/UI/IconButton.tsx — legacy wrapper (surface public)',
    ],
};
