import { Link } from '@inertiajs/react';

export type PublicCompanyCardData = {
    id: number;
    name: string;
    industry: string | null;
    about?: string | null;
    location?: string | null;
    logo_url?: string | null;
    ournear_verified?: boolean;
    open_jobs: number;
    url: string;
};

function initials(name: string): string {
    const source = name === 'Employer profile' ? 'EP' : name;
    return (
        source
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map((part) => part[0])
            .join('')
            .toUpperCase() || 'EP'
    );
}

type Props = {
    company: PublicCompanyCardData;
    compact?: boolean;
};

export default function PublicCompanyCard({ company, compact = false }: Props) {
    return (
        <article className="flex min-w-0 max-w-full flex-col rounded-xl border border-line bg-white p-4 shadow-sm transition-shadow hover:shadow-md sm:p-5">
            <div className="flex min-w-0 items-start gap-3">
                {company.logo_url ? (
                    <Link href={company.url} className="shrink-0">
                        <img
                            src={company.logo_url}
                            alt={`${company.name} logo`}
                            className={`rounded-lg border border-line object-cover ${compact ? 'h-12 w-12' : 'h-14 w-14'}`}
                        />
                    </Link>
                ) : (
                    <Link
                        href={company.url}
                        className={`flex shrink-0 items-center justify-center rounded-lg bg-brand/10 font-semibold text-brand ${compact ? 'h-12 w-12 text-xs' : 'h-14 w-14 text-sm'}`}
                        aria-label={`${company.name} profile`}
                    >
                        {initials(company.name)}
                    </Link>
                )}
                <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-2">
                        <h3 className={`min-w-0 font-semibold text-ink ${compact ? 'text-base' : 'text-lg'}`}>
                            <Link href={company.url} className="break-words hover:text-brand">
                                {company.name}
                            </Link>
                        </h3>
                        {company.ournear_verified ? (
                            <span className="shrink-0 rounded-full bg-accent/15 px-2 py-0.5 text-xs font-semibold text-accent">
                                Verified
                            </span>
                        ) : null}
                    </div>
                    <p className="mt-1 truncate text-sm text-muted">{company.industry ?? 'Industry not listed'}</p>
                    {company.location ? <p className="mt-0.5 truncate text-sm text-muted">{company.location}</p> : null}
                </div>
            </div>
            {!compact && company.about ? (
                <p className="mt-3 line-clamp-2 text-sm text-muted">{company.about}</p>
            ) : null}
            <p className="mt-3 text-sm font-medium text-ink">
                {company.open_jobs} {company.open_jobs === 1 ? 'open job' : 'open jobs'}
            </p>
            <div className={`flex flex-wrap gap-3 ${compact ? 'mt-3' : 'mt-4 border-t border-line pt-4'}`}>
                <Link href={company.url} className="text-sm font-semibold text-brand hover:underline">
                    View company
                </Link>
                {company.open_jobs > 0 ? (
                    <Link href="/jobs" className="text-sm font-medium text-muted hover:text-brand">
                        Browse jobs
                    </Link>
                ) : null}
            </div>
        </article>
    );
}
