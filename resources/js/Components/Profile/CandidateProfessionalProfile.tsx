import type { ReactNode } from 'react';
import EmploymentTimeline from '@/Components/Account/EmploymentTimeline';
import type { ProfileModuleId } from '@/Components/Profile/profileCompletionModules';
import type { AccountProfile, AccountProfileLookups, AccountProfessionalProfile } from '@/types/account';

export type ProfessionalEditTarget = Extract<
    ProfileModuleId,
    'about' | 'professional' | 'experience' | 'education' | 'skills' | 'projects' | 'certifications'
>;

type Props = {
    profile: AccountProfile;
    lookups: AccountProfileLookups;
    onEditSection: (section: ProfessionalEditTarget) => void;
    viewOnly?: boolean;
};

function SectionCard({
    title,
    description,
    onEdit,
    onAdd,
    addLabel,
    children,
}: {
    title: string;
    description?: string;
    onEdit?: () => void;
    onAdd?: () => void;
    addLabel?: string;
    children: ReactNode;
}) {
    return (
        <section className="rounded-xl border border-line bg-white p-5 sm:p-6">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 className="text-base font-semibold text-ink">{title}</h2>
                    {description ? <p className="mt-1 text-sm text-muted">{description}</p> : null}
                </div>
                <div className="flex flex-wrap gap-3">
                    {onAdd ? (
                        <button type="button" className="text-sm font-semibold text-brand hover:underline" onClick={onAdd}>
                            {addLabel ?? 'Add'}
                        </button>
                    ) : null}
                    {onEdit ? (
                        <button type="button" className="text-sm font-semibold text-brand hover:underline" onClick={onEdit}>
                            Edit
                        </button>
                    ) : null}
                </div>
            </div>
            <div className="mt-4">{children}</div>
        </section>
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

function emptyValue(value: ReactNode): ReactNode {
    if (value === null || value === undefined || value === '') {
        return <span className="text-muted">—</span>;
    }
    return value;
}

function formatPrivateSalary(value: string | number | null | undefined): string | null {
    if (value === null || value === undefined || value === '') {
        return null;
    }
    return String(value);
}

export default function CandidateProfessionalProfile({ profile, lookups, onEditSection, viewOnly = false }: Props) {
    const edit = (section: ProfessionalEditTarget) => (viewOnly ? undefined : () => onEditSection(section));
    const pro: AccountProfessionalProfile = profile.professional ?? {
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

    const qualificationLabel =
        pro.qualification ||
        lookups.qualifications.find((item) => item.id === pro.qualification_id)?.name ||
        null;

    const experienceRangeLabel =
        lookups.experience_ranges.find((item) => item.id === pro.experience_range_id)?.name ?? null;

    const noticeLabel =
        pro.notice_period_label ??
        lookups.notice_periods.find((item) => item.value === pro.notice_period)?.label ??
        pro.notice_period;

    return (
        <div className="space-y-4">
            <SectionCard
                title="Professional summary"
                description="Headline and bio shown on your public profile when sharing is enabled."
                onEdit={edit('about')}
            >
                <dl className="space-y-3">
                    <FieldRow label="Headline" value={emptyValue(profile.professional_headline || profile.headline)} />
                    <FieldRow
                        label="Bio"
                        value={
                            profile.bio ? (
                                <span className="whitespace-pre-wrap">{profile.bio}</span>
                            ) : (
                                emptyValue(null)
                            )
                        }
                    />
                </dl>
            </SectionCard>

            <SectionCard
                title="Current professional details"
                description="Role, industry, and experience level. Salary fields stay private."
                onEdit={edit('professional')}
            >
                <dl className="space-y-3">
                    <FieldRow label="Current designation" value={emptyValue(pro.current_designation)} />
                    <FieldRow label="Current company" value={emptyValue(pro.current_company)} />
                    <FieldRow label="Industry" value={emptyValue(pro.industry)} />
                    <FieldRow label="Total experience" value={emptyValue(experienceRangeLabel)} />
                    <FieldRow label="Qualification" value={emptyValue(qualificationLabel)} />
                    <FieldRow label="Current salary (private)" value={emptyValue(formatPrivateSalary(pro.current_salary))} />
                    <FieldRow label="Expected salary (private)" value={emptyValue(formatPrivateSalary(pro.expected_salary))} />
                    <FieldRow label="Notice period" value={emptyValue(noticeLabel)} />
                </dl>
            </SectionCard>

            <SectionCard
                title="Employment history"
                description="Work history in chronological order."
                onEdit={edit('experience')}
                onAdd={edit('experience')}
                addLabel="Add employment"
            >
                <EmploymentTimeline histories={pro.employment_histories} />
            </SectionCard>

            <SectionCard
                title="Education and qualifications"
                onEdit={edit('education')}
                onAdd={edit('education')}
                addLabel="Add education"
            >
                {pro.educations && pro.educations.length > 0 ? (
                    <ul className="space-y-3">
                        {pro.educations.map((item) => (
                            <li key={item.id} className="border-b border-line pb-3 last:border-0 last:pb-0">
                                <p className="text-sm font-semibold text-ink">{item.institution}</p>
                                <p className="text-sm text-muted">
                                    {[item.qualification, item.field].filter(Boolean).join(' · ') || 'Details not specified'}
                                </p>
                                {(item.started_on || item.ended_on) && (
                                    <p className="text-sm text-muted">
                                        {[item.started_on, item.ended_on].filter(Boolean).join(' – ')}
                                    </p>
                                )}
                            </li>
                        ))}
                    </ul>
                ) : (
                    <p className="text-sm text-muted">No education records yet.</p>
                )}
            </SectionCard>

            <SectionCard title="Skills" onEdit={edit('skills')}>
                {pro.skills.length > 0 ? (
                    <ul className="flex flex-wrap gap-2">
                        {pro.skills.map((skill) => (
                            <li
                                key={skill.id}
                                className="rounded-full bg-surface px-3 py-1 text-sm text-ink"
                            >
                                {skill.name}
                            </li>
                        ))}
                    </ul>
                ) : (
                    <p className="text-sm text-muted">No skills added yet.</p>
                )}
            </SectionCard>

            <SectionCard
                title="Projects"
                onEdit={edit('projects')}
                onAdd={edit('projects')}
                addLabel="Add project"
            >
                {pro.projects && pro.projects.length > 0 ? (
                    <ul className="space-y-3">
                        {pro.projects.map((item) => (
                            <li key={item.id} className="border-b border-line pb-3 last:border-0 last:pb-0">
                                <p className="text-sm font-semibold text-ink">{item.name}</p>
                                <p className="text-sm text-muted">
                                    {[item.role, item.company].filter(Boolean).join(' · ') || 'Details not specified'}
                                </p>
                                {item.description ? (
                                    <p className="mt-1 whitespace-pre-wrap text-sm text-ink">{item.description}</p>
                                ) : null}
                                {(item.started_on || item.ended_on) && (
                                    <p className="text-sm text-muted">
                                        {[item.started_on, item.ended_on].filter(Boolean).join(' – ')}
                                    </p>
                                )}
                            </li>
                        ))}
                    </ul>
                ) : (
                    <p className="text-sm text-muted">No projects added yet.</p>
                )}
            </SectionCard>

            <SectionCard
                title="Certifications"
                description="Optional document attachments are supported when you link a stored document."
                onEdit={edit('certifications')}
                onAdd={edit('certifications')}
                addLabel="Add certification"
            >
                {pro.certifications && pro.certifications.length > 0 ? (
                    <ul className="space-y-3">
                        {pro.certifications.map((item) => (
                            <li key={item.id} className="border-b border-line pb-3 last:border-0 last:pb-0">
                                <p className="text-sm font-semibold text-ink">{item.name}</p>
                                <p className="text-sm text-muted">
                                    {[item.issuer, item.issued_on, item.expires_on].filter(Boolean).join(' · ') ||
                                        'Details not specified'}
                                </p>
                                {item.candidate_document_id ? (
                                    <p className="text-xs text-muted">Document linked</p>
                                ) : null}
                            </li>
                        ))}
                    </ul>
                ) : (
                    <p className="text-sm text-muted">No certifications added yet.</p>
                )}
            </SectionCard>
        </div>
    );
}
