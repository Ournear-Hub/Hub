export function recorded(value: string | number | null | undefined): string {
    if (value === null || value === undefined) {
        return 'Not recorded yet';
    }

    const text = String(value).trim();
    if (text === '' || text === '—') {
        return 'Not recorded yet';
    }

    return text;
}

export function unavailable(available: boolean | null | undefined): string {
    return available ? '' : 'Not available in V1.1';
}
