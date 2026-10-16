/**
 * Canonical design token ownership (OurNear Corporate Library — PR-1).
 *
 * **Where to change tokens**
 * 1. Primitive colors, radius, shadows, font → `resources/css/app.css` (`@theme`)
 * 2. Documented JS constants (reference / non-CSS consumers) → this file
 * 3. Component-ready Tailwind class fragments → `resources/js/design-system/classNames.ts`
 *
 * Do not introduce page-specific token names (e.g. candidateBlue).
 */

/** Tier 1 — global / primitive (mirrors `@theme` in app.css) */
export const color = {
    brand: '#214449',
    accent: '#f15a24',
    white: '#ffffff',
    canvas: '#f9fafa',
    surface: '#f4f5f5',
    line: '#e5e7e7',
    ink: '#0f172a',
    muted: '#64748b',
    disabled: '#94a3a8',
    success: '#22c55e',
    warning: '#f59e0b',
    danger: '#ef4444',
} as const;

export const radius = {
    sm: '0.375rem',
    md: '0.375rem',
    lg: '0.5rem',
    xl: '0.75rem',
} as const;

export const shadow = {
    sm: '0 1px 2px rgb(15 23 42 / 0.06)',
    md: '0 4px 12px rgb(15 23 42 / 0.08)',
} as const;

export const fontFamily = {
    sans: "'Instrument Sans', ui-sans-serif, system-ui, sans-serif",
} as const;

/**
 * Tier 2 — semantic Tailwind utility names (use in className strings).
 * Values are unchanged from V2; `@theme` registers the underlying CSS variables.
 */
export const semantic = {
    color: {
        primary: 'brand',
        accent: 'accent',
        canvas: 'canvas',
        surface: 'surface',
        line: 'line',
        ink: 'ink',
        muted: 'muted',
        disabled: 'disabled',
        success: 'success',
        warning: 'warning',
        danger: 'danger',
    },
    radius: {
        sm: 'rounded-sm',
        md: 'rounded-md',
        lg: 'rounded-lg',
        xl: 'rounded-xl',
    },
} as const;

/** Default icon stroke width (Tabler outline) — see `resources/js/icons/` */
export const iconDefaults = {
    stroke: 2,
} as const;

export type SemanticColor = keyof typeof semantic.color;
