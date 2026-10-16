/**
 * Active-state matching for account sidebar / mobile nav (path + optional URL hash).
 */
export function matchAccountNavHref(href: string, path: string, hash: string): boolean {
    const pathWithQuery = path.split('#')[0] ?? path;
    const normalizedPath = pathWithQuery.split('?')[0]?.replace(/\/$/, '') || pathWithQuery;
    const normalizedHash = hash.startsWith('#') ? hash : hash ? `#${hash}` : '';

    if (href.includes('#')) {
        const [hrefPath, fragment] = href.split('#');
        const base = (hrefPath ?? href).replace(/\/$/, '') || '/';
        const expectedHash = fragment ? `#${fragment}` : '';

        return normalizedPath === base && normalizedHash === expectedHash;
    }

    const [hrefPath, query] = href.split('?');
    const base = (hrefPath ?? href).replace(/\/$/, '') || '/';

    if (query) {
        const expected = new URLSearchParams(query);
        const actualQuery = pathWithQuery.includes('?') ? pathWithQuery.split('?')[1] : '';
        const actual = new URLSearchParams(actualQuery);
        const pathOnly = normalizedPath;

        if (pathOnly !== base && !path.startsWith(`${base}/`)) {
            return false;
        }

        for (const [key, expectedValue] of expected.entries()) {
            if (actual.get(key) !== expectedValue) {
                return false;
            }
        }

        return pathWithQuery.startsWith(base);
    }

    if (base === '/account/hiring/applications' && !query) {
        if (normalizedPath !== base) {
            return false;
        }
        const actualQuery = pathWithQuery.includes('?') ? pathWithQuery.split('?')[1] : '';
        const actual = new URLSearchParams(actualQuery);
        const stage = actual.get('stage');
        return stage === null || stage === '' || stage === 'all';
    }

    if (base === '/account/hiring/candidates' && !query) {
        if (normalizedPath !== base) {
            return false;
        }
        const actualQuery = pathWithQuery.includes('?') ? pathWithQuery.split('?')[1] : '';
        const actual = new URLSearchParams(actualQuery);
        const stage = actual.get('stage');
        return stage === null || stage === '' || stage === 'all';
    }

    if (base === '/account/hiring/automation') {
        return normalizedPath === base;
    }

    if (base === '/account') {
        return normalizedPath === '/account';
    }

    if (base === '/account/hiring') {
        return normalizedPath === '/account/hiring';
    }

    if (base === '/account/resumes') {
        return normalizedPath === '/account/resumes';
    }

    if (base === '/account/resumes/builder') {
        return normalizedPath === '/account/resumes/builder' || normalizedPath.startsWith('/account/resumes/builder/');
    }

    if (base === '/account/profile') {
        return normalizedPath === '/account/profile' && normalizedHash === '';
    }

    if (base === '/account/settings/account') {
        return normalizedPath.startsWith('/account/settings');
    }

    return normalizedPath === base || normalizedPath.startsWith(`${base}/`);
}
