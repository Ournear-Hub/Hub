import { FormEvent, useCallback, useEffect, useId, useRef, useState } from 'react';
import { Link } from '@inertiajs/react';
import SearchField from '@/Components/molecules/SearchField';
import IconButton from '@/Components/UI/IconButton';
import CompanyLogo from '@/Components/Public/CompanyLogo';
import Avatar from '@/Components/atoms/Avatar';
import { SearchIcon } from '@/Components/UI/icons';
import { focusRing } from '@/design-system/classNames';

const SEARCH_LABEL = 'Search companies and public profiles';
const MIN_QUERY = 2;

type SuggestResults = {
    jobs: Array<{ id: number; title: string; company: string | null; location: string | null; url: string }>;
    companies: Array<{
        id: number;
        name: string;
        industry: string | null;
        location: string | null;
        logo_url: string | null;
        url: string;
    }>;
    profiles: Array<{ name: string; headline: string | null; photo_url: string | null; url: string }>;
};

type FilterTab = 'all' | 'companies' | 'profiles';

type SearchFormProps = {
    id: string;
    className?: string;
    inputClassName?: string;
    onSubmitted?: () => void;
    dropdown?: boolean;
};

function GlobalSearchForm({ id, className = '', inputClassName = '', onSubmitted, dropdown = true }: SearchFormProps) {
    const [query, setQuery] = useState('');
    const [filter, setFilter] = useState<FilterTab>('all');
    const [open, setOpen] = useState(false);
    const [loading, setLoading] = useState(false);
    const [results, setResults] = useState<SuggestResults | null>(null);
    const rootRef = useRef<HTMLDivElement>(null);
    const debounceRef = useRef<number | null>(null);

    const fetchSuggestions = useCallback(async (term: string) => {
        const q = term.trim();
        if (q.length < MIN_QUERY) {
            setResults(null);
            setLoading(false);
            return;
        }
        setLoading(true);
        try {
            const response = await fetch(`/search/suggest?q=${encodeURIComponent(q)}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) {
                setResults(null);
                return;
            }
            const payload = (await response.json()) as { results: SuggestResults };
            setResults(payload.results);
        } catch {
            setResults(null);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        if (!dropdown) {
            return;
        }
        if (debounceRef.current !== null) {
            window.clearTimeout(debounceRef.current);
        }
        if (query.trim().length < MIN_QUERY) {
            setResults(null);
            setLoading(false);
            return;
        }
        setLoading(true);
        debounceRef.current = window.setTimeout(() => {
            void fetchSuggestions(query);
        }, 280);
        return () => {
            if (debounceRef.current !== null) {
                window.clearTimeout(debounceRef.current);
            }
        };
    }, [dropdown, fetchSuggestions, query]);

    useEffect(() => {
        if (!dropdown) {
            return;
        }
        const onPointerDown = (event: MouseEvent) => {
            if (!rootRef.current?.contains(event.target as Node)) {
                setOpen(false);
            }
        };
        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        };
        document.addEventListener('mousedown', onPointerDown);
        document.addEventListener('keydown', onKeyDown);
        return () => {
            document.removeEventListener('mousedown', onPointerDown);
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [dropdown]);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        setOpen(false);
        onSubmitted?.();
    };

    const trimmed = query.trim();
    const showPanel = dropdown && open && trimmed.length >= MIN_QUERY;

    const companies = results?.companies ?? [];
    const profiles = results?.profiles ?? [];

    const visibleCompanies = filter === 'profiles' ? [] : companies;
    const visibleProfiles = filter === 'companies' ? [] : profiles;

    const hasAny = visibleCompanies.length > 0 || visibleProfiles.length > 0;

    const closeAndNavigate = () => {
        setOpen(false);
        onSubmitted?.();
    };

    const tabClass = (active: boolean) =>
        `rounded-md px-2.5 py-1 text-xs font-semibold ${active ? 'bg-brand/10 text-brand' : 'text-muted hover:bg-surface hover:text-ink'}`;

    return (
        <div ref={rootRef} className={`relative min-w-0 ${className}`}>
            <form onSubmit={submit}>
                <SearchField
                    id={id}
                    aria-label={SEARCH_LABEL}
                    placeholder="Search companies & profiles"
                    value={query}
                    onChange={(event) => {
                        setQuery(event.currentTarget.value);
                        setOpen(true);
                    }}
                    onFocus={() => setOpen(true)}
                    autoComplete="off"
                    role="combobox"
                    aria-expanded={showPanel}
                    aria-controls={`${id}-suggest`}
                    inputClassName={inputClassName}
                />
            </form>
            {showPanel ? (
                <div
                    id={`${id}-suggest`}
                    role="listbox"
                    className="absolute left-0 right-0 top-full z-[60] mt-2 max-h-[min(24rem,70vh)] overflow-y-auto rounded-lg border border-line bg-white py-2 shadow-lg"
                >
                    <div className="flex flex-wrap gap-1 border-b border-line px-3 pb-2">
                        <button type="button" className={tabClass(filter === 'all')} onClick={() => setFilter('all')}>
                            All
                        </button>
                        <button type="button" className={tabClass(filter === 'companies')} onClick={() => setFilter('companies')}>
                            Companies
                        </button>
                        <button type="button" className={tabClass(filter === 'profiles')} onClick={() => setFilter('profiles')}>
                            Profiles
                        </button>
                    </div>
                    {loading ? <p className="px-4 py-3 text-sm text-muted">Searching…</p> : null}
                    {!loading && !hasAny ? <p className="px-4 py-3 text-sm text-muted">No results found.</p> : null}
                    {!loading && hasAny ? (
                        <ul className="px-2 py-1">
                            {visibleCompanies.map((company) => (
                                <li key={`c-${company.id}`}>
                                    <Link
                                        href={company.url}
                                        onClick={closeAndNavigate}
                                        className="flex min-w-0 items-center gap-3 rounded-md px-2 py-2 hover:bg-surface"
                                    >
                                        <CompanyLogo
                                            name={company.name}
                                            src={company.logo_url}
                                            sizeClass="h-9 w-9"
                                            shape="rounded-lg"
                                        />
                                        <span className="min-w-0">
                                            <span className="block truncate text-sm font-medium text-ink">{company.name}</span>
                                            <span className="block truncate text-xs text-muted">
                                                {[company.industry, company.location].filter(Boolean).join(' · ') || 'Company profile'}
                                            </span>
                                        </span>
                                    </Link>
                                </li>
                            ))}
                            {visibleProfiles.map((profile) => (
                                <li key={profile.url}>
                                    <Link
                                        href={profile.url}
                                        onClick={closeAndNavigate}
                                        className="flex min-w-0 items-center gap-3 rounded-md px-2 py-2 hover:bg-surface"
                                    >
                                        <Avatar name={profile.name} src={profile.photo_url ?? undefined} size="sm" shape="circle" />
                                        <span className="min-w-0">
                                            <span className="block truncate text-sm font-medium text-ink">{profile.name}</span>
                                            {profile.headline ? (
                                                <span className="block truncate text-xs text-muted">{profile.headline}</span>
                                            ) : (
                                                <span className="block truncate text-xs text-muted">Public profile</span>
                                            )}
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    ) : null}
                </div>
            ) : null}
        </div>
    );
}

export default function GlobalHeaderSearch({ className = '' }: { className?: string }) {
    const id = useId();

    return (
        <GlobalSearchForm
            id={id}
            className={`hidden min-w-0 md:block ${className}`}
            inputClassName="w-full min-w-[18rem]"
            dropdown
        />
    );
}

/** Compact search icon on narrow viewports; expands the same global search form in-place. */
export function GlobalHeaderSearchMobile() {
    const id = useId();
    const [open, setOpen] = useState(false);

    return (
        <div className="relative md:hidden">
            <IconButton
                label={open ? 'Close search' : SEARCH_LABEL}
                className={focusRing}
                aria-expanded={open}
                onClick={() => setOpen((value) => !value)}
            >
                <SearchIcon />
            </IconButton>
            {open ? (
                <div className="absolute right-0 top-full z-[60] mt-2 w-[min(20rem,calc(100vw-2rem))] rounded-lg border border-line bg-white p-2 shadow-lg">
                    <GlobalSearchForm id={id} inputClassName="w-full" dropdown onSubmitted={() => setOpen(false)} />
                </div>
            ) : null}
        </div>
    );
}
