/** Align legacy CMS hire CTAs with public product language (display only). */
export function hireTalentCtaLabel(label?: string | null): string {
    const trimmed = label?.trim();
    if (!trimmed) {
        return 'Hire Talent';
    }

    const lower = trimmed.toLowerCase();
    if (lower.includes('for employer')) {
        return 'Hire Talent';
    }

    return trimmed;
}
