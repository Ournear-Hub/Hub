import { Head, Link } from '@inertiajs/react';
import PageContainer from '@/Components/Public/PageContainer';
import AccountLayout from '@/Layouts/AccountLayout';
import type { AccountEmploymentHistory, AccountProfessionalProfile, ResumeBuilderPreview } from '@/types/account';

type BuilderProps = {
    preview: ResumeBuilderPreview;
};

export default function ResumeBuilder({ preview }: BuilderProps) {
    const professional = preview.professional as AccountProfessionalProfile;
    const skills = professional.skills ?? [];
    const histories = (professional.employment_histories ?? []) as AccountEmploymentHistory[];

    return (
        <AccountLayout title="Resume builder">
            
                    <div className="mb-6 flex flex-wrap gap-3 text-sm">
                        <Link href="/account/resumes" className="font-medium text-brand">
                            Back to resumes
                        </Link>
                        <Link href="/account/profile" className="font-medium text-brand">
                            Edit profile
                        </Link>
                    </div>
                    <article className="space-y-6 rounded-xl border border-line bg-white p-6">
                        <header>
                            <h2 className="text-2xl font-semibold text-ink">{preview.personal.name}</h2>
                            <p className="mt-1 text-sm text-muted">
                                {[preview.personal.email, preview.personal.mobile, preview.personal.location]
                                    .filter(Boolean)
                                    .join(' · ')}
                            </p>
                            {preview.personal.bio ? <p className="mt-3 text-sm text-ink">{preview.personal.bio}</p> : null}
                        </header>
                        <section>
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Professional summary</h3>
                            <p className="mt-2 text-sm text-ink">
                                {[professional.current_designation, professional.current_company, professional.industry]
                                    .filter(Boolean)
                                    .join(' · ') || 'Add your current role on the professional profile.'}
                            </p>
                            <p className="mt-1 text-sm text-muted">
                                {[professional.experience, professional.qualification, professional.functional_area]
                                    .filter(Boolean)
                                    .join(' · ')}
                            </p>
                        </section>
                        <section>
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Skills</h3>
                            {skills.length === 0 ? (
                                <p className="mt-2 text-sm text-muted">No skills added yet.</p>
                            ) : (
                                <p className="mt-2 text-sm text-ink">{skills.map((skill) => skill.name).join(', ')}</p>
                            )}
                        </section>
                        <section>
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Employment history</h3>
                            {histories.length === 0 ? (
                                <p className="mt-2 text-sm text-muted">No employment history added yet.</p>
                            ) : (
                                <ul className="mt-2 space-y-2 text-sm text-ink">
                                    {histories.map((history) => (
                                        <li key={history.id}>
                                            <strong>
                                                {history.designation} · {history.company}
                                            </strong>
                                            <span className="block text-muted">
                                                {history.started_on} – {history.is_current ? 'Present' : history.ended_on}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>
                        <section>
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Preferences</h3>
                            <p className="mt-2 text-sm text-ink">
                                {(professional.preferred_locations ?? []).map((item) => item.name).join(', ') ||
                                    'No preferred locations'}
                            </p>
                            <p className="mt-1 text-sm text-muted">
                                {(professional.preferred_employment_types ?? []).map((item) => item.name).join(', ')}
                            </p>
                        </section>
                    </article>
                </AccountLayout>
    );
}
