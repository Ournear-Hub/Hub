export type PublicShareContentType = 'job' | 'company' | 'profile';

export type PublicShareContext = {
    type: PublicShareContentType;
    title: string;
    url: string;
    company?: string | null;
    location?: string | null;
    subtitle?: string | null;
    referralCode?: string | null;
};

export function resolveShareUrl(context: PublicShareContext): string {
    const base = context.url.trim();
    if (!context.referralCode || context.type !== 'job') {
        return base;
    }
    try {
        const parsed = new URL(base);
        parsed.searchParams.set('ref', context.referralCode);
        return parsed.toString();
    } catch {
        const separator = base.includes('?') ? '&' : '?';
        return `${base}${separator}ref=${encodeURIComponent(context.referralCode)}`;
    }
}

export function whatsappShareUrl(text: string): string {
    return `https://wa.me/?text=${encodeURIComponent(text)}`;
}

export function mailtoShareUrl(subject: string, body: string): string {
    return `mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
}

export function buildWhatsappText(context: PublicShareContext, url: string): string {
    const title = context.title.trim();
    switch (context.type) {
        case 'job': {
            let line = `Check out this job: ${title}`;
            if (context.company?.trim()) {
                line += ` — ${context.company.trim()}`;
            }
            if (context.location?.trim()) {
                line += ` (${context.location.trim()})`;
            }
            return `${line} ${url}`;
        }
        case 'company':
            return `Explore this company on OurNear Hire: ${title} ${url}`;
        case 'profile': {
            let line = `View this profile on OurNear Hire: ${title}`;
            if (context.subtitle?.trim()) {
                line += ` — ${context.subtitle.trim()}`;
            }
            return `${line} ${url}`;
        }
        default:
            return `${title} ${url}`;
    }
}

export function buildEmailPayload(context: PublicShareContext, url: string): { subject: string; body: string } {
    const title = context.title.trim();
    switch (context.type) {
        case 'job': {
            const lines = [
                'I found this job on OurNear Hire and thought you might be interested.',
                '',
                title,
            ];
            if (context.company?.trim()) {
                lines.push(context.company.trim());
            }
            if (context.location?.trim()) {
                lines.push(context.location.trim());
            }
            lines.push(url);
            return {
                subject: 'Job opportunity on OurNear Hire',
                body: lines.join('\n'),
            };
        }
        case 'company':
            return {
                subject: `${title} on OurNear Hire`,
                body: `I found this company profile on OurNear Hire.\n\n${title}\n${url}`,
            };
        case 'profile': {
            const lines = ['I wanted to share this profile with you on OurNear Hire.', '', title];
            if (context.subtitle?.trim()) {
                lines.push(context.subtitle.trim());
            }
            lines.push(url);
            return {
                subject: `${title} on OurNear Hire`,
                body: lines.join('\n'),
            };
        }
        default:
            return { subject: 'Shared from OurNear Hire', body: `${title}\n${url}` };
    }
}

export function buildNativeText(context: PublicShareContext, url: string): string {
    return buildWhatsappText(context, url).replace(` ${url}`, '');
}

export async function copyShareUrl(url: string): Promise<boolean> {
    try {
        await navigator.clipboard.writeText(url);
        return true;
    } catch {
        return false;
    }
}
