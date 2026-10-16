import { router, useForm } from '@inertiajs/react';
import { useEffect, useMemo, useState, type FormEvent } from 'react';
import EmploymentTimeline from '@/Components/Account/EmploymentTimeline';
import Combobox from '@/Components/molecules/Combobox';
import FormField from '@/Components/Public/FormField';
import type {
    AccountCertification,
    AccountEducation,
    AccountEmploymentHistory,
    AccountProfessionalLanguage,
    AccountProfileLookups,
    AccountProfessionalProfile,
    AccountProject,
} from '@/types/account';

const selectClass =
    'w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand';

const emptyProfessional: AccountProfessionalProfile = {
    current_designation: null,
    current_company: null,
    industry_id: null,
    industry: null,
    job_category_id: null,
    functional_area: null,
    experience: null,
    experience_range_id: null,
    qualification: null,
    qualification_id: null,
    current_salary: null,
    expected_salary: null,
    notice_period: null,
    notice_period_label: null,
    employment_preference: null,
    skills: [],
    employment_histories: [],
    preferred_location_ids: [],
    preferred_locations: [],
    preferred_employment_type_ids: [],
    preferred_employment_types: [],
    willing_to_relocate: false,
    availability: null,
    certifications: [],
    languages: [],
    educations: [],
    projects: [],
};

type Focus =
    | 'professional'
    | 'preferences'
    | 'skills'
    | 'experience'
    | 'certifications'
    | 'education'
    | 'projects'
    | 'languages';

type Props = {
    professional?: AccountProfessionalProfile | null;
    lookups: AccountProfileLookups;
    focus?: Focus | null;
    onCancel?: () => void;
};

function salaryValue(value: string | number | null | undefined): string {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    return String(value);
}

function toggleNumber(values: number[], id: number): number[] {
    return values.includes(id) ? values.filter((item) => item !== id) : [...values, id];
}

export default function CandidateProfessionalSections({ professional, lookups, focus = null, onCancel }: Props) {
    const show = (section: Focus) => focus === null || focus === section;
    const data = professional ?? emptyProfessional;
    const [skillName, setSkillName] = useState('');
    const [editingHistoryId, setEditingHistoryId] = useState<number | null>(null);

    const skillOptions = useMemo(
        () =>
            (lookups.skills ?? []).map((skill) => ({
                value: skill.name,
                label: skill.name,
            })),
        [lookups.skills],
    );

    const professionalForm = useForm({
        current_designation: data.current_designation ?? '',
        current_company: data.current_company ?? '',
        industry_id: data.industry_id ? String(data.industry_id) : '',
        experience_range_id: data.experience_range_id ? String(data.experience_range_id) : '',
        qualification: data.qualification ?? '',
        qualification_id: data.qualification_id ? String(data.qualification_id) : '',
        current_salary: salaryValue(data.current_salary),
        expected_salary: salaryValue(data.expected_salary),
        notice_period: data.notice_period ?? '',
        employment_preference: data.employment_preference ?? '',
        preferred_location_ids: data.preferred_location_ids,
        preferred_employment_type_ids: data.preferred_employment_type_ids,
        willing_to_relocate: data.willing_to_relocate ?? false,
        availability: data.availability ?? '',
    });

    const historyForm = useForm({
        company: '',
        designation: '',
        industry_id: '',
        started_on: '',
        ended_on: '',
        is_current: false,
        is_internship: false,
        description: '',
    });

    useEffect(() => {
        professionalForm.setData({
            current_designation: data.current_designation ?? '',
            current_company: data.current_company ?? '',
            industry_id: data.industry_id ? String(data.industry_id) : '',
            experience_range_id: data.experience_range_id ? String(data.experience_range_id) : '',
            qualification: data.qualification ?? '',
            qualification_id: data.qualification_id ? String(data.qualification_id) : '',
            current_salary: salaryValue(data.current_salary),
            expected_salary: salaryValue(data.expected_salary),
            notice_period: data.notice_period ?? '',
            employment_preference: data.employment_preference ?? '',
            preferred_location_ids: data.preferred_location_ids,
            preferred_employment_type_ids: data.preferred_employment_type_ids,
            willing_to_relocate: data.willing_to_relocate ?? false,
            availability: data.availability ?? '',
        });
    }, [professional]);

    const submitProfessional = (event: FormEvent) => {
        event.preventDefault();

        const onSuccess = () => {
            onCancel?.();
        };

        const onError = (errors: Record<string, string>) => {
            Object.entries(errors).forEach(([key, message]) => {
                professionalForm.setError(key as keyof typeof professionalForm.data, message);
            });
        };

        if (focus === 'preferences') {
            router.put(
                '/account/profile/professional',
                {
                    preferred_location_ids: professionalForm.data.preferred_location_ids,
                    preferred_employment_type_ids: professionalForm.data.preferred_employment_type_ids,
                    employment_preference: professionalForm.data.employment_preference,
                    willing_to_relocate: professionalForm.data.willing_to_relocate,
                    availability: professionalForm.data.availability,
                },
                { preserveScroll: true, onSuccess, onError },
            );
            return;
        }

        if (focus === 'professional') {
            router.put(
                '/account/profile/professional',
                {
                    current_designation: professionalForm.data.current_designation,
                    current_company: professionalForm.data.current_company,
                    industry_id: professionalForm.data.industry_id,
                    experience_range_id: professionalForm.data.experience_range_id,
                    qualification: professionalForm.data.qualification,
                    qualification_id: professionalForm.data.qualification_id,
                    current_salary: professionalForm.data.current_salary,
                    expected_salary: professionalForm.data.expected_salary,
                    notice_period: professionalForm.data.notice_period,
                },
                { preserveScroll: true, onSuccess, onError },
            );
            return;
        }

        professionalForm.put('/account/profile/professional', {
            preserveScroll: true,
            onSuccess,
        });
    };

    const addSkill = (event: FormEvent) => {
        event.preventDefault();
        router.post(
            '/account/profile/skills',
            { name: skillName },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setSkillName('');
                    router.reload({ only: ['profile'] });
                },
            },
        );
    };

    const removeSkill = (id: number) => {
        router.delete(`/account/profile/skills/${id}`, {
            preserveScroll: true,
            onSuccess: () => router.reload({ only: ['profile'] }),
        });
    };

    const startEditHistory = (history: AccountEmploymentHistory) => {
        setEditingHistoryId(history.id);
        historyForm.setData({
            company: history.company,
            designation: history.designation,
            industry_id: history.industry_id ? String(history.industry_id) : '',
            started_on: history.started_on ?? '',
            ended_on: history.ended_on ?? '',
            is_current: history.is_current,
            is_internship: history.is_internship ?? false,
            description: history.description ?? '',
        });
        historyForm.clearErrors();
    };

    const resetHistoryForm = () => {
        setEditingHistoryId(null);
        historyForm.reset();
        historyForm.clearErrors();
    };

    const submitHistory = (event: FormEvent) => {
        event.preventDefault();
        if (editingHistoryId) {
            historyForm.put(`/account/profile/employment-history/${editingHistoryId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    resetHistoryForm();
                    router.reload({ only: ['profile'] });
                },
            });
            return;
        }

        historyForm.post('/account/profile/employment-history', {
            preserveScroll: true,
            onSuccess: () => {
                resetHistoryForm();
                router.reload({ only: ['profile'] });
            },
        });
    };

    const removeHistory = (id: number) => {
        if (!window.confirm('Remove this employment history record?')) {
            return;
        }
        router.delete(`/account/profile/employment-history/${id}`, {
            preserveScroll: true,
            onSuccess: () => router.reload({ only: ['profile'] }),
        });
    };

    return (
        <div className="space-y-8">
            {show('professional') || show('preferences') ? (
            <form onSubmit={submitProfessional} className="space-y-8 rounded-xl border border-line bg-white p-6">
                {show('professional') ? (
                <section className="space-y-4">
                    <h2 className="text-lg font-semibold text-ink">Professional</h2>
                    <p className="text-sm text-muted">
                        Recruitment details stay on your candidate profile. Salary is private and is not shown
                        publicly.
                    </p>
                    <FormField
                        id="current_designation"
                        label="Current designation"
                        value={professionalForm.data.current_designation}
                        onChange={(event) => professionalForm.setData('current_designation', event.target.value)}
                    />
                    {professionalForm.errors.current_designation && (
                        <p className="-mt-2 text-sm text-danger">{professionalForm.errors.current_designation}</p>
                    )}
                    <FormField
                        id="current_company"
                        label="Current company"
                        value={professionalForm.data.current_company}
                        onChange={(event) => professionalForm.setData('current_company', event.target.value)}
                    />
                    {professionalForm.errors.current_company && (
                        <p className="-mt-2 text-sm text-danger">{professionalForm.errors.current_company}</p>
                    )}
                    <div>
                        <label htmlFor="industry_id" className="mb-1 block text-sm font-medium text-ink">
                            Current industry
                        </label>
                        <select
                            id="industry_id"
                            className={selectClass}
                            value={professionalForm.data.industry_id}
                            onChange={(event) => professionalForm.setData('industry_id', event.target.value)}
                        >
                            <option value="">Not selected</option>
                            {lookups.industries
                                .filter(
                                    (industry) =>
                                        industry.selectable !== false ||
                                        String(industry.id) === professionalForm.data.industry_id,
                                )
                                .map((industry) => (
                                    <option key={industry.id} value={industry.id}>
                                        {industry.name}
                                    </option>
                                ))}
                        </select>
                        {professionalForm.errors.industry_id && (
                            <p className="mt-1 text-sm text-danger">{professionalForm.errors.industry_id}</p>
                        )}
                    </div>
                    <div>
                        <label htmlFor="experience_range_id" className="mb-1 block text-sm font-medium text-ink">
                            Total experience
                        </label>
                        <select
                            id="experience_range_id"
                            className={selectClass}
                            value={professionalForm.data.experience_range_id}
                            onChange={(event) => professionalForm.setData('experience_range_id', event.target.value)}
                        >
                            <option value="">Not classified</option>
                            {lookups.experience_ranges
                                .filter(
                                    (range) =>
                                        range.selectable !== false ||
                                        String(range.id) === professionalForm.data.experience_range_id,
                                )
                                .map((range) => (
                                    <option key={range.id} value={range.id}>
                                        {range.name}
                                    </option>
                                ))}
                        </select>
                        {professionalForm.errors.experience_range_id && (
                            <p className="mt-1 text-sm text-danger">{professionalForm.errors.experience_range_id}</p>
                        )}
                    </div>
                    <div>
                        <label htmlFor="qualification_pick" className="mb-1 block text-sm font-medium text-ink">
                            Qualification
                        </label>
                        <Combobox
                            id="qualification_pick"
                            surface="public"
                            placeholder="Search qualifications"
                            emptyMessage="No matching qualification"
                            options={lookups.qualifications
                                .filter(
                                    (item) =>
                                        item.selectable !== false ||
                                        String(item.id) === professionalForm.data.qualification_id,
                                )
                                .map((item) => ({ value: String(item.id), label: item.name }))}
                            value={professionalForm.data.qualification_id}
                            onChange={(next) => {
                                const selected = lookups.qualifications.find((item) => String(item.id) === next);
                                professionalForm.setData({
                                    qualification_id: next,
                                    qualification: selected?.name ?? '',
                                });
                            }}
                        />
                        {professionalForm.errors.qualification_id && (
                            <p className="mt-1 text-sm text-danger">{professionalForm.errors.qualification_id}</p>
                        )}
                    </div>
                    <FormField
                        id="qualification"
                        label="Or enter qualification if not listed"
                        value={
                            professionalForm.data.qualification_id
                                ? ''
                                : (professionalForm.data.qualification ?? '')
                        }
                        onChange={(event) =>
                            professionalForm.setData({
                                qualification_id: '',
                                qualification: event.target.value,
                            })
                        }
                    />
                    {professionalForm.errors.qualification && (
                        <p className="-mt-2 text-sm text-danger">{professionalForm.errors.qualification}</p>
                    )}
                    <div className="grid gap-4 sm:grid-cols-2">
                        <FormField
                            id="current_salary"
                            label="Current salary (private)"
                            type="number"
                            min="0"
                            step="0.01"
                            value={professionalForm.data.current_salary}
                            onChange={(event) => professionalForm.setData('current_salary', event.target.value)}
                        />
                        <FormField
                            id="expected_salary"
                            label="Expected salary (private)"
                            type="number"
                            min="0"
                            step="0.01"
                            value={professionalForm.data.expected_salary}
                            onChange={(event) => professionalForm.setData('expected_salary', event.target.value)}
                        />
                    </div>
                    {(professionalForm.errors.current_salary || professionalForm.errors.expected_salary) && (
                        <p className="-mt-2 text-sm text-danger">
                            {professionalForm.errors.current_salary || professionalForm.errors.expected_salary}
                        </p>
                    )}
                    <div>
                        <label htmlFor="notice_period" className="mb-1 block text-sm font-medium text-ink">
                            Notice period
                        </label>
                        <select
                            id="notice_period"
                            className={selectClass}
                            value={professionalForm.data.notice_period}
                            onChange={(event) => professionalForm.setData('notice_period', event.target.value)}
                        >
                            <option value="">Not selected</option>
                            {lookups.notice_periods.map((period) => (
                                <option key={period.value} value={period.value}>
                                    {period.label}
                                </option>
                            ))}
                        </select>
                        {professionalForm.errors.notice_period && (
                            <p className="mt-1 text-sm text-danger">{professionalForm.errors.notice_period}</p>
                        )}
                    </div>
                </section>
                ) : null}

                {show('preferences') ? (
                <section className="space-y-4">
                    <h2 className="text-lg font-semibold text-ink">Preferences</h2>
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
                                            professionalForm.data.preferred_location_ids.includes(location.id),
                                    )
                                    .map((location) => (
                                        <label key={location.id} className="flex items-center gap-2 text-sm text-ink">
                                            <input
                                                type="checkbox"
                                                checked={professionalForm.data.preferred_location_ids.includes(location.id)}
                                                onChange={() =>
                                                    professionalForm.setData(
                                                        'preferred_location_ids',
                                                        toggleNumber(professionalForm.data.preferred_location_ids, location.id),
                                                    )
                                                }
                                            />
                                            {location.name}
                                        </label>
                                    ))
                            )}
                        </div>
                        {professionalForm.errors.preferred_location_ids && (
                            <p className="mt-1 text-sm text-danger">{professionalForm.errors.preferred_location_ids}</p>
                        )}
                    </fieldset>
                    <fieldset>
                        <legend className="mb-2 text-sm font-medium text-ink">Preferred job types</legend>
                        <div className="grid gap-2 sm:grid-cols-2">
                            {lookups.employment_types
                                .filter(
                                    (type) =>
                                        type.selectable !== false ||
                                        professionalForm.data.preferred_employment_type_ids.includes(type.id),
                                )
                                .map((type) => (
                                    <label key={type.id} className="flex items-center gap-2 text-sm text-ink">
                                        <input
                                            type="checkbox"
                                            checked={professionalForm.data.preferred_employment_type_ids.includes(type.id)}
                                            onChange={() =>
                                                professionalForm.setData(
                                                    'preferred_employment_type_ids',
                                                    toggleNumber(professionalForm.data.preferred_employment_type_ids, type.id),
                                                )
                                            }
                                        />
                                        {type.name}
                                    </label>
                                ))}
                        </div>
                        {professionalForm.errors.preferred_employment_type_ids && (
                            <p className="mt-1 text-sm text-danger">{professionalForm.errors.preferred_employment_type_ids}</p>
                        )}
                    </fieldset>
                    <FormField
                        id="employment_preference"
                        label="Employment preferences"
                        as="textarea"
                        maxLength={2000}
                        value={professionalForm.data.employment_preference}
                        onChange={(event) => professionalForm.setData('employment_preference', event.target.value)}
                    />
                    {professionalForm.errors.employment_preference && (
                        <p className="-mt-2 text-sm text-danger">{professionalForm.errors.employment_preference}</p>
                    )}
                    <label className="flex items-center gap-2 text-sm text-ink">
                        <input
                            type="checkbox"
                            checked={professionalForm.data.willing_to_relocate}
                            onChange={(event) => professionalForm.setData('willing_to_relocate', event.target.checked)}
                        />
                        Willing to relocate
                    </label>
                    <FormField
                        id="availability"
                        label="Availability"
                        value={professionalForm.data.availability}
                        onChange={(event) => professionalForm.setData('availability', event.target.value)}
                        placeholder="For example, Immediate or 30 days"
                    />
                    {professionalForm.errors.availability && (
                        <p className="-mt-2 text-sm text-danger">{professionalForm.errors.availability}</p>
                    )}
                </section>
                ) : null}

                <div className="flex flex-wrap gap-3">
                <button
                    type="submit"
                    disabled={professionalForm.processing}
                    className="rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white hover:opacity-95 disabled:bg-disabled"
                >
                    Save
                </button>
                {onCancel ? (
                    <button type="button" className="rounded-md border border-line px-4 py-2.5 text-sm font-semibold text-ink" onClick={onCancel}>
                        Cancel
                    </button>
                ) : null}
                </div>
            </form>
            ) : null}

            {show('skills') ? (
            <section className="space-y-4 rounded-xl border border-line bg-white p-6">
                <h2 className="text-lg font-semibold text-ink">Skills</h2>
                <div className="flex flex-wrap gap-2">
                    {data.skills.length === 0 ? (
                        <p className="text-sm text-muted">No skills added yet.</p>
                    ) : (
                        data.skills.map((skill) => (
                            <span
                                key={skill.id}
                                className="inline-flex items-center gap-2 rounded-full bg-surface px-3 py-1 text-sm text-ink"
                            >
                                {skill.name}
                                <button
                                    type="button"
                                    className="text-danger"
                                    onClick={() => removeSkill(skill.id)}
                                    aria-label={`Remove ${skill.name}`}
                                >
                                    ×
                                </button>
                            </span>
                        ))
                    )}
                </div>
                <form onSubmit={addSkill} className="flex flex-col gap-3 sm:flex-row">
                    <Combobox
                        id="skill_name"
                        surface="public"
                        className="min-w-0 flex-1"
                        placeholder="Search or enter a skill"
                        emptyMessage="Press Add skill to save this name"
                        options={skillOptions}
                        value={skillName}
                        onChange={setSkillName}
                    />
                    <button
                        type="submit"
                        className="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white hover:opacity-95"
                    >
                        Add skill
                    </button>
                    {onCancel ? (
                        <button type="button" className="rounded-md border border-line px-4 py-2 text-sm font-semibold text-ink" onClick={onCancel}>
                            Cancel
                        </button>
                    ) : null}
                </form>
            </section>
            ) : null}

            {show('experience') ? (
            <section className="space-y-4 rounded-xl border border-line bg-white p-6">
                <h2 className="text-lg font-semibold text-ink">Employment history</h2>
                <EmploymentTimeline
                    histories={data.employment_histories}
                    onEdit={startEditHistory}
                    onRemove={removeHistory}
                />

                <form onSubmit={submitHistory} className="space-y-4 border-t border-line pt-4">
                    <h3 className="text-sm font-semibold text-ink">
                        {editingHistoryId ? 'Edit employment record' : 'Add employment record'}
                    </h3>
                    <FormField
                        id="history_company"
                        label="Company"
                        value={historyForm.data.company}
                        onChange={(event) => historyForm.setData('company', event.target.value)}
                    />
                    {historyForm.errors.company && <p className="-mt-2 text-sm text-danger">{historyForm.errors.company}</p>}
                    <FormField
                        id="history_designation"
                        label="Designation"
                        value={historyForm.data.designation}
                        onChange={(event) => historyForm.setData('designation', event.target.value)}
                    />
                    {historyForm.errors.designation && (
                        <p className="-mt-2 text-sm text-danger">{historyForm.errors.designation}</p>
                    )}
                    <div>
                        <label htmlFor="history_industry_id" className="mb-1 block text-sm font-medium text-ink">
                            Industry
                        </label>
                        <select
                            id="history_industry_id"
                            className={selectClass}
                            value={historyForm.data.industry_id}
                            onChange={(event) => historyForm.setData('industry_id', event.target.value)}
                        >
                            <option value="">Not selected</option>
                            {lookups.industries
                                .filter((industry) => industry.selectable !== false || String(industry.id) === historyForm.data.industry_id)
                                .map((industry) => (
                                    <option key={industry.id} value={industry.id}>
                                        {industry.name}
                                    </option>
                                ))}
                        </select>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <FormField
                            id="history_started_on"
                            label="Start date"
                            type="date"
                            value={historyForm.data.started_on}
                            onChange={(event) => historyForm.setData('started_on', event.target.value)}
                        />
                        <FormField
                            id="history_ended_on"
                            label="End date"
                            type="date"
                            value={historyForm.data.ended_on}
                            onChange={(event) => historyForm.setData('ended_on', event.target.value)}
                            disabled={historyForm.data.is_current}
                        />
                    </div>
                    {(historyForm.errors.started_on || historyForm.errors.ended_on) && (
                        <p className="-mt-2 text-sm text-danger">
                            {historyForm.errors.started_on || historyForm.errors.ended_on}
                        </p>
                    )}
                    <label className="flex items-center gap-2 text-sm text-ink">
                        <input
                            type="checkbox"
                            checked={historyForm.data.is_internship}
                            onChange={(event) => {
                                const checked = event.target.checked;
                                historyForm.setData('is_internship', checked);
                                if (checked) {
                                    historyForm.setData('is_current', false);
                                }
                            }}
                        />
                        This is an internship (separate from full-time employment)
                    </label>
                    <label className="flex items-center gap-2 text-sm text-ink">
                        <input
                            type="checkbox"
                            checked={historyForm.data.is_current}
                            disabled={historyForm.data.is_internship}
                            onChange={(event) => {
                                historyForm.setData('is_current', event.target.checked);
                                if (event.target.checked) {
                                    historyForm.setData('ended_on', '');
                                }
                            }}
                        />
                        This is my current employment
                    </label>
                    <FormField
                        id="history_description"
                        label="Role summary"
                        as="textarea"
                        maxLength={2000}
                        value={historyForm.data.description}
                        onChange={(event) => historyForm.setData('description', event.target.value)}
                    />
                    <div className="flex gap-3">
                        <button
                            type="submit"
                            disabled={historyForm.processing}
                            className="rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white hover:opacity-95 disabled:bg-disabled"
                        >
                            {editingHistoryId ? 'Update record' : 'Add record'}
                        </button>
                        {editingHistoryId ? (
                            <button type="button" className="text-sm font-medium text-muted" onClick={resetHistoryForm}>
                                Cancel
                            </button>
                        ) : null}
                    </div>
                </form>
                {onCancel ? (
                    <button type="button" className="text-sm font-medium text-muted" onClick={onCancel}>
                        Done
                    </button>
                ) : null}
            </section>
            ) : null}

            {show('certifications') ? (
                <CertificationSection certifications={data.certifications ?? []} onCancel={onCancel} />
            ) : null}
            {show('education') ? <EducationSection educations={data.educations ?? []} /> : null}
            {show('projects') ? <ProjectSection projects={data.projects ?? []} /> : null}
            {show('languages') ? (
            <LanguageSection
                languages={data.languages ?? []}
                proficiencies={lookups.language_proficiencies ?? []}
            />
            ) : null}
        </div>
    );
}

function CertificationSection({
    certifications,
    onCancel,
}: {
    certifications: AccountCertification[];
    onCancel?: () => void;
}) {
    const [editingId, setEditingId] = useState<number | null>(null);

    const form = useForm({
        name: '',
        issuer: '',
        issued_on: '',
        expires_on: '',
    });

    const resetForm = () => {
        setEditingId(null);
        form.reset();
        form.clearErrors();
    };

    const startEdit = (item: AccountCertification) => {
        setEditingId(item.id);
        form.setData({
            name: item.name,
            issuer: item.issuer ?? '',
            issued_on: item.issued_on ?? '',
            expires_on: item.expires_on ?? '',
        });
        form.clearErrors();
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const onSuccess = () => {
            resetForm();
            router.reload({ only: ['profile'] });
        };

        if (editingId) {
            form.put(`/account/profile/certifications/${editingId}`, {
                preserveScroll: true,
                onSuccess,
            });
            return;
        }

        form.post('/account/profile/certifications', {
            preserveScroll: true,
            onSuccess,
        });
    };

    const remove = (id: number) => {
        if (!window.confirm('Remove this certification?')) {
            return;
        }
        router.delete(`/account/profile/certifications/${id}`, {
            preserveScroll: true,
            onSuccess: () => router.reload({ only: ['profile'] }),
        });
    };

    return (
        <section className="space-y-4 rounded-xl border border-line bg-white p-6">
            <h2 className="text-lg font-semibold text-ink">Certifications</h2>
            <p className="text-sm text-muted">
                Save certification details with or without a linked document. These do not change your profile
                completion score.
            </p>
            {certifications.length === 0 ? (
                <p className="text-sm text-muted">No certifications added yet.</p>
            ) : (
                <ul className="space-y-2">
                    {certifications.map((item) => (
                        <li key={item.id} className="flex items-start justify-between gap-3 rounded-md bg-surface px-3 py-2 text-sm">
                            <div>
                                <p className="font-medium text-ink">{item.name}</p>
                                <p className="text-muted">
                                    {[item.issuer, item.issued_on, item.expires_on].filter(Boolean).join(' · ')}
                                </p>
                                {item.candidate_document_id ? (
                                    <p className="text-xs text-muted">Document linked</p>
                                ) : null}
                            </div>
                            <div className="flex shrink-0 gap-3">
                                <button type="button" className="font-medium text-brand" onClick={() => startEdit(item)}>
                                    Edit
                                </button>
                                <button type="button" className="font-medium text-danger" onClick={() => remove(item.id)}>
                                    Remove
                                </button>
                            </div>
                        </li>
                    ))}
                </ul>
            )}
            <form onSubmit={submit} className="space-y-3 border-t border-line pt-4">
                <h3 className="text-sm font-semibold text-ink">
                    {editingId ? 'Edit certification' : 'Add certification'}
                </h3>
                <FormField
                    id="certification_name"
                    label="Certification name"
                    value={form.data.name}
                    onChange={(event) => form.setData('name', event.target.value)}
                />
                {form.errors.name ? <p className="-mt-2 text-sm text-danger">{form.errors.name}</p> : null}
                <FormField
                    id="certification_issuer"
                    label="Issuer"
                    value={form.data.issuer}
                    onChange={(event) => form.setData('issuer', event.target.value)}
                />
                <div className="grid gap-4 sm:grid-cols-2">
                    <FormField
                        id="certification_issued_on"
                        label="Issued on"
                        type="date"
                        value={form.data.issued_on}
                        onChange={(event) => form.setData('issued_on', event.target.value)}
                    />
                    <FormField
                        id="certification_expires_on"
                        label="Expires on"
                        type="date"
                        value={form.data.expires_on}
                        onChange={(event) => form.setData('expires_on', event.target.value)}
                    />
                </div>
                {(form.errors.issued_on || form.errors.expires_on) && (
                    <p className="-mt-2 text-sm text-danger">{form.errors.issued_on || form.errors.expires_on}</p>
                )}
                <div className="flex flex-wrap gap-3">
                    <button
                        type="submit"
                        disabled={form.processing}
                        className="rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white hover:opacity-95 disabled:bg-disabled"
                    >
                        {editingId ? 'Save certification' : 'Add certification'}
                    </button>
                    {editingId ? (
                        <button type="button" className="text-sm font-medium text-muted" onClick={resetForm}>
                            Cancel edit
                        </button>
                    ) : null}
                </div>
            </form>
            {onCancel ? (
                <button type="button" className="text-sm font-medium text-muted" onClick={onCancel}>
                    Done
                </button>
            ) : null}
        </section>
    );
}

function EducationSection({ educations }: { educations: AccountEducation[] }) {
    const form = useForm({
        institution: '',
        qualification: '',
        field: '',
        started_on: '',
        ended_on: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/account/profile/educations', {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <section className="space-y-4 rounded-xl border border-line bg-white p-6">
            <h2 className="text-lg font-semibold text-ink">Education</h2>
            {educations.length === 0 ? (
                <p className="text-sm text-muted">No education records yet.</p>
            ) : (
                <ul className="space-y-2">
                    {educations.map((item) => (
                        <li key={item.id} className="flex items-start justify-between gap-3 rounded-md bg-surface px-3 py-2 text-sm">
                            <div>
                                <p className="font-medium text-ink">{item.institution}</p>
                                <p className="text-muted">
                                    {[item.qualification, item.field].filter(Boolean).join(' · ')}
                                </p>
                            </div>
                            <button
                                type="button"
                                className="text-sm font-medium text-danger"
                                onClick={() => router.delete(`/account/profile/educations/${item.id}`, { preserveScroll: true })}
                            >
                                Remove
                            </button>
                        </li>
                    ))}
                </ul>
            )}
            <form onSubmit={submit} className="space-y-3 border-t border-line pt-4">
                <FormField
                    id="education_institution"
                    label="Institution"
                    value={form.data.institution}
                    onChange={(event) => form.setData('institution', event.target.value)}
                />
                {form.errors.institution ? <p className="-mt-2 text-sm text-danger">{form.errors.institution}</p> : null}
                <FormField
                    id="education_qualification"
                    label="Qualification"
                    value={form.data.qualification}
                    onChange={(event) => form.setData('qualification', event.target.value)}
                />
                <FormField
                    id="education_field"
                    label="Field of study"
                    value={form.data.field}
                    onChange={(event) => form.setData('field', event.target.value)}
                />
                <div className="grid gap-4 sm:grid-cols-2">
                    <FormField
                        id="education_started_on"
                        label="Started"
                        type="date"
                        value={form.data.started_on}
                        onChange={(event) => form.setData('started_on', event.target.value)}
                    />
                    <FormField
                        id="education_ended_on"
                        label="Ended"
                        type="date"
                        value={form.data.ended_on}
                        onChange={(event) => form.setData('ended_on', event.target.value)}
                    />
                </div>
                <button
                    type="submit"
                    disabled={form.processing}
                    className="rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white hover:opacity-95 disabled:bg-disabled"
                >
                    Add education
                </button>
            </form>
        </section>
    );
}

function ProjectSection({ projects }: { projects: AccountProject[] }) {
    const form = useForm({
        name: '',
        role: '',
        company: '',
        description: '',
        started_on: '',
        ended_on: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/account/profile/projects', {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <section className="space-y-4 rounded-xl border border-line bg-white p-6">
            <h2 className="text-lg font-semibold text-ink">Projects</h2>
            {projects.length === 0 ? (
                <p className="text-sm text-muted">No projects added yet.</p>
            ) : (
                <ul className="space-y-2">
                    {projects.map((item) => (
                        <li key={item.id} className="flex items-start justify-between gap-3 rounded-md bg-surface px-3 py-2 text-sm">
                            <div>
                                <p className="font-medium text-ink">{item.name}</p>
                                <p className="text-muted">{[item.role, item.company].filter(Boolean).join(' · ')}</p>
                            </div>
                            <button
                                type="button"
                                className="text-sm font-medium text-danger"
                                onClick={() => router.delete(`/account/profile/projects/${item.id}`, { preserveScroll: true })}
                            >
                                Remove
                            </button>
                        </li>
                    ))}
                </ul>
            )}
            <form onSubmit={submit} className="space-y-3 border-t border-line pt-4">
                <FormField
                    id="project_name"
                    label="Project name"
                    value={form.data.name}
                    onChange={(event) => form.setData('name', event.target.value)}
                />
                {form.errors.name ? <p className="-mt-2 text-sm text-danger">{form.errors.name}</p> : null}
                <FormField
                    id="project_role"
                    label="Your role"
                    value={form.data.role}
                    onChange={(event) => form.setData('role', event.target.value)}
                />
                <FormField
                    id="project_company"
                    label="Company or client"
                    value={form.data.company}
                    onChange={(event) => form.setData('company', event.target.value)}
                />
                <FormField
                    id="project_description"
                    label="Description"
                    as="textarea"
                    maxLength={2000}
                    value={form.data.description}
                    onChange={(event) => form.setData('description', event.target.value)}
                />
                <button
                    type="submit"
                    disabled={form.processing}
                    className="rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white hover:opacity-95 disabled:bg-disabled"
                >
                    Add project
                </button>
            </form>
        </section>
    );
}

function LanguageSection({
    languages,
    proficiencies,
}: {
    languages: AccountProfessionalLanguage[];
    proficiencies: Array<{ value: string; label: string }>;
}) {
    const form = useForm({
        name: '',
        proficiency: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/account/profile/languages', {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <section className="space-y-4 rounded-xl border border-line bg-white p-6">
            <h2 className="text-lg font-semibold text-ink">Languages</h2>
            <p className="text-sm text-muted">Professional languages are separate from your account language setting.</p>
            {languages.length === 0 ? (
                <p className="text-sm text-muted">No languages added yet.</p>
            ) : (
                <ul className="space-y-2">
                    {languages.map((item) => (
                        <li key={item.id} className="flex items-start justify-between gap-3 rounded-md bg-surface px-3 py-2 text-sm">
                            <div>
                                <p className="font-medium text-ink">{item.name}</p>
                                {item.proficiency_label ? <p className="text-muted">{item.proficiency_label}</p> : null}
                            </div>
                            <button
                                type="button"
                                className="font-medium text-danger"
                                onClick={() => router.delete(`/account/profile/languages/${item.id}`, { preserveScroll: true })}
                            >
                                Remove
                            </button>
                        </li>
                    ))}
                </ul>
            )}
            <form onSubmit={submit} className="space-y-3 border-t border-line pt-4">
                <FormField
                    id="language_name"
                    label="Language"
                    value={form.data.name}
                    onChange={(event) => form.setData('name', event.target.value)}
                />
                {form.errors.name ? <p className="-mt-2 text-sm text-danger">{form.errors.name}</p> : null}
                <div>
                    <label htmlFor="language_proficiency" className="mb-1 block text-sm font-medium text-ink">
                        Proficiency
                    </label>
                    <select
                        id="language_proficiency"
                        className={selectClass}
                        value={form.data.proficiency}
                        onChange={(event) => form.setData('proficiency', event.target.value)}
                    >
                        <option value="">Not specified</option>
                        {proficiencies.map((item) => (
                            <option key={item.value} value={item.value}>
                                {item.label}
                            </option>
                        ))}
                    </select>
                </div>
                <button
                    type="submit"
                    disabled={form.processing}
                    className="rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white hover:opacity-95 disabled:bg-disabled"
                >
                    Add language
                </button>
            </form>
        </section>
    );
}
