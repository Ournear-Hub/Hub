/**
 * Tier 3 — component-ready Tailwind class fragments (public + account UI).
 * Primitive tokens live in `resources/css/app.css` (`@theme`).
 * Canonical ownership & JS constants: `resources/js/foundation/tokens.ts`.
 */

export const focusRing =
    'outline-none focus-visible:ring-2 focus-visible:ring-brand/35 focus-visible:ring-offset-2 focus-visible:ring-offset-canvas';

export const controlBase = `w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink placeholder:text-disabled ${focusRing} disabled:cursor-not-allowed disabled:bg-surface disabled:text-disabled`;

/** Admin/corporate control recipe (matches legacy Admin/ui Input). */
export const adminControlBase =
    'w-full min-h-10 rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand focus-visible:ring-2 focus-visible:ring-brand/20 disabled:cursor-not-allowed disabled:bg-surface disabled:text-disabled read-only:bg-surface read-only:text-ink';

export const controlError = 'border-danger focus-visible:ring-danger/30';

/** Native file input — public/account (visible control, browser file button styling via file:*). */
export const fileInputBase =
    `block w-full cursor-pointer rounded-md border border-line bg-white px-3 py-2 text-sm text-ink ${focusRing} file:mr-3 file:rounded-md file:border-0 file:bg-surface file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-ink hover:file:bg-surface disabled:cursor-not-allowed disabled:opacity-60`;

/** Native file input — admin/corporate. */
export const fileInputAdminBase =
    'block w-full cursor-pointer rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus-within:border-brand focus-visible:ring-2 focus-visible:ring-brand/20 file:mr-3 file:rounded-md file:border-0 file:bg-surface file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-ink hover:file:bg-surface disabled:cursor-not-allowed disabled:bg-surface disabled:text-disabled disabled:opacity-60';

/** Native checkbox — public/account accent check color. */
export const checkboxBase =
    `h-4 w-4 shrink-0 rounded border border-line bg-white accent-accent ${focusRing} disabled:cursor-not-allowed disabled:opacity-60`;

/** Native checkbox — admin/corporate brand check color. */
export const checkboxAdminBase =
    `h-4 w-4 shrink-0 rounded border border-line bg-white accent-brand ${focusRing} disabled:cursor-not-allowed disabled:opacity-60 read-only:opacity-80`;

export const checkboxError = 'border-danger ring-1 ring-danger/40';

/** Native radio — public/account accent selection color. */
export const radioBase =
    `h-4 w-4 shrink-0 rounded-full border border-line bg-white accent-accent ${focusRing} disabled:cursor-not-allowed disabled:opacity-60`;

/** Native radio — admin/corporate brand selection color. */
export const radioAdminBase =
    `h-4 w-4 shrink-0 rounded-full border border-line bg-white accent-brand ${focusRing} disabled:cursor-not-allowed disabled:opacity-60 read-only:opacity-80`;

export const radioError = checkboxError;

/** Visually hidden switch input — pairs with switchTrack* via `peer`. */
export const switchInput = 'peer sr-only';

const switchTrackShared =
    "relative inline-block h-6 w-11 shrink-0 rounded-full border border-line bg-surface transition-[background-color,border-color] after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition-transform after:duration-200 after:content-[''] peer-focus-visible:ring-2 peer-focus-visible:ring-brand/35 peer-focus-visible:ring-offset-2 peer-focus-visible:ring-offset-canvas peer-checked:after:translate-x-5 peer-disabled:cursor-not-allowed peer-disabled:opacity-60";

/** Switch track — public/account (ON = accent). */
export const switchTrackBase = `${switchTrackShared} peer-checked:border-accent peer-checked:bg-accent`;

/** Switch track — admin/corporate (ON = brand). */
export const switchTrackAdmin = `${switchTrackShared} peer-checked:border-brand peer-checked:bg-brand`;

export const labelBase = 'mb-1 block text-sm font-medium text-ink';

export const helperText = 'mt-1 text-xs text-muted';

export const errorText = 'mt-1 text-xs text-danger';

/** Unified auth (login/signup) — spec-aligned field chrome */
export const authLabelBase = 'mb-1 block text-[0.8125rem] font-semibold text-ink';

export const authControlBase = `w-full min-h-[2.875rem] rounded-lg border border-[#D5DFE0] bg-white px-3.5 text-sm text-ink placeholder:text-muted ${focusRing} disabled:cursor-not-allowed disabled:bg-surface disabled:text-disabled`;

export const authControlError = 'border-danger focus-visible:ring-danger/30';

export const authHelperText = 'mt-1 text-[0.6875rem] leading-snug text-muted';

export const authErrorText = 'mt-1 text-[0.6875rem] text-danger';

/** InputGroup — layout/addon join recipes (control styling stays on canonical Input). */
export const inputGroupRoot = 'flex w-full min-w-0 items-stretch';

export const inputGroupAddon =
    'inline-flex shrink-0 items-center border border-line bg-surface px-3 text-sm text-muted select-none';

export const inputGroupAddonLeading = 'rounded-l-md border-r-0';

export const inputGroupAddonTrailing = 'rounded-r-md border-l-0';

export const inputGroupControlSlot = 'min-w-0 flex-1';

/** Join native/descendant inputs without cloning child props. */
export const inputGroupControlAfterLeading = '[&_input]:rounded-l-none [&_input]:border-l-0';

export const inputGroupControlBeforeTrailing = '[&_input]:rounded-r-none [&_input]:border-r-0';

export const inputGroupControlBeforeAction = '[&_input]:rounded-r-none [&_input]:border-r-0';

export const inputGroupActionSlot =
    'flex shrink-0 items-stretch [&_button]:h-full [&_button]:rounded-l-none [&_button]:border-l-0';

/** Combobox popup and options (input uses controlBase). */
export const comboboxListbox =
    'absolute z-20 mt-1 max-h-60 w-full overflow-auto rounded-md border border-line bg-white py-1 shadow-md';

export const comboboxOptionBase = 'px-3 py-2 text-sm text-ink';

export const comboboxOptionActive = 'bg-surface';

export const comboboxOptionDisabled = 'cursor-not-allowed text-disabled';

export const comboboxOptionSelected = 'font-medium';

export const comboboxEmpty = 'px-3 py-2 text-sm text-muted';

/** MultiSelect compound control (reuses combobox listbox option recipes). */
export const multiSelectControl =
    'flex min-h-10 w-full flex-wrap items-center gap-1 rounded-md border border-line bg-white px-2 py-1 text-sm';

export const multiSelectControlError = 'border-danger focus-within:ring-danger/30';

export const multiSelectChip =
    'inline-flex max-w-[min(100%,14rem)] items-center gap-0.5 rounded-md bg-surface py-0.5 pl-2 pr-0.5 text-xs font-medium text-ink';

export const multiSelectChipLabel = 'truncate';

export const multiSelectChipRemove =
    `inline-flex shrink-0 rounded p-0.5 text-muted hover:bg-white hover:text-ink ${focusRing}`;

export const multiSelectInput =
    'min-w-[5rem] flex-1 border-0 bg-transparent py-1.5 text-sm text-ink outline-none placeholder:text-disabled disabled:cursor-not-allowed';

/** SegmentedControl — connected mutually-exclusive segments. */
export const segmentedControlRoot =
    'inline-flex max-w-full flex-wrap rounded-md border border-line bg-surface p-0.5';

const segmentedControlSegmentShared = `inline-flex shrink-0 items-center justify-center gap-1.5 rounded-md font-medium transition ${focusRing}`;

export const segmentedControlSegmentSm = `${segmentedControlSegmentShared} min-h-8 px-2.5 text-xs`;

export const segmentedControlSegmentMd = `${segmentedControlSegmentShared} min-h-9 px-3 text-sm`;

export const segmentedControlSelected = 'bg-white text-ink shadow-sm';

export const segmentedControlUnselected = 'text-muted hover:bg-white/60 hover:text-ink';

/** Accordion — native details/summary disclosure styling. */
export const accordionRoot = 'divide-y divide-line rounded-md border border-line bg-white';

export const accordionItem = 'group';

export const accordionSummary =
    `flex cursor-pointer list-none items-center gap-3 px-4 py-3 text-sm font-semibold text-ink marker:content-none [&::-webkit-details-marker]:hidden ${focusRing}`;

export const accordionSummaryDisabled = 'cursor-not-allowed opacity-50';

export const accordionContent = 'px-4 pb-4 pt-0 text-sm text-ink';

export const accordionChevron = 'ml-auto shrink-0 text-muted transition-transform group-open:rotate-180';

/** Tooltip — transient supplementary hint (portaled, non-interactive). */
export const tooltipContent =
    'pointer-events-none fixed z-50 max-w-xs rounded-md border border-line/80 bg-ink px-2.5 py-1.5 text-xs font-medium leading-snug text-white shadow-md transition-opacity duration-150';

export const tooltipContentHidden = 'opacity-0';

export const tooltipContentVisible = 'opacity-100';

export const tooltipDisabledWrap = `inline-flex rounded ${focusRing}`;

/** Popover — portaled interactive floating surface. */
export const popoverContent =
    'fixed z-50 w-72 max-w-[min(calc(100vw-1rem),20rem)] rounded-lg border border-line bg-white p-4 text-sm text-ink shadow-lg outline-none';

/** DropdownMenu — command menu surface and items. */
export const dropdownMenuContent =
    'fixed z-50 min-w-[10rem] max-w-[min(calc(100vw-1rem),18rem)] rounded-lg border border-line bg-white p-1 text-sm text-ink shadow-lg outline-none';

export const dropdownMenuItem =
    `flex w-full items-center gap-2 rounded-md px-2.5 py-2 text-left font-medium text-ink hover:bg-surface ${focusRing}`;

export const dropdownMenuItemActive = 'bg-surface';

export const dropdownMenuItemDestructive = 'text-danger hover:bg-danger/5 focus-visible:ring-danger/30';

export const dropdownMenuItemDisabled = 'cursor-not-allowed opacity-50 hover:bg-transparent';

export const dropdownMenuSeparator = 'my-1 h-px bg-line';

/** Alert — inline persistent feedback surface. */
export const alertRoot = 'flex items-start gap-3 rounded-lg border px-4 py-3 text-sm';

export const alertContent = 'min-w-0 flex-1';

export const alertTitle = 'font-semibold text-ink';

export const alertMessage = 'text-ink';

export const alertBorderInfo = 'border-brand/25';

export const alertBorderSuccess = 'border-success/30';

export const alertBorderWarning = 'border-warning/40';

export const alertBorderError = 'border-danger/30';

/** Toast — transient portaled feedback (reuses alert tone/border semantics). */
export const toastRoot =
    'pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg border px-4 py-3 text-sm shadow-lg';

export const toastPlacementBottomEnd = 'fixed bottom-4 right-4 z-[60]';

export const toastPlacementBottomStart = 'fixed bottom-4 left-4 z-[60]';

export const toastPlacementTopEnd = 'fixed top-4 right-4 z-[60]';

export const toastPlacementTopStart = 'fixed top-4 left-4 z-[60]';

/** EmptyState — zero-data collection/section surface. */
export const emptyStateRoot = 'flex flex-col rounded-xl border border-dashed border-line bg-white';

export const emptyStateAlignCenter = 'items-center text-center';

export const emptyStateAlignStart = 'items-start text-left';

export const emptyStateSizeSm = 'gap-2 px-4 py-6';

export const emptyStateSizeMd = 'gap-3 px-6 py-10';

export const emptyStateSizeLg = 'gap-4 px-8 py-12';

export const emptyStateTitleSm = 'text-sm font-semibold text-ink';

export const emptyStateTitleMd = 'text-base font-semibold text-ink';

export const emptyStateTitleLg = 'text-lg font-semibold text-ink';

export const emptyStateDescription = 'max-w-md text-sm text-muted';

export const emptyStateActions = 'flex flex-wrap items-center gap-2';

export const emptyStateActionsCenter = 'justify-center';

export const emptyStateActionsStart = 'justify-start';

/** DataTable — admin list table shell (PR-5M M-A; mirrors V2 Admin/ui). */
export const dataTableRoot = 'overflow-x-auto rounded-xl border border-line bg-white';

export const dataTableTable = 'min-w-full text-left text-sm';

export const dataTableHeadRow = 'border-b border-line bg-surface text-muted';

export const dataTableHeadCell = 'px-4 py-3 font-medium';

export const dataTableBodyRow = 'border-b border-line last:border-0';

export const dataTableBodyRowClickable = 'cursor-pointer';

export const dataTableBodyRowSelected = 'bg-surface';

export const dataTableBodyRowHover = 'hover:bg-surface/60';

export const dataTableBodyCell = 'px-4 py-3';

export const dataTableBodyCellMuted = 'text-muted';

/** Strip dashed EmptyState chrome when embedded in a table cell (V2 parity). */
export const dataTableEmptyStateInCell = 'border-0 bg-transparent rounded-none shadow-none w-full';

/** FilterBar — admin filter form grid shell (PR-5O O-A; mirrors V2 Admin/ui). */
export const filterBarRoot = 'mb-6 grid gap-3 rounded-xl border border-line bg-white p-4 md:grid-cols-4';

/** DetailHeader — Detail pattern title block (spec §11; not admin list PageHeader — PR-5P). */
export const detailHeaderRoot = 'flex flex-wrap items-start justify-between gap-3';

export const detailHeaderMain = 'min-w-0 flex-1';

export const detailHeaderActions = 'shrink-0';

/** DetailSection — bordered detail subsection (account profile sections). */
export const detailSectionRoot = 'rounded-xl border border-line bg-white p-5';

export const detailSectionHeader = 'flex flex-wrap items-center justify-between gap-3';

export const detailSectionTitle = 'font-semibold text-ink';

export const detailSectionDescription = 'mt-1 text-sm text-muted';

export const detailSectionActions = 'flex gap-3';

export const detailSectionActionButton = 'text-sm font-medium text-brand';

export const detailSectionBody = 'mt-3 text-sm text-ink';

/** ApplicantSummaryCard — employer applicant/submission summary (account surface). */
export const applicantSummaryCardRoot = 'rounded-xl border border-line bg-white p-4 sm:p-5';

export const applicantSummaryCardLayout = 'flex flex-col gap-4 sm:flex-row sm:items-start';

export const applicantSummaryCardAvatarImage = 'h-14 w-14 shrink-0 rounded-full object-cover sm:h-16 sm:w-16';

export const applicantSummaryCardInitials =
    'flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-surface text-sm font-semibold text-brand sm:h-16 sm:w-16';

export const applicantSummaryCardBody = 'min-w-0 flex-1';

export const applicantSummaryCardNameRow = 'flex flex-wrap items-center gap-2';

export const applicantSummaryCardTitle = 'text-base font-semibold text-ink sm:text-lg';

export const applicantSummaryCardDesignation = 'mt-1 text-sm font-medium text-ink';

export const applicantSummaryCardMeta = 'mt-1 text-sm text-muted';

export const applicantSummaryCardContext = 'mt-2 text-sm text-muted';

export const applicantSummaryCardContextValue = 'font-medium text-ink';

export const applicantSummaryCardTime = 'mt-1 text-sm text-muted';

export const applicantSummaryCardReference = 'mt-1 text-xs text-muted';

export const applicantSummaryCardNotices = 'mt-2 space-y-2 text-xs text-muted';

export const applicantSummaryCardActions = 'mt-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center';

/** ApplicantResumeOverview — employer resume + optional qualification summary. */
export const applicantResumeOverviewSummary = 'mb-4 border-b border-line pb-4';

export const applicantResumeOverviewSummaryTitle = 'text-sm font-semibold text-ink';

export const applicantResumeOverviewSummaryList = 'mt-2 grid gap-2 text-sm sm:grid-cols-2';

export const applicantResumeOverviewSummaryLabel = 'text-muted';

export const applicantResumeOverviewTitle = 'text-base font-semibold text-ink';

export const applicantResumeOverviewActions = 'mt-3 flex flex-col gap-2 sm:flex-row sm:flex-wrap';

export const applicantResumeOverviewEmpty = 'mt-2 text-sm text-muted';

export const applicantResumeOverviewFootnote = 'mt-2 text-xs text-muted';

/** DocumentGroupList — grouped document rows (spec §11; display-only organism). */
export const documentGroupListRoot = 'space-y-6';

export const documentGroupListGroupLabel = 'text-sm font-semibold uppercase tracking-wide text-muted';

export const documentGroupListList = 'mt-3 space-y-3';

export const documentGroupListItemTitle = 'mt-1 text-lg font-semibold text-ink';

export const documentGroupListIdentity = 'text-xs font-medium uppercase tracking-wide text-muted';

export const documentGroupListActions = 'mt-3 flex flex-wrap gap-3 text-sm';

export const documentGroupListExtra = 'mt-4';

/** MatchComparisonTable — side-by-side factor comparison (ApplicationDrawer screening desk). */
export const matchComparisonTableRoot = '';

export const matchComparisonTableDisclaimer = 'text-xs text-muted';

export const matchComparisonTableEmpty = 'text-sm text-muted';

export const matchComparisonTableTable = 'min-w-full text-left text-sm';

export const matchComparisonTableThead = 'bg-surface text-muted';

export const matchComparisonTableCellFactor = 'px-3 py-2 font-medium text-ink';

export const matchComparisonTableSourceHint = 'mt-1 text-xs font-normal text-muted';

export const matchComparisonTableCellValue = 'px-3 py-2 text-ink';

export const matchComparisonTableCellDetail = 'px-3 py-2 text-muted';

export const matchComparisonTableFootnote = 'border-t border-line px-3 py-2 text-xs text-muted';

/** ScreeningStagesTable — screening workflow stage audit (ApplicationDrawer). */
export const screeningStagesTableRoot = '';

export const screeningStagesTableScroll = 'overflow-x-auto';

export const screeningStagesTableTable = 'min-w-full text-left text-sm';

export const screeningStagesTableThead = 'text-muted';

export const screeningStagesTableCell = 'px-2 py-2 font-medium';

export const screeningStagesTableCellInk = 'px-2 py-2 text-ink';

export const screeningStagesTableCellMuted = 'px-2 py-2 text-muted';

export const screeningStagesTableEmpty = 'text-sm text-muted';

/** Skeleton — loading shape primitive (pulse suppressed when prefers-reduced-motion). */
export const skeletonBase =
    'block shrink-0 animate-pulse bg-surface motion-reduce:animate-none motion-reduce:bg-line';

export const skeletonVariantText = 'h-4 w-full rounded';

export const skeletonVariantRect = 'min-h-5 w-full rounded-md';

export const skeletonVariantCircle = 'size-10 shrink-0 rounded-full';

/** Spinner — active loading indicator (icon via OurNear registry `spinner`). */
export const spinnerRoot = 'inline-flex shrink-0 items-center justify-center text-current';

export const spinnerIcon = 'shrink-0 motion-reduce:animate-none';

/** Progress — determinate completion bar. */
export const progressRoot = 'w-full';

export const progressHeader = 'mb-2 flex items-center justify-between gap-2 text-sm';

export const progressLabelText = 'font-medium text-ink';

export const progressValueText = 'shrink-0 tabular-nums text-muted';

export const progressTrackSm = 'h-1.5 w-full overflow-hidden rounded-full bg-surface';

export const progressTrackMd = 'h-2 w-full overflow-hidden rounded-full bg-surface';

export const progressTrackLg = 'h-3 w-full overflow-hidden rounded-full bg-surface';

export const progressFill =
    'h-full rounded-full bg-brand transition-[width] duration-300 motion-reduce:transition-none';

/** Divider — structural content separation (not card/menu-specific borders). */
export const dividerHorizontal = 'm-0 w-full shrink-0 border-0 border-t border-line';

export const dividerVertical = 'inline-block w-px shrink-0 self-stretch border-0 bg-line min-h-[1em]';

/** Link — canonical navigation anchor styling. */
export const linkBase = `inline-flex items-center gap-1 font-medium transition-colors ${focusRing} rounded-sm`;

export const linkVariantDefault = 'text-brand';

export const linkVariantMuted = 'text-muted hover:text-ink';

export const linkVariantSubtle = 'text-ink/90 hover:text-ink';

export const linkUnderlineNone = 'no-underline';

export const linkUnderlineHover = 'no-underline underline-offset-2 hover:underline';

export const linkUnderlineAlways = 'underline underline-offset-2';

/** Breadcrumb — ordered hierarchy navigation (V2 Navigation/Breadcrumbs contract). */
export const breadcrumbNav = 'mb-4 text-sm';

export const breadcrumbList = 'flex flex-wrap items-center gap-1 text-muted';

export const breadcrumbItem = 'flex min-w-0 items-center gap-1';

export const breadcrumbLink = 'truncate font-medium';

export const breadcrumbCurrent = 'truncate font-medium text-ink';

/** Pagination — Laravel-style page link row (V2 Components/Pagination contract). */
export const paginationNav = 'mt-2 flex flex-wrap gap-1 lg:mt-0';

export const paginationItemBase = 'rounded-md px-3 py-1.5 text-sm';

export const paginationItemLink = 'border border-line bg-white text-ink hover:bg-surface';

export const paginationItemCurrent = 'bg-brand text-white';

export const paginationItemDisabled = 'text-disabled';

/** Tabs — tablist / tab / tabpanel (V2 Admin/ui/Tabs visual contract). */
export const tabsRoot = 'flex flex-col';

export const tabsList = 'flex flex-wrap gap-1 border-b border-line';

export const tabsTabBase =
    'min-h-10 rounded-t-md px-3 py-2 text-sm font-medium focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/40';

export const tabsTabSelected = 'bg-white text-brand';

export const tabsTabUnselected = 'text-muted hover:text-ink';

export const tabsTabDisabled = 'cursor-not-allowed opacity-50';

export const tabsPanel = 'pt-4';

/** Timeline — vertical chronological event list (V2 Admin/ui/Timeline contract). */
export const timelineList = 'space-y-3 border-l border-line pl-4';

export const timelineItem = 'relative text-sm';

export const timelineMarker = 'absolute -left-[21px] mt-1.5 h-2.5 w-2.5 rounded-full bg-brand';

export const timelineTitle = 'font-medium text-ink';

export const timelineMeta = 'mt-0.5 text-xs text-muted';

export const timelineEmpty = 'text-sm text-muted';

/** Dialog — centered modal shell (V2 Admin/ui/Modal contract). */
export const dialogOverlay = 'fixed inset-0 z-[70] flex items-end justify-center p-4 sm:items-center';

export const dialogPanel = 'relative z-10 w-full max-w-md rounded-xl border border-line bg-white p-5';

export const dialogHeader = 'flex items-start justify-between gap-3';

export const dialogTitle = 'text-lg font-semibold text-ink';

export const dialogBody = 'mt-4 text-sm text-muted';

export const dialogFooter = 'mt-5 flex flex-wrap justify-end gap-2';

/** Drawer — full-height right slide-over workspace (V2 Admin/ui/Drawer contract). */
export const drawerOverlay = 'fixed inset-0 z-[60] flex justify-end';

export const drawerPanel =
    'relative z-10 flex h-full w-full min-w-0 max-w-none flex-col bg-white sm:max-w-lg';

export const drawerPanelSizeXl = 'sm:max-w-3xl';

export const drawerHeader = 'flex items-start justify-between gap-3 border-b border-line px-5 py-4';

export const drawerTitle = 'text-xl font-semibold text-ink';

export const drawerBody = 'min-h-0 flex-1 overflow-y-auto overflow-x-hidden px-5 py-4';

export const drawerFooter = 'border-t border-line px-5 py-4';

/** Stack — vertical sibling spacing (flex column + gap; not a form group or page shell). */
export const stackRoot = 'flex flex-col';

export const stackGapXs = 'gap-1';

export const stackGapSm = 'gap-2';

export const stackGapMd = 'gap-4';

export const stackGapLg = 'gap-6';

export const stackGapXl = 'gap-8';

export const stackAlignStretch = 'items-stretch';

export const stackAlignStart = 'items-start';

export const stackAlignCenter = 'items-center';

export const stackAlignEnd = 'items-end';

/** Cluster — horizontal sibling spacing (flex row + gap; not InputGroup or generic flex). */
export const clusterRoot = 'flex flex-row';

export const clusterGapXs = stackGapXs;

export const clusterGapSm = stackGapSm;

export const clusterGapMd = stackGapMd;

export const clusterGapLg = stackGapLg;

export const clusterGapXl = stackGapXl;

export const clusterAlignStretch = stackAlignStretch;

export const clusterAlignStart = stackAlignStart;

export const clusterAlignCenter = stackAlignCenter;

export const clusterAlignEnd = stackAlignEnd;

export const clusterAlignBaseline = 'items-baseline';

export const clusterWrap = 'flex-wrap';

export const clusterNoWrap = 'flex-nowrap';

/** AspectRatio — layout box for width-to-height relationship (not Image/Card/Avatar). */
export const aspectRatioRoot = 'w-full';

export const aspectRatioSquare = 'aspect-square';

export const aspectRatioVideo = 'aspect-video';

/** V2 About founder portrait — aspect-[4/5]. */
export const aspectRatioPortrait = 'aspect-[4/5]';

/** Container — horizontal content boundary (width + optional gutters; not a visual surface). */
export const containerRoot = 'mx-auto w-full min-w-0';

export const containerSizeSm = 'max-w-3xl';

export const containerSizeMd = 'max-w-6xl';

export const containerSizeLg = 'max-w-7xl';

export const containerSizeXl = 'max-w-screen-xl';

export const containerSize2xl = 'max-w-screen-2xl';

export const containerSizeFull = 'max-w-none';

export const containerGutterSm = 'px-4';

export const containerGutterMd = 'px-4 sm:px-6 lg:px-8';

export const containerGutterLg = 'px-6 sm:px-8 lg:px-10';

/** Image — object-fit recipes for the canonical Image atom (layout/radius from caller). */
export const imageFitCover = 'object-cover';

export const imageFitContain = 'object-contain';

export const imageFitFill = 'object-fill';

export const imageFitNone = 'object-none';

/** Chip — compact metadata label (skills, filters, selected values); distinct from Badge pills. */
export const chipRoot =
    'inline-flex max-w-[min(100%,14rem)] items-center gap-1 rounded-md font-medium';

export const chipSizeSm = 'py-0.5 pl-2 text-xs';

export const chipSizeMd = 'py-1 pl-2.5 text-sm';

export const chipTrailingPaddingDefault = 'pr-2';

export const chipTrailingPaddingRemovable = 'pr-0.5';

export const chipVariantNeutral = 'border border-line bg-surface text-ink';

export const chipVariantAccent = 'border border-accent/25 bg-accent/10 text-accent';

export const chipVariantBrand = 'border border-brand/25 bg-brand/10 text-brand';

export const chipLabel = 'min-w-0 truncate';

export const chipLeadingIcon = 'inline-flex shrink-0 text-current';

export const chipRemoveButton =
    `inline-flex shrink-0 rounded p-0.5 text-muted hover:bg-white/80 hover:text-ink ${focusRing}`;

/** Avatar — identity display primitive (image → initials → neutral icon). */
export const avatarRoot = 'relative inline-flex shrink-0 overflow-hidden select-none';

export const avatarSizeXs = 'h-6 w-6 text-[10px] leading-none';

export const avatarSizeSm = 'h-8 w-8 text-xs leading-none';

export const avatarSizeMd = 'h-10 w-10 text-sm leading-none';

export const avatarSizeLg = 'h-12 w-12 text-sm leading-none';

export const avatarSizeXl = 'h-16 w-16 text-base leading-none';

/** Personal dashboard sidebar profile card (responsive ~96–128px, width-limited). */
export const avatarSizeProfileSidebar =
    'h-24 w-24 max-w-[calc(100%-1rem)] text-lg leading-none sm:h-[6.75rem] sm:w-[6.75rem] lg:h-28 lg:w-28 lg:text-xl xl:h-32 xl:w-32';

/** Personal dashboard main profile summary (responsive 64–96px). */
export const avatarSizeProfileSummary =
    'h-16 w-16 text-base leading-none sm:h-20 sm:w-20 lg:h-24 lg:w-24';

export const avatarShapeCircle = 'rounded-full';

export const avatarShapeSquare = 'rounded-md';

export const avatarImage = 'h-full w-full object-cover';

export const avatarInitials =
    'inline-flex h-full w-full items-center justify-center bg-brand font-semibold text-white';

export const avatarNeutral =
    'inline-flex h-full w-full items-center justify-center bg-surface text-muted';

/** VisuallyHidden — screen-reader-only content (Tailwind sr-only recipe). */
export const visuallyHidden = 'sr-only';

export const cardDensity = {
    /** Account/dashboard cards — 16px internal padding */
    compact: 'rounded-xl border border-line bg-white p-4 shadow-sm',
    standard: 'rounded-xl border border-line bg-white p-5 shadow-sm sm:p-6',
    feature: 'rounded-xl border border-line bg-white p-6 shadow-md sm:p-8',
} as const;

/** Shared spacing for candidate personal account pages (compact SaaS dashboard rhythm). */
export const accountLayout = {
    /** Main column below page title — 12–16px between related blocks */
    pageStack: 'flex min-w-0 w-full flex-col gap-3 sm:gap-4',
    /** 20–24px between major sections on a page */
    sectionStack: 'flex min-w-0 flex-col gap-5 sm:gap-6',
    /** Sidebar + main grid */
    shellGrid: 'grid gap-4 lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-5 xl:grid-cols-[16rem_minmax(0,1fr)]',
    cardGrid: 'gap-3 sm:gap-4',
    mainPy: 'py-4 sm:py-5',
    pageTitleBar: 'py-3 sm:py-4',
    sidebarIdentity: 'mb-3 rounded-xl border border-line bg-white p-4 shadow-sm',
    /** Section heading → first line of body (10–12px) */
    headingToBody: 'mt-2.5',
    listDivideRow: 'py-2.5',
    /** Left-aligned personal profile forms, previews, and resume workspace. */
    profileWorkspace: 'min-w-0 w-full max-w-3xl space-y-4 text-left xl:max-w-4xl',
    profileResumeWorkspace: 'min-w-0 w-full space-y-5 text-left',
} as const;

export const typography = {
    display: 'text-3xl font-semibold tracking-tight text-ink sm:text-4xl',
    pageTitle: 'text-2xl font-semibold tracking-tight text-ink sm:text-3xl',
    sectionTitle: 'text-lg font-semibold text-ink',
    cardTitle: 'text-base font-semibold text-ink',
    body: 'text-sm text-ink',
    secondary: 'text-sm text-muted',
    caption: 'text-xs text-muted',
    eyebrow: 'text-xs font-semibold uppercase tracking-wide text-brand',
} as const;

export const statusTone = {
    success: 'bg-success/10 text-success',
    warning: 'bg-warning/15 text-ink',
    error: 'bg-danger/10 text-danger',
    neutral: 'bg-surface text-muted',
    info: 'bg-brand/10 text-brand',
    brand: 'bg-brand/10 text-brand',
} as const;

export type StatusTone = keyof typeof statusTone;

export type CardDensity = keyof typeof cardDensity;
