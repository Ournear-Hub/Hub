import { Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Button, Select, StatusBadge } from '@/Components/Admin/ui';
import type { SharedPageProps } from '@/types/page';

type StatusHistory = {
    from: string | null;
    to: string;
    actor_id: number | null;
    actor_name: string | null;
    created_at: string;
};

type PossibleMatch = {
    business_id: string | null;
    name: string;
    role: string;
};

type FreelancerDetail = {
    id: number;
    business_id: string;
    name: string;
    mobile: string;
    email: string;
    gender: string | null;
    date_of_birth: string | null;
    location: string | null;
    preferred_locations: string[];
    primary_skill: string;
    current_designation: string | null;
    current_company: string | null;
    industry: string | null;
    functional_area: string | null;
    experience: string | null;
    employment_type: string | null;
    preferred_work_type: string | null;
    expected_rate: string | null;
    notice_period: string | null;
    availability: string | null;
    skills: string[];
    summary: string | null;
    consent: boolean;
    status: string;
    status_label: string;
    submitted_at: string | null;
    has_resume: boolean;
    resume_name: string | null;
    has_certificate: boolean;
    certificate_name: string | null;
    resume_url: string;
    certificate_url: string;
    status_history: StatusHistory[];
    possible_matches: PossibleMatch[];
};

type Props = {
    freelancer: FreelancerDetail;
    statuses: Array<{ value: string; label: string }>;
};

function Field({ label, value }: { label: string; value: string | number | null | undefined }) {
    return (
        <div>
            <dt className="text-xs font-semibold uppercase tracking-wide text-muted">{label}</dt>
            <dd className="mt-1 text-sm text-ink">{value || '—'}</dd>
        </div>
    );
}

export default function FreelancersShow({ freelancer, statuses }: Props) {
    const flash = usePage<SharedPageProps>().props.flash;
    const form = useForm({ status: freelancer.status });

    const updateStatus = (event: FormEvent) => {
        event.preventDefault();
        form.patch(`/admin/freelancers/${freelancer.id}/status`, { preserveScroll: true });
    };

    return (
        <AdminLayout title={freelancer.business_id}>
            <div className="mb-6 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <Link href="/admin/freelancers" className="text-sm font-medium text-brand">
                        Back to freelancers
                    </Link>
                    <h1 className="mt-2 text-2xl font-semibold tracking-tight text-ink">{freelancer.name}</h1>
                    <p className="mt-1 text-sm text-muted">
                        {freelancer.business_id} · <StatusBadge status={freelancer.status_label} />
                    </p>
                </div>
            </div>

            {flash.success ? (
                <p className="mb-4 rounded-md border border-success/20 bg-success/10 px-3 py-2 text-sm text-success">
                    {flash.success}
                </p>
            ) : null}

            <div className="grid gap-6 xl:grid-cols-3">
                <div className="space-y-6 xl:col-span-2">
                    <section className="rounded-xl border border-line bg-white p-6">
                        <h2 className="text-sm font-semibold uppercase tracking-wide text-muted">Personal</h2>
                        <dl className="mt-4 grid gap-4 sm:grid-cols-2">
                            <Field label="Full name" value={freelancer.name} />
                            <Field label="Gender" value={freelancer.gender} />
                            <Field label="Date of birth" value={freelancer.date_of_birth} />
                            <Field label="Freelancer ID" value={freelancer.business_id} />
                        </dl>
                    </section>

                    <section className="rounded-xl border border-line bg-white p-6">
                        <h2 className="text-sm font-semibold uppercase tracking-wide text-muted">Contact</h2>
                        <dl className="mt-4 grid gap-4 sm:grid-cols-2">
                            <Field label="Mobile" value={freelancer.mobile} />
                            <Field label="Email" value={freelancer.email} />
                        </dl>
                    </section>

                    <section className="rounded-xl border border-line bg-white p-6">
                        <h2 className="text-sm font-semibold uppercase tracking-wide text-muted">Location</h2>
                        <dl className="mt-4 grid gap-4 sm:grid-cols-2">
                            <Field label="Current location" value={freelancer.location} />
                            <Field label="Preferred locations" value={freelancer.preferred_locations.join(', ')} />
                        </dl>
                    </section>

                    <section className="rounded-xl border border-line bg-white p-6">
                        <h2 className="text-sm font-semibold uppercase tracking-wide text-muted">Professional</h2>
                        <dl className="mt-4 grid gap-4 sm:grid-cols-2">
                            <Field label="Primary skill" value={freelancer.primary_skill} />
                            <Field label="Current designation" value={freelancer.current_designation} />
                            <Field label="Current company" value={freelancer.current_company} />
                            <Field label="Industry" value={freelancer.industry} />
                            <Field label="Functional area" value={freelancer.functional_area} />
                            <Field label="Experience" value={freelancer.experience} />
                        </dl>
                    </section>

                    <section className="rounded-xl border border-line bg-white p-6">
                        <h2 className="text-sm font-semibold uppercase tracking-wide text-muted">Skills</h2>
                        <p className="mt-3 text-sm text-ink">
                            {freelancer.skills.length > 0 ? freelancer.skills.join(', ') : '—'}
                        </p>
                        {freelancer.summary ? (
                            <p className="mt-4 whitespace-pre-line text-sm text-muted">{freelancer.summary}</p>
                        ) : null}
                    </section>

                    <section className="rounded-xl border border-line bg-white p-6">
                        <h2 className="text-sm font-semibold uppercase tracking-wide text-muted">Work preferences</h2>
                        <dl className="mt-4 grid gap-4 sm:grid-cols-2">
                            <Field label="Preferred job type" value={freelancer.employment_type} />
                            <Field label="Preferred work type" value={freelancer.preferred_work_type} />
                            <Field label="Expected rate / salary" value={freelancer.expected_rate} />
                            <Field label="Notice period" value={freelancer.notice_period} />
                            <Field label="Availability" value={freelancer.availability} />
                        </dl>
                    </section>
                </div>

                <div className="space-y-6">
                    <section className="rounded-xl border border-line bg-white p-6">
                        <h2 className="text-sm font-semibold uppercase tracking-wide text-muted">Status</h2>
                        <form onSubmit={updateStatus} className="mt-4 space-y-3">
                            <Select
                                value={form.data.status}
                                aria-label="Freelancer status"
                                onChange={(event) => form.setData('status', event.target.value)}
                            >
                                {statuses.map((status) => (
                                    <option key={status.value} value={status.value}>
                                        {status.label}
                                    </option>
                                ))}
                            </Select>
                            {form.errors.status && <p className="text-sm text-danger">{form.errors.status}</p>}
                            <Button type="submit">Update status</Button>
                        </form>
                        <p className="mt-3 text-xs text-muted">
                            Converted is a status only. This does not create a user or candidate profile.
                        </p>
                        <ul className="mt-4 space-y-2 text-xs text-muted">
                            {freelancer.status_history.map((item, index) => (
                                <li key={`${item.created_at}-${index}`}>
                                    {item.from ?? '—'} → {item.to} · {item.actor_name ?? 'System'} · {item.created_at}
                                </li>
                            ))}
                        </ul>
                    </section>

                    <section className="rounded-xl border border-line bg-white p-6">
                        <h2 className="text-sm font-semibold uppercase tracking-wide text-muted">Documents</h2>
                        <div className="mt-4 space-y-2 text-sm">
                            {freelancer.has_resume ? (
                                <a href={freelancer.resume_url} className="block font-medium text-brand">
                                    Download resume{freelancer.resume_name ? ` (${freelancer.resume_name})` : ''}
                                </a>
                            ) : (
                                <p className="text-muted">No resume uploaded.</p>
                            )}
                            {freelancer.has_certificate ? (
                                <a href={freelancer.certificate_url} className="block font-medium text-brand">
                                    Download certificate
                                    {freelancer.certificate_name ? ` (${freelancer.certificate_name})` : ''}
                                </a>
                            ) : (
                                <p className="text-muted">No certificate uploaded.</p>
                            )}
                        </div>
                    </section>

                    <section className="rounded-xl border border-line bg-white p-6">
                        <h2 className="text-sm font-semibold uppercase tracking-wide text-muted">Consent & submission</h2>
                        <dl className="mt-4 space-y-3">
                            <Field label="Consent to be contacted" value={freelancer.consent ? 'Yes' : 'No'} />
                            <Field label="Submitted" value={freelancer.submitted_at} />
                        </dl>
                    </section>

                    {freelancer.possible_matches.length > 0 ? (
                        <section className="rounded-xl border border-line bg-white p-6">
                            <h2 className="text-sm font-semibold uppercase tracking-wide text-muted">
                                Possible existing records
                            </h2>
                            <p className="mt-2 text-xs text-muted">
                                Matching email or mobile on an existing user. Records are not merged automatically.
                            </p>
                            <ul className="mt-3 space-y-2 text-sm text-ink">
                                {freelancer.possible_matches.map((match) => (
                                    <li key={`${match.role}-${match.business_id}-${match.name}`}>
                                        {match.business_id ?? '—'} · {match.name} · {match.role}
                                    </li>
                                ))}
                            </ul>
                        </section>
                    ) : null}
                </div>
            </div>
        </AdminLayout>
    );
}
