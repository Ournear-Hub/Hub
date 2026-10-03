import type { ReactNode } from 'react';
import BrandMark from '@/Components/BrandMark';
import type {
    AccountCertification,
    AccountEducation,
    AccountEmploymentHistory,
    AccountProfessionalLanguage,
    AccountProfessionalProfile,
    AccountProject,
    ResumeBuilderPreview,
} from '@/types/account';

function recorded(value?: string | null): string | null {
    const trimmed = value?.trim() ?? '';

    return trimmed === '' ? null : trimmed;
}

function joinRecorded(values: Array<string | null | undefined>, separator = ' · '): string | null {
    const parts = values.map((value) => recorded(value)).filter((value): value is string => value !== null);

    return parts.length > 0 ? parts.join(separator) : null;
}

function initials(name: string): string {
    return (
        name
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map((part) => part.charAt(0).toUpperCase())
            .join('') || '—'
    );
}

function ResumeSection({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="ourneer-resume-section mt-6 first:mt-0">
            <h3 className="ourneer-resume-section-title border-b border-brand/25 pb-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-brand">
                {title}
            </h3>
            <div className="mt-2.5 text-[13px] leading-relaxed text-brand">{children}</div>
        </section>
    );
}

function renderHistoryList(histories: AccountEmploymentHistory[]) {
    return (
        <ul className="space-y-3.5">
            {histories.map((history) => {
                const dates = joinRecorded([history.started_on, history.is_current ? 'Present' : history.ended_on], ' – ');

                return (
                    <li key={history.id} className="ourneer-resume-block break-inside-avoid">
                        <p className="font-semibold text-ink">{joinRecorded([history.designation, history.company]) ?? 'Role'}</p>
                        {joinRecorded([history.industry, dates]) ? (
                            <p className="mt-0.5 text-[12px] text-muted">{joinRecorded([history.industry, dates])}</p>
                        ) : null}
                        {recorded(history.description) ? (
                            <p className="mt-1.5 whitespace-pre-line text-brand/90">{history.description}</p>
                        ) : null}
                    </li>
                );
            })}
        </ul>
    );
}

export default function OurNearResume({ preview }: { preview: ResumeBuilderPreview }) {
    const personal = preview.personal;
    const professional = preview.professional as AccountProfessionalProfile;
    const name = recorded(personal.name);
    const designation = recorded(personal.designation) ?? recorded(professional.current_designation);
    const contact = joinRecorded([personal.email, personal.mobile, personal.location]);
    const bio = recorded(personal.bio);
    const photoUrl = recorded(personal.photo_url ?? null);
    const skills = (professional.skills ?? []).map((skill) => recorded(skill.name)).filter((skillName): skillName is string => skillName !== null);
    const histories = (professional.employment_histories ?? []) as AccountEmploymentHistory[];
    const professionalHistories = histories.filter((history) => !history.is_internship);
    const internshipHistories = histories.filter((history) => history.is_internship);
    const educations = (professional.educations ?? []) as AccountEducation[];
    const projects = (professional.projects ?? []) as AccountProject[];
    const certifications = (professional.certifications ?? []) as AccountCertification[];
    const languages = (professional.languages ?? []) as AccountProfessionalLanguage[];
    const qualification = recorded(professional.qualification);
    const additional = [
        recorded(professional.notice_period_label) ? `Notice period: ${professional.notice_period_label}` : null,
        recorded(professional.relocation_summary),
        recorded(professional.availability) ? `Availability: ${professional.availability}` : null,
        recorded(professional.employment_preference) ? `Employment preference: ${professional.employment_preference}` : null,
        (professional.preferred_employment_types ?? []).length > 0
            ? `Preferred employment: ${(professional.preferred_employment_types ?? []).map((item) => item.name).join(', ')}`
            : null,
        (professional.preferred_locations ?? []).length > 0 && !recorded(professional.relocation_summary)
            ? `Preferred locations: ${(professional.preferred_locations ?? []).map((item) => item.name).join(', ')}`
            : null,
    ].filter((value): value is string => value !== null);

    const identityFields = [
        ['Designation', professional.current_designation],
        ['Company', professional.current_company],
        ['Industry', professional.industry],
        ['Functional area', professional.functional_area],
        ['Experience', professional.experience],
        ['Location', personal.location],
    ].filter(([, value]) => recorded(value));

    const displayName = name ?? 'Candidate';

    return (
        <article className="ourneer-resume-sheet overflow-x-hidden rounded-xl border border-line bg-white text-brand shadow-sm print:overflow-visible print:border-0 print:shadow-none">
            <header className="ourneer-resume-header break-inside-avoid border-b-2 border-brand/15 px-6 py-5 sm:px-8 print:px-0 print:py-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="flex min-w-0 gap-4">
                        {photoUrl ? (
                            <img
                                src={photoUrl}
                                alt=""
                                className="h-[4.5rem] w-[4.5rem] shrink-0 rounded-full border border-line object-cover print:h-16 print:w-16"
                            />
                        ) : (
                            <div
                                aria-hidden
                                className="flex h-[4.5rem] w-[4.5rem] shrink-0 items-center justify-center rounded-full border border-line bg-surface text-lg font-semibold text-brand print:h-16 print:w-16"
                            >
                                {initials(displayName)}
                            </div>
                        )}
                        <div className="min-w-0">
                            <h1 className="text-xl font-semibold tracking-tight text-ink sm:text-2xl">{displayName}</h1>
                            {designation ? <p className="mt-1 text-sm font-medium text-brand">{designation}</p> : null}
                            {contact ? <p className="mt-2 text-[13px] leading-snug text-muted">{contact}</p> : null}
                        </div>
                    </div>
                    <div className="flex shrink-0 flex-col items-start gap-1 sm:items-end sm:text-right">
                        <BrandMark usePublicBranding className="text-brand" />
                        <p className="text-[10px] font-semibold uppercase tracking-[0.2em] text-muted">OurNear Hire</p>
                        {recorded(personal.ournear_id) ? (
                            <p className="text-[11px] font-medium text-brand/80">OurNear ID {personal.ournear_id}</p>
                        ) : null}
                    </div>
                </div>
            </header>

            <div className="px-6 py-6 sm:px-8 sm:py-7 print:px-0 print:py-4">
                {bio ? (
                    <ResumeSection title="Professional summary">
                        <p className="text-brand/95">{bio}</p>
                    </ResumeSection>
                ) : null}

                {identityFields.length > 0 ? (
                    <ResumeSection title="Professional identity">
                        <dl className="grid gap-2.5 sm:grid-cols-2">
                            {identityFields.map(([label, value]) => (
                                <div key={label} className="ourneer-resume-block break-inside-avoid">
                                    <dt className="text-[10px] font-semibold uppercase tracking-wide text-accent">{label}</dt>
                                    <dd className="mt-0.5 text-brand">{recorded(value)}</dd>
                                </div>
                            ))}
                        </dl>
                    </ResumeSection>
                ) : null}

                {professionalHistories.length > 0 ? (
                    <ResumeSection title="Professional experience">{renderHistoryList(professionalHistories)}</ResumeSection>
                ) : null}

                {internshipHistories.length > 0 ? (
                    <ResumeSection title="Internship">{renderHistoryList(internshipHistories)}</ResumeSection>
                ) : null}

                {educations.length > 0 ? (
                    <ResumeSection title="Education">
                        <ul className="space-y-2.5">
                            {educations.map((education) => (
                                <li key={education.id} className="ourneer-resume-block break-inside-avoid">
                                    <p className="font-semibold text-ink">{education.institution}</p>
                                    <p className="mt-0.5 text-[12px] text-muted">
                                        {joinRecorded([
                                            education.qualification,
                                            education.field,
                                            joinRecorded([education.started_on, education.ended_on], ' – '),
                                        ])}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    </ResumeSection>
                ) : qualification ? (
                    <ResumeSection title="Education / qualification">
                        <p>{qualification}</p>
                    </ResumeSection>
                ) : null}

                {projects.length > 0 ? (
                    <ResumeSection title="Projects">
                        <ul className="space-y-3">
                            {projects.map((project) => (
                                <li key={project.id} className="ourneer-resume-block break-inside-avoid">
                                    <p className="font-semibold text-ink">{project.name}</p>
                                    {joinRecorded([project.role, project.company]) ? (
                                        <p className="mt-0.5 text-[12px] text-muted">{joinRecorded([project.role, project.company])}</p>
                                    ) : null}
                                    {recorded(project.description) ? (
                                        <p className="mt-1.5 whitespace-pre-line text-brand/90">{project.description}</p>
                                    ) : null}
                                </li>
                            ))}
                        </ul>
                    </ResumeSection>
                ) : null}

                {skills.length > 0 ? (
                    <ResumeSection title="Skills">
                        <ul className="flex flex-wrap gap-1.5">
                            {skills.map((skill) => (
                                <li
                                    key={skill}
                                    className="rounded-md border border-line bg-surface px-2 py-0.5 text-[12px] font-medium text-brand print:border-brand/20"
                                >
                                    {skill}
                                </li>
                            ))}
                        </ul>
                    </ResumeSection>
                ) : null}

                {certifications.length > 0 ? (
                    <ResumeSection title="Certifications">
                        <ul className="space-y-2.5">
                            {certifications.map((certification) => (
                                <li key={certification.id} className="ourneer-resume-block break-inside-avoid">
                                    <p className="font-semibold text-ink">{certification.name}</p>
                                    {joinRecorded([
                                        certification.issuer,
                                        certification.issued_on,
                                        certification.expires_on ? `Expires ${certification.expires_on}` : null,
                                    ]) ? (
                                        <p className="mt-0.5 text-[12px] text-muted">
                                            {joinRecorded([
                                                certification.issuer,
                                                certification.issued_on,
                                                certification.expires_on ? `Expires ${certification.expires_on}` : null,
                                            ])}
                                        </p>
                                    ) : null}
                                </li>
                            ))}
                        </ul>
                    </ResumeSection>
                ) : null}

                {languages.length > 0 ? (
                    <ResumeSection title="Languages">
                        <p>
                            {languages
                                .map((language) => joinRecorded([language.name, language.proficiency_label], ' — '))
                                .filter((value): value is string => value !== null)
                                .join(', ')}
                        </p>
                    </ResumeSection>
                ) : null}

                {additional.length > 0 ? (
                    <ResumeSection title="Additional information">
                        <ul className="list-disc space-y-1 pl-5">
                            {additional.map((item) => (
                                <li key={item}>{item}</li>
                            ))}
                        </ul>
                    </ResumeSection>
                ) : null}
            </div>
        </article>
    );
}
