/** Shared Tailwind class fragments for public + account UI. */

export const focusRing =
    'outline-none focus-visible:ring-2 focus-visible:ring-brand/35 focus-visible:ring-offset-2 focus-visible:ring-offset-canvas';

export const controlBase = `w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink placeholder:text-disabled ${focusRing} disabled:cursor-not-allowed disabled:bg-surface disabled:text-disabled`;

export const controlError = 'border-danger focus-visible:ring-danger/30';

export const labelBase = 'mb-1 block text-sm font-medium text-ink';

export const helperText = 'mt-1 text-xs text-muted';

export const errorText = 'mt-1 text-xs text-danger';

export const cardDensity = {
    compact: 'rounded-lg border border-line bg-white p-3 shadow-sm',
    standard: 'rounded-xl border border-line bg-white p-5 shadow-sm sm:p-6',
    feature: 'rounded-xl border border-line bg-white p-6 shadow-md sm:p-8',
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
