import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useMemo, useState } from 'react';
import type { MediaOption } from '@/types/media';

type StatForm = {
    registered_companies: number;
    talent_pool_companies: number;
    staffing_companies: number;
    registered_users: number;
};

type ShowcaseCompanyAdmin = {
    id: number;
    name: string;
    is_active: boolean;
    sort_order: number;
    show_on_home: boolean;
    show_on_talent_pool: boolean;
    show_on_staffing: boolean;
    media_asset_id: number | null;
    logo_url: string | null;
    logo_filename: string | null;
    employer_profile_id?: number | null;
};

type EmployerOption = {
    id: number;
    name: string;
};

type Props = {
    mode: 'statistics' | 'companies';
    statistics?: StatForm | null;
    companies?: ShowcaseCompanyAdmin[];
    showcaseSurface?: 'home' | 'talent_pool' | 'staffing' | null;
    mediaOptions: MediaOption[];
    employerOptions?: EmployerOption[];
    activePage: string;
    tablesReady?: boolean;
};

const inputClass =
    'w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand';

type SurfaceFlag = 'show_on_home' | 'show_on_talent_pool' | 'show_on_staffing';

function surfaceFlag(surface: Props['showcaseSurface']): SurfaceFlag {
    switch (surface) {
        case 'talent_pool':
            return 'show_on_talent_pool';
        case 'staffing':
            return 'show_on_staffing';
        default:
            return 'show_on_home';
    }
}

function CompanyEditor({
    company,
    surface,
    mediaOptions,
    employerOptions = [],
    activePage,
}: {
    company?: ShowcaseCompanyAdmin;
    surface: Props['showcaseSurface'];
    mediaOptions: MediaOption[];
    employerOptions?: EmployerOption[];
    activePage: string;
}) {
    const flag = surfaceFlag(surface);
    const isNew = company === undefined;

    const form = useForm({
        name: company?.name ?? '',
        employer_profile_id: company?.employer_profile_id ? String(company.employer_profile_id) : '',
        is_active: company?.is_active ?? true,
        sort_order: company?.sort_order ?? 0,
        show_on_home: company?.show_on_home ?? surface === 'home',
        show_on_talent_pool: company?.show_on_talent_pool ?? surface === 'talent_pool',
        show_on_staffing: company?.show_on_staffing ?? surface === 'staffing',
        logo: null as File | null,
        media_asset_id: '',
        remove_logo: false,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (isNew) {
            form.post('/admin/website-content/showcase/companies', {
                forceFormData: true,
                preserveScroll: true,
            });
            return;
        }
        form.put(`/admin/website-content/showcase/companies/${company.id}`, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const assignedOnSurface = company ? Boolean(company[flag]) : true;

    return (
        <form onSubmit={submit} className="rounded-xl border border-line bg-white p-4 space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <h3 className="text-base font-semibold text-ink">{isNew ? 'Add company' : company.name}</h3>
                {!isNew ? (
                    <label className="flex items-center gap-2 text-sm text-ink">
                        <input
                            type="checkbox"
                            checked={form.data.is_active}
                            onChange={(e) => form.setData('is_active', e.target.checked)}
                        />
                        Active
                    </label>
                ) : null}
            </div>

            <div>
                <label className="mb-1 block text-sm font-medium text-ink">Company name</label>
                <input
                    className={inputClass}
                    value={form.data.name}
                    onChange={(e) => form.setData('name', e.target.value)}
                    required
                />
                {form.errors.name ? <p className="mt-1 text-sm text-danger">{form.errors.name}</p> : null}
            </div>

            <div>
                <label className="mb-1 block text-sm font-medium text-ink">Canonical company</label>
                <select
                    className={inputClass}
                    value={form.data.employer_profile_id}
                    onChange={(e) => form.setData('employer_profile_id', e.target.value)}
                >
                    <option value="">Showcase card only (not linked)</option>
                    {employerOptions.map((option) => (
                        <option key={option.id} value={option.id}>
                            {option.name}
                        </option>
                    ))}
                </select>
                <p className="mt-1 text-xs text-muted">
                    Link an existing company so Home, Talent Pool, and Staffing placements share one record. Page flags below are placement settings, not a new company.
                </p>
                {form.errors.employer_profile_id ? (
                    <p className="mt-1 text-sm text-danger">{form.errors.employer_profile_id}</p>
                ) : null}
            </div>

            {!isNew ? (
                <label className="flex items-center gap-2 text-sm text-ink">
                    <input
                        type="checkbox"
                        checked={form.data[flag] as boolean}
                        onChange={(e) => form.setData(flag, e.target.checked)}
                    />
                    Show on this section
                </label>
            ) : null}

            {company?.logo_url ? (
                <div>
                    <p className="text-xs text-muted">Current logo: {company.logo_filename ?? '—'}</p>
                    <img src={company.logo_url} alt="" className="mt-2 h-16 w-auto max-w-[12rem] object-contain" />
                </div>
            ) : null}

            <div>
                <label className="mb-1 block text-sm font-medium text-ink">Upload logo (PNG, JPG, WEBP)</label>
                <input
                    type="file"
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    className={inputClass}
                    onChange={(e) => {
                        const file = e.target.files?.[0] ?? null;
                        form.setData('logo', file);
                        if (file) {
                            form.setData('media_asset_id', '');
                            form.setData('remove_logo', false);
                        }
                    }}
                />
                {form.errors.logo ? <p className="mt-1 text-sm text-danger">{form.errors.logo}</p> : null}
            </div>

            {mediaOptions.length > 0 ? (
                <div>
                    <label className="mb-1 block text-sm font-medium text-ink">Or select from media library</label>
                    <select
                        className={inputClass}
                        value={form.data.media_asset_id}
                        onChange={(e) => {
                            form.setData('media_asset_id', e.target.value);
                            if (e.target.value) {
                                form.setData('logo', null);
                                form.setData('remove_logo', false);
                            }
                        }}
                    >
                        <option value="">— Keep current / use upload —</option>
                        {mediaOptions.map((option) => (
                            <option key={option.id} value={String(option.id)}>
                                {option.original_name} (#{option.id})
                            </option>
                        ))}
                    </select>
                </div>
            ) : null}

            {company?.logo_url ? (
                <label className="flex items-center gap-2 text-sm text-ink">
                    <input
                        type="checkbox"
                        checked={form.data.remove_logo}
                        onChange={(e) => form.setData('remove_logo', e.target.checked)}
                    />
                    Remove logo (keeps company record)
                </label>
            ) : null}

            <div className="flex flex-wrap gap-2">
                <button type="submit" className="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white" disabled={form.processing}>
                    Save
                </button>
                <button
                    type="button"
                    className="rounded-md border border-line px-4 py-2 text-sm font-semibold text-ink"
                    onClick={() => form.reset()}
                >
                    Cancel
                </button>
            </div>

            {!isNew && !assignedOnSurface ? (
                <p className="text-xs text-muted">This company is not assigned to the current section until you enable “Show on this section” and save.</p>
            ) : null}
        </form>
    );
}

export default function ShowcasePanel({
    mode,
    statistics,
    companies = [],
    showcaseSurface,
    mediaOptions,
    employerOptions = [],
    activePage,
    tablesReady = true,
}: Props) {
    const [editingId, setEditingId] = useState<number | 'new' | null>(null);

    const statsForm = useForm({
        registered_companies: statistics?.registered_companies ?? 0,
        talent_pool_companies: statistics?.talent_pool_companies ?? 0,
        staffing_companies: statistics?.staffing_companies ?? 0,
        registered_users: statistics?.registered_users ?? 0,
    });

    const initialStats = useMemo(() => statistics ?? null, [statistics]);

    const saveStats = (event: FormEvent) => {
        event.preventDefault();
        statsForm.put('/admin/website-content/showcase/statistics', { preserveScroll: true });
    };

    const cancelStats = () => {
        if (initialStats) {
            statsForm.setData(initialStats);
        }
    };

    const filteredCompanies = useMemo(() => {
        if (!showcaseSurface) {
            return companies;
        }
        const flag = surfaceFlag(showcaseSurface);
        return companies.filter((c) => c[flag]);
    }, [companies, showcaseSurface]);

    if (mode === 'statistics') {
        return (
            <form onSubmit={saveStats} className="max-w-xl space-y-4 rounded-xl border border-line bg-white p-4">
                {!tablesReady ? (
                    <p className="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                        Showcase database tables are not migrated yet. Run <code className="font-mono">php artisan migrate</code> from the
                        project root, then reload this page to save statistics.
                    </p>
                ) : null}
                <p className="text-sm text-muted">Manual homepage counters only — not calculated from live database totals.</p>
                {(
                    [
                        ['registered_companies', 'Registered Companies'],
                        ['talent_pool_companies', 'Talent Pool Companies'],
                        ['staffing_companies', 'Staffing Companies'],
                        ['registered_users', 'Registered Users'],
                    ] as const
                ).map(([key, label]) => (
                    <div key={key}>
                        <label className="mb-1 block text-sm font-medium text-ink">{label}</label>
                        <input
                            type="number"
                            min={0}
                            className={inputClass}
                            value={statsForm.data[key]}
                            onChange={(e) => statsForm.setData(key, Number(e.target.value))}
                        />
                        {statsForm.errors[key] ? <p className="mt-1 text-sm text-danger">{statsForm.errors[key]}</p> : null}
                    </div>
                ))}
                <div className="flex gap-2">
                    <button type="submit" className="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white" disabled={statsForm.processing}>
                        Save
                    </button>
                    <button type="button" onClick={cancelStats} className="rounded-md border border-line px-4 py-2 text-sm font-semibold text-ink">
                        Cancel
                    </button>
                </div>
            </form>
        );
    }

    const editingCompany = editingId === 'new' ? undefined : companies.find((c) => c.id === editingId);

    return (
        <div className="space-y-6">
            {!tablesReady ? (
                <p className="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                    Showcase database tables are not migrated yet. Run <code className="font-mono">php artisan migrate</code> before adding
                    or editing companies.
                </p>
            ) : null}
            <p className="text-sm text-muted">
                Shared company records — assign each company to Home, Talent Pool, and/or Staffing independently. Inactive companies are hidden on all public sections.
            </p>

            <div className="flex flex-wrap gap-2">
                <button
                    type="button"
                    className="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white"
                    onClick={() => setEditingId('new')}
                >
                    Add company
                </button>
            </div>

            {editingId !== null ? (
                <CompanyEditor
                    company={editingCompany}
                    surface={showcaseSurface}
                    mediaOptions={mediaOptions}
                    employerOptions={employerOptions}
                    activePage={activePage}
                />
            ) : null}

            <div className="overflow-x-auto rounded-xl border border-line bg-white">
                <table className="min-w-full text-left text-sm">
                    <thead className="border-b border-line bg-surface text-muted">
                        <tr>
                            <th className="px-4 py-3 font-medium">Order</th>
                            <th className="px-4 py-3 font-medium">Name</th>
                            <th className="px-4 py-3 font-medium">Logo</th>
                            <th className="px-4 py-3 font-medium">Active</th>
                            <th className="px-4 py-3 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {filteredCompanies.length === 0 ? (
                            <tr>
                                <td colSpan={5} className="px-4 py-8 text-muted">
                                    No companies assigned to this section yet.
                                </td>
                            </tr>
                        ) : (
                            filteredCompanies.map((company) => (
                                <tr key={company.id} className="border-b border-line last:border-0">
                                    <td className="px-4 py-3 text-muted">{company.sort_order}</td>
                                    <td className="px-4 py-3 font-medium text-ink">{company.name}</td>
                                    <td className="px-4 py-3">
                                        {company.logo_url ? (
                                            <img src={company.logo_url} alt="" className="h-10 w-auto max-w-[8rem] object-contain" />
                                        ) : (
                                            '—'
                                        )}
                                    </td>
                                    <td className="px-4 py-3 text-muted">{company.is_active ? 'Yes' : 'No'}</td>
                                    <td className="px-4 py-3">
                                        <button
                                            type="button"
                                            className="font-medium text-brand hover:text-accent"
                                            onClick={() => setEditingId(company.id)}
                                        >
                                            Edit
                                        </button>
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            <details className="text-sm text-muted">
                <summary className="cursor-pointer font-medium text-ink">All showcase companies ({companies.length})</summary>
                <ul className="mt-2 list-disc pl-5">
                    {companies.map((c) => (
                        <li key={c.id}>
                            {c.name}
                            {!c.is_active ? ' (inactive)' : ''}
                        </li>
                    ))}
                </ul>
            </details>
        </div>
    );
}
