import { router, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import FormField from '@/Components/Public/FormField';
import type {
    AccountEmploymentHistory,
    AccountProfileLookups,
    AccountProfessionalProfile,
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
};

type Props = {
    professional?: AccountProfessionalProfile | null;
    lookups: AccountProfileLookups;
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

export default function CandidateProfessionalSections({ professional, lookups }: Props) {
    const data = professional ?? emptyProfessional;
    const [skillName, setSkillName] = useState('');
    const [editingHistoryId, setEditingHistoryId] = useState<number | null>(null);

    const professionalForm = useForm({
        current_designation: data.current_designation ?? '',
        current_company: data.current_company ?? '',
        industry_id: data.industry_id ? String(data.industry_id) : '',
        job_category_id: data.job_category_id ? String(data.job_category_id) : '',
        experience: data.experience ?? '',
        experience_range_id: data.experience_range_id ? String(data.experience_range_id) : '',
        qualification: data.qualification ?? '',
        qualification_id: data.qualification_id ? String(data.qualification_id) : '',
        current_salary: salaryValue(data.current_salary),
        expected_salary: salaryValue(data.expected_salary),
        notice_period: data.notice_period ?? '',
        employment_preference: data.employment_preference ?? '',
        preferred_location_ids: data.preferred_location_ids,
        preferred_employment_type_ids: data.preferred_employment_type_ids,
    });

    const historyForm = useForm({
        company: '',
        designation: '',
        industry_id: '',
        started_on: '',
        ended_on: '',
        is_current: false,
        description: '',
    });

    const categories = lookups.categories.filter(
        (category) =>
            !professionalForm.data.industry_id ||
            String(category.industry_id ?? '') === professionalForm.data.industry_id ||
            String(category.id) === professionalForm.data.job_category_id,
    );

    const submitProfessional = (event: FormEvent) => {
        event.preventDefault();
        professionalForm.put('/account/profile/professional');
    };

    const addSkill = (event: FormEvent) => {
        event.preventDefault();
        router.post(
            '/account/profile/skills',
            { name: skillName },
            {
                preserveScroll: true,
                onSuccess: () => setSkillName(''),
            },
        );
    };

    const removeSkill = (id: number) => {
        router.delete(`/account/profile/skills/${id}`, { preserveScroll: true });
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
                onSuccess: () => resetHistoryForm(),
            });
            return;
        }

        historyForm.post('/account/profile/employment-history', {
            preserveScroll: true,
            onSuccess: () => resetHistoryForm(),
        });
    };

    const removeHistory = (id: number) => {
        if (!window.confirm('Remove this employment history record?')) {
            return;
        }
        router.delete(`/account/profile/employment-history/${id}`, { preserveScroll: true });
    };

    return (
        <div className="space-y-8">
            <form onSubmit={submitProfessional} className="space-y-8 rounded-xl border border-line bg-white p-6">
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
                            onChange={(event) => {
                                professionalForm.setData('industry_id', event.target.value);
                                professionalForm.setData('job_category_id', '');
                            }}
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
                        <label htmlFor="job_category_id" className="mb-1 block text-sm font-medium text-ink">
                            Functional area
                        </label>
                        <select
                            id="job_category_id"
                            className={selectClass}
                            value={professionalForm.data.job_category_id}
                            onChange={(event) => professionalForm.setData('job_category_id', event.target.value)}
                        >
                            <option value="">Not selected</option>
                            {categories
                                .filter(
                                    (category) =>
                                        category.selectable !== false ||
                                        String(category.id) === professionalForm.data.job_category_id,
                                )
                                .map((category) => (
                                    <option key={category.id} value={category.id}>
                                        {category.name}
                                    </option>
                                ))}
                        </select>
                        {professionalForm.errors.job_category_id && (
                            <p className="mt-1 text-sm text-danger">{professionalForm.errors.job_category_id}</p>
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
                    <FormField
                        id="experience"
                        label="Experience notes"
                        value={professionalForm.data.experience}
                        onChange={(event) => professionalForm.setData('experience', event.target.value)}
                    />
                    {professionalForm.errors.experience && (
                        <p className="-mt-2 text-sm text-danger">{professionalForm.errors.experience}</p>
                    )}
                    <div>
                        <label htmlFor="qualification_id" className="mb-1 block text-sm font-medium text-ink">
                            Qualification
                        </label>
                        <select
                            id="qualification_id"
                            className={selectClass}
                            value={professionalForm.data.qualification_id}
                            onChange={(event) => professionalForm.setData('qualification_id', event.target.value)}
                        >
                            <option value="">Not classified</option>
                            {lookups.qualifications
                                .filter(
                                    (item) =>
                                        item.selectable !== false ||
                                        String(item.id) === professionalForm.data.qualification_id,
                                )
                                .map((item) => (
                                    <option key={item.id} value={item.id}>
                                        {item.name}
                                    </option>
                                ))}
                        </select>
                        {professionalForm.errors.qualification_id && (
                            <p className="mt-1 text-sm text-danger">{professionalForm.errors.qualification_id}</p>
                        )}
                    </div>
                    <FormField
                        id="qualification"
                        label="Qualification notes"
                        value={professionalForm.data.qualification}
                        onChange={(event) => professionalForm.setData('qualification', event.target.value)}
                    />
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
                </section>

                <button
                    type="submit"
                    disabled={professionalForm.processing}
                    className="rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white hover:opacity-95 disabled:bg-disabled"
                >
                    Save professional profile
                </button>
            </form>

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
                    <input
                        id="skill_name"
                        name="name"
                        value={skillName}
                        onChange={(event) => setSkillName(event.target.value)}
                        placeholder="Add a skill"
                        className={selectClass}
                        maxLength={64}
                    />
                    <button
                        type="submit"
                        className="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white hover:opacity-95"
                    >
                        Add skill
                    </button>
                </form>
            </section>

            <section className="space-y-4 rounded-xl border border-line bg-white p-6">
                <h2 className="text-lg font-semibold text-ink">Employment history</h2>
                {data.employment_histories.length === 0 ? (
                    <p className="text-sm text-muted">No employment history added yet.</p>
                ) : (
                    <ul className="space-y-3">
                        {data.employment_histories.map((history) => (
                            <li key={history.id} className="rounded-md bg-surface px-3 py-3 text-sm">
                                <p className="font-medium text-ink">
                                    {history.designation} · {history.company}
                                    {history.is_current ? ' · Current' : ''}
                                </p>
                                <p className="text-muted">
                                    {[history.started_on, history.is_current ? 'Present' : history.ended_on]
                                        .filter(Boolean)
                                        .join(' – ')}
                                    {history.industry ? ` · ${history.industry}` : ''}
                                </p>
                                {history.description ? <p className="mt-1 text-ink">{history.description}</p> : null}
                                <div className="mt-2 flex gap-3">
                                    <button
                                        type="button"
                                        className="font-medium text-brand"
                                        onClick={() => startEditHistory(history)}
                                    >
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        className="font-medium text-danger"
                                        onClick={() => removeHistory(history.id)}
                                    >
                                        Remove
                                    </button>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}

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
                            checked={historyForm.data.is_current}
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
            </section>
        </div>
    );
}
