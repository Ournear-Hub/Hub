import { useForm } from '@inertiajs/react';
import { useEffect, useMemo, useState, type FormEvent, type ReactNode } from 'react';
import Button from '@/Components/atoms/Button';
import FormField from '@/Components/Public/FormField';
import { accountLayout } from '@/design-system/classNames';
import type { AccountProfessionalProfile, AccountProfileLookups } from '@/types/account';

const selectClass =
    'w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand';

type PreferenceForm = {
    preferred_location_ids: number[];
    preferred_employment_type_ids: number[];
    employment_preference: string;
    willing_to_relocate: boolean;
    availability: string;
};

type Props = {
    professional: AccountProfessionalProfile | null | undefined;
    lookups: AccountProfileLookups;
};

function toggleNumber(values: number[], id: number): number[] {
    return values.includes(id) ? values.filter((item) => item !== id) : [...values, id];
}

function sliceFromProfessional(pro: AccountProfessionalProfile | null | undefined): PreferenceForm {
    const data = pro ?? {
        preferred_location_ids: [],
        preferred_employment_type_ids: [],
        employment_preference: null,
        willing_to_relocate: false,
        availability: null,
    };
    return {
        preferred_location_ids: data.preferred_location_ids ?? [],
        preferred_employment_type_ids: data.preferred_employment_type_ids ?? [],
        employment_preference: data.employment_preference ?? '',
        willing_to_relocate: data.willing_to_relocate ?? false,
        availability: data.availability ?? '',
    };
}

function hasSavedPreferences(pro: AccountProfessionalProfile | null | undefined): boolean {
    if (!pro) {
        return false;
    }
    return (
        (pro.preferred_locations?.length ?? 0) > 0 ||
        (pro.preferred_employment_types?.length ?? 0) > 0 ||
        Boolean(pro.employment_preference?.trim()) ||
        pro.willing_to_relocate === true ||
        Boolean(pro.availability?.trim())
    );
}

function ChipList({ items, emptyLabel }: { items: string[]; emptyLabel: string }) {
    if (items.length === 0) {
        return <span className="text-sm text-muted">{emptyLabel}</span>;
    }
    return (
        <ul className="flex flex-wrap gap-2">
            {items.map((item) => (
                <li key={item} className="rounded-full bg-surface px-3 py-1 text-sm text-ink">
                    {item}
                </li>
            ))}
        </ul>
    );
}

function FieldRow({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div className="grid gap-1 sm:grid-cols-[minmax(8rem,12rem)_1fr] sm:gap-4">
            <dt className="text-sm font-medium text-muted">{label}</dt>
            <dd className="text-sm text-ink">{value}</dd>
        </div>
    );
}

export default function CandidateJobPreferences({ professional, lookups }: Props) {
    const [editing, setEditing] = useState(false);
    const saved = useMemo(() => sliceFromProfessional(professional), [professional]);
    const form = useForm<PreferenceForm>(saved);

    useEffect(() => {
        if (!editing) {
            form.setData(saved);
            form.clearErrors();
        }
    }, [saved, editing]);

    const locationNames = (professional?.preferred_locations ?? []).map((item) => item.name);
    const typeNames = (professional?.preferred_employment_types ?? []).map((item) => item.name);
    const empty = !hasSavedPreferences(professional);

    const closeEditor = () => {
        setEditing(false);
        form.setData(saved);
        form.clearErrors();
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put('/account/profile/professional', {
            preserveScroll: true,
            onSuccess: () => {
                setEditing(false);
            },
        });
    };

    return (
        <div className={accountLayout.profileWorkspace}>
            <header className="min-w-0">
                <h1 className="text-xl font-semibold text-ink sm:text-2xl">Job Preferences</h1>
                <p className="mt-1 text-sm text-muted">Tell us what kind of opportunities you&apos;re looking for.</p>
            </header>

            {!editing ? (
                <section className="rounded-xl border border-line bg-white p-5 sm:p-6">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <h2 className="text-base font-semibold text-ink">Your preferences</h2>
                        <button
                            type="button"
                            className="text-sm font-semibold text-brand hover:underline"
                            onClick={() => setEditing(true)}
                        >
                            {empty ? 'Add preferences' : 'Edit Preferences'}
                        </button>
                    </div>

                    {empty ? (
                        <p className="mt-4 text-sm text-muted">
                            You have not saved job preferences yet. Add preferred locations, job types, and other
                            details so we can match you with relevant opportunities.
                        </p>
                    ) : (
                        <dl className="mt-4 space-y-4">
                            <div>
                                <dt className="mb-2 text-sm font-medium text-muted">Preferred locations</dt>
                                <dd>
                                    <ChipList items={locationNames} emptyLabel="No preferred locations selected." />
                                </dd>
                            </div>
                            <div>
                                <dt className="mb-2 text-sm font-medium text-muted">Preferred job types</dt>
                                <dd>
                                    <ChipList items={typeNames} emptyLabel="No preferred job types selected." />
                                </dd>
                            </div>
                            <FieldRow
                                label="Employment preferences"
                                value={
                                    professional?.employment_preference?.trim() ? (
                                        <span className="whitespace-pre-wrap">{professional.employment_preference}</span>
                                    ) : (
                                        <span className="text-muted">—</span>
                                    )
                                }
                            />
                            <FieldRow
                                label="Willing to relocate"
                                value={professional?.willing_to_relocate ? 'Yes' : 'No'}
                            />
                            <FieldRow
                                label="Availability"
                                value={
                                    professional?.availability?.trim() ? professional.availability : (
                                        <span className="text-muted">—</span>
                                    )
                                }
                            />
                        </dl>
                    )}
                </section>
            ) : (
                <form onSubmit={submit} className="space-y-6 rounded-xl border border-line bg-white p-5 sm:p-6">
                    <fieldset>
                        <legend className="mb-2 text-sm font-medium text-ink">Preferred locations</legend>
                        <div className="grid gap-2 sm:grid-cols-2">
                            {lookups.locations.length === 0 ? (
                                <p className="text-sm text-muted">No locations are available yet.</p>
                            ) : (
                                lookups.locations
                                    .filter(
                                        (location) =>
                                            location.selectable !== false ||
                                            form.data.preferred_location_ids.includes(location.id),
                                    )
                                    .map((location) => (
                                        <label key={location.id} className="flex items-center gap-2 text-sm text-ink">
                                            <input
                                                type="checkbox"
                                                checked={form.data.preferred_location_ids.includes(location.id)}
                                                onChange={() =>
                                                    form.setData(
                                                        'preferred_location_ids',
                                                        toggleNumber(form.data.preferred_location_ids, location.id),
                                                    )
                                                }
                                            />
                                            {location.name}
                                        </label>
                                    ))
                            )}
                        </div>
                        {form.errors.preferred_location_ids ? (
                            <p className="mt-1 text-sm text-danger">{form.errors.preferred_location_ids}</p>
                        ) : null}
                    </fieldset>

                    <fieldset>
                        <legend className="mb-2 text-sm font-medium text-ink">Preferred job types</legend>
                        <div className="grid gap-2 sm:grid-cols-2">
                            {lookups.employment_types
                                .filter(
                                    (type) =>
                                        type.selectable !== false ||
                                        form.data.preferred_employment_type_ids.includes(type.id),
                                )
                                .map((type) => (
                                    <label key={type.id} className="flex items-center gap-2 text-sm text-ink">
                                        <input
                                            type="checkbox"
                                            checked={form.data.preferred_employment_type_ids.includes(type.id)}
                                            onChange={() =>
                                                form.setData(
                                                    'preferred_employment_type_ids',
                                                    toggleNumber(form.data.preferred_employment_type_ids, type.id),
                                                )
                                            }
                                        />
                                        {type.name}
                                    </label>
                                ))}
                        </div>
                        {form.errors.preferred_employment_type_ids ? (
                            <p className="mt-1 text-sm text-danger">{form.errors.preferred_employment_type_ids}</p>
                        ) : null}
                    </fieldset>

                    <FormField
                        id="job_pref_employment_preference"
                        label="Employment preferences"
                        as="textarea"
                        maxLength={2000}
                        value={form.data.employment_preference}
                        onChange={(event) => form.setData('employment_preference', event.target.value)}
                    />
                    {form.errors.employment_preference ? (
                        <p className="-mt-2 text-sm text-danger">{form.errors.employment_preference}</p>
                    ) : null}

                    <label className="flex items-center gap-2 text-sm text-ink">
                        <input
                            type="checkbox"
                            checked={form.data.willing_to_relocate}
                            onChange={(event) => form.setData('willing_to_relocate', event.target.checked)}
                        />
                        Willing to relocate
                    </label>

                    <FormField
                        id="job_pref_availability"
                        label="Availability"
                        value={form.data.availability}
                        onChange={(event) => form.setData('availability', event.target.value)}
                        placeholder="For example, Immediate or 30 days"
                    />
                    {form.errors.availability ? (
                        <p className="-mt-2 text-sm text-danger">{form.errors.availability}</p>
                    ) : null}

                    <div className="flex flex-wrap gap-3 border-t border-line pt-4">
                        <Button type="submit" variant="primary" loading={form.processing} disabled={form.processing}>
                            Save Preferences
                        </Button>
                        <Button type="button" variant="outline" disabled={form.processing} onClick={closeEditor}>
                            Cancel
                        </Button>
                    </div>
                </form>
            )}
        </div>
    );
}
