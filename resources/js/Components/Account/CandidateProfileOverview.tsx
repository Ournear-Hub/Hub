import { Link } from '@inertiajs/react';
import DetailSection from '@/Components/organisms/DetailSection';
import type { AccountProfile } from '@/types/account';

type SectionKey =
    | 'about'
    | 'basic'
    | 'professional'
    | 'experience'
    | 'education'
    | 'skills'
    | 'projects'
    | 'certifications'
    | 'languages'
    | 'preferences';

type Props = {
    profile: AccountProfile;
    onEdit: (section: SectionKey) => void;
};

function Section({
    title,
    children,
    onEdit,
    addLabel,
    onAdd,
}: {
    title: string;
    children: React.ReactNode;
    onEdit?: () => void;
    addLabel?: string;
    onAdd?: () => void;
}) {
    return (
        <section className="rounded-xl border border-line bg-white p-5">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <h2 className="font-semibold text-ink">{title}</h2>
                <div className="flex gap-3">
                    {onAdd ? (
                        <button type="button" className="text-sm font-medium text-brand" onClick={onAdd}>
                            {addLabel ?? 'Add'}
                        </button>
                    ) : null}
                    {onEdit ? (
                        <button type="button" className="text-sm font-medium text-brand" onClick={onEdit}>
                            Edit
                        </button>
                    ) : null}
                </div>
            </div>
            <div className="mt-3 text-sm text-ink">{children}</div>
        </section>
    );
}

export default function CandidateProfileOverview({ profile, onEdit }: Props) {
    const professional = profile.professional;
    const hireId = profile.ournear_id || profile.modules?.candidate || profile.business_id;
    const publicUrl = profile.public_profile_url;

    return (
        <div className="mt-6 space-y-4">
            <DetailSection title="About" onEdit={() => onEdit('about')}>
                <p className="whitespace-pre-line text-muted">{profile.bio || 'Add a short professional summary.'}</p>
            </DetailSection>

            <Section title="Professional information" onEdit={() => onEdit('professional')}>
                <dl className="grid gap-3 sm:grid-cols-2">
                    <div>
                        <dt className="text-muted">Designation</dt>
                        <dd>{professional?.current_designation || '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-muted">Company</dt>
                        <dd>
                            {professional?.current_company ? (
                                <span>{professional.current_company}</span>
                            ) : (
                                '—'
                            )}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted">Experience</dt>
                        <dd>{professional?.experience || profile.experience || '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-muted">Industry</dt>
                        <dd>{professional?.industry || '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-muted">Hire ID</dt>
                        <dd>{hireId || '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-muted">Location</dt>
                        <dd>{profile.location || '—'}</dd>
                    </div>
                </dl>
            </Section>

            <Section title="Work experience" onEdit={() => onEdit('experience')} addLabel="Add Experience" onAdd={() => onEdit('experience')}>
                {(professional?.employment_histories ?? []).length === 0 ? (
                    <p className="text-muted">No work experience added yet.</p>
                ) : (
                    <ul className="space-y-3">
                        {professional?.employment_histories.map((item) => (
                            <li key={item.id}>
                                <p className="font-medium">{item.designation}</p>
                                <p className="text-muted">{item.company}</p>
                                <p className="text-muted">
                                    {[item.started_on, item.is_current ? 'Present' : item.ended_on].filter(Boolean).join(' – ')}
                                </p>
                            </li>
                        ))}
                    </ul>
                )}
            </Section>

            <Section title="Education" onEdit={() => onEdit('education')} addLabel="Add Education" onAdd={() => onEdit('education')}>
                {(professional?.educations ?? []).length === 0 ? (
                    <p className="text-muted">{professional?.qualification || 'No education added yet.'}</p>
                ) : (
                    <ul className="space-y-3">
                        {professional?.educations?.map((item) => (
                            <li key={item.id}>
                                <p className="font-medium">{item.institution}</p>
                                <p className="text-muted">{[item.qualification, item.field].filter(Boolean).join(' · ')}</p>
                            </li>
                        ))}
                    </ul>
                )}
            </Section>

            <Section title="Skills" onEdit={() => onEdit('skills')} addLabel="Add Skill" onAdd={() => onEdit('skills')}>
                {(professional?.skills ?? []).length === 0 ? (
                    <p className="text-muted">No skills added yet.</p>
                ) : (
                    <ul className="flex flex-wrap gap-2">
                        {professional?.skills.map((skill) => (
                            <li key={skill.id} className="rounded-full bg-surface px-3 py-1">
                                {skill.name}
                            </li>
                        ))}
                    </ul>
                )}
            </Section>

            <Section title="Projects" onEdit={() => onEdit('projects')} addLabel="Add Project" onAdd={() => onEdit('projects')}>
                {(professional?.projects ?? []).length === 0 ? (
                    <p className="text-muted">No projects added yet.</p>
                ) : (
                    <ul className="space-y-3">
                        {professional?.projects?.map((item) => (
                            <li key={item.id}>
                                <p className="font-medium">{item.name}</p>
                                <p className="text-muted">{[item.role, item.company].filter(Boolean).join(' · ')}</p>
                            </li>
                        ))}
                    </ul>
                )}
            </Section>

            <Section title="Certifications & Training" onEdit={() => onEdit('certifications')} addLabel="Add Certification" onAdd={() => onEdit('certifications')}>
                {(professional?.certifications ?? []).length === 0 ? (
                    <p className="text-muted">No certifications added yet.</p>
                ) : (
                    <ul className="space-y-3">
                        {professional?.certifications?.map((item) => (
                            <li key={item.id}>
                                <p className="font-medium">{item.name}</p>
                                <p className="text-muted">{item.issuer || '—'}</p>
                            </li>
                        ))}
                    </ul>
                )}
            </Section>

            <Section title="Languages" onEdit={() => onEdit('languages')} addLabel="Add Language" onAdd={() => onEdit('languages')}>
                {(professional?.languages ?? []).length === 0 ? (
                    <p className="text-muted">No languages added yet.</p>
                ) : (
                    <ul className="space-y-2">
                        {professional?.languages?.map((item) => (
                            <li key={item.id}>
                                {item.name}
                                {item.proficiency_label ? ` · ${item.proficiency_label}` : ''}
                            </li>
                        ))}
                    </ul>
                )}
            </Section>

            <Section title="Job preferences" onEdit={() => onEdit('preferences')}>
                <p className="text-muted">
                    {(professional?.preferred_locations ?? []).map((item) => item.name).join(', ') || 'Preferred locations not added.'}
                </p>
                <p className="mt-2 text-muted">
                    {(professional?.preferred_employment_types ?? []).map((item) => item.name).join(', ') || 'Job types not added.'}
                </p>
            </Section>

            <section className="grid gap-4 md:grid-cols-2">
                <article className="rounded-xl border border-line bg-white p-5">
                    <h2 className="font-semibold text-ink">Hire Resume</h2>
                    <p className="mt-2 text-sm text-muted">Hire Resume {profile.profile_completion}% complete</p>
                    <Link href="/account/resumes/builder" className="mt-4 inline-block text-sm font-semibold text-brand">
                        Complete Hire Resume
                    </Link>
                </article>
                <article className="rounded-xl border border-line bg-white p-5">
                    <h2 className="font-semibold text-ink">My Resume</h2>
                    <p className="mt-2 text-sm text-muted">
                        {profile.library_resume?.has_upload
                            ? profile.library_resume.title ?? 'Uploaded personal resume on file.'
                            : 'Upload a resume for job applications.'}
                    </p>
                    <Link href="/account/resumes" className="mt-4 inline-block text-sm font-semibold text-brand">
                        Open My Resume
                    </Link>
                </article>
                <article className="rounded-xl border border-line bg-white p-5">
                    <h2 className="font-semibold text-ink">Verification</h2>
                    <ul className="mt-2 space-y-1 text-sm text-muted">
                        <li>Email — {profile.email_verified ? 'Verified' : 'Required'}</li>
                        <li>
                            Mobile —{' '}
                            {profile.mobile_verified
                                ? 'Verified'
                                : profile.verification_context?.mobile_required
                                  ? 'Required for hiring'
                                  : 'Optional for job search'}
                        </li>
                        <li>WhatsApp — {profile.whatsapp_number ? 'Saved' : 'Optional'} · verification not required</li>
                    </ul>
                    <Link href="/account/settings/verification" className="mt-4 inline-block text-sm font-semibold text-brand">
                        Open verification
                    </Link>
                </article>
                <article className="rounded-xl border border-line bg-white p-5">
                    <h2 className="font-semibold text-ink">Documents</h2>
                    <p className="mt-2 text-sm text-muted">Private documents stay in your account.</p>
                    {publicUrl ? (
                        <Link href={publicUrl} className="mt-4 block text-sm font-semibold text-brand">
                            View public profile
                        </Link>
                    ) : null}
                    <Link href="/account/documents" className="mt-2 inline-block text-sm font-semibold text-brand">
                        Open documents
                    </Link>
                </article>
            </section>
        </div>
    );
}
