import { Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Button, EmptyState, Input, Label, Select, StatusBadge, Textarea } from '@/Components/Admin/ui';
import type { SharedPageProps } from '@/types/page';
import type { ActivityLogItem, LoginLogItem, UserActivityItem, UserDetail } from '@/types/security';

type Props = {
    profile: UserDetail;
    languages: Array<{ value: string; label: string }>;
    login_logs: LoginLogItem[];
    security_activities: ActivityLogItem[];
    activities: UserActivityItem[];
};

export default function AdminProfileShow({ profile, languages, login_logs, security_activities, activities }: Props) {
    const permissions = usePage<SharedPageProps>().props.auth.user?.permissions ?? [];
    const canManageWebsite = permissions.includes('website.manage');
    const form = useForm({
        name: profile.name,
        username: profile.username ?? '',
        email: profile.email,
        mobile: profile.mobile ?? '',
        location: profile.location ?? '',
        preferred_language: profile.preferred_language ?? 'en',
        department: profile.admin_profile?.department ?? '',
        designation: profile.admin_profile?.designation ?? '',
        joined_at: profile.admin_profile?.joined_at ?? '',
        professional_details: profile.admin_profile?.professional_details ?? '',
        qualifications: profile.admin_profile?.qualifications ?? '',
        skills: profile.admin_profile?.skills ?? '',
        employment_information: profile.admin_profile?.employment_information ?? '',
        photo: null as File | null,
        remove_photo: false,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put('/admin/profile', { forceFormData: true, preserveScroll: true });
    };

    return (
        <AdminLayout title="Admin Profile">
            <div className="mb-6 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight text-ink">Admin Profile</h1>
                    <p className="mt-1 text-sm text-muted">
                        Your staff profile is separate from Website Settings.
                    </p>
                </div>
                <div className="flex flex-wrap gap-2">
                    <Link href="/admin/profile/password">
                        <Button>Change password</Button>
                    </Link>
                    {canManageWebsite ? (
                        <Link href="/admin/settings">
                            <Button variant="secondary">Website Settings</Button>
                        </Link>
                    ) : null}
                </div>
            </div>

            <div className="mb-6 flex flex-wrap items-center gap-4 rounded-xl border border-line bg-white p-5">
                {profile.photo_url ? (
                    <img src={profile.photo_url} alt="" className="h-16 w-16 rounded-full object-cover" />
                ) : (
                    <div className="flex h-16 w-16 items-center justify-center rounded-full bg-surface text-lg font-semibold text-ink">
                        {profile.name.slice(0, 1).toUpperCase()}
                    </div>
                )}
                <div>
                    <p className="text-lg font-semibold text-ink">{profile.name}</p>
                    <p className="text-sm text-muted">{profile.business_id || 'OurNear ID pending'} · @{profile.username || 'no-username'}</p>
                    <div className="mt-2 flex flex-wrap gap-2">
                        <StatusBadge status={profile.account_status_label || 'Active'} />
                        {profile.roles.map((role) => (
                            <StatusBadge key={role.id} status={role.name} />
                        ))}
                    </div>
                </div>
            </div>

            <form onSubmit={submit} className="grid gap-6 lg:grid-cols-2">
                <section className="rounded-xl border border-line bg-white p-5">
                    <h2 className="text-lg font-semibold text-ink">Identity</h2>
                    <div className="mt-4 space-y-3">
                        <div>
                            <Label htmlFor="name">Full name</Label>
                            <Input id="name" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} />
                            {form.errors.name ? <p className="mt-1 text-sm text-danger">{form.errors.name}</p> : null}
                        </div>
                        <div>
                            <Label htmlFor="username">Username</Label>
                            <Input id="username" value={form.data.username} onChange={(event) => form.setData('username', event.target.value)} />
                            {form.errors.username ? <p className="mt-1 text-sm text-danger">{form.errors.username}</p> : null}
                        </div>
                        <div>
                            <Label htmlFor="email">Email</Label>
                            <Input id="email" type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} />
                            {form.errors.email ? <p className="mt-1 text-sm text-danger">{form.errors.email}</p> : null}
                        </div>
                        <div>
                            <Label htmlFor="mobile">Mobile</Label>
                            <Input id="mobile" value={form.data.mobile} onChange={(event) => form.setData('mobile', event.target.value)} />
                        </div>
                        <div>
                            <Label htmlFor="location">Location</Label>
                            <Input id="location" value={form.data.location} onChange={(event) => form.setData('location', event.target.value)} />
                        </div>
                        <div>
                            <Label htmlFor="preferred_language">Preferred language</Label>
                            <Select
                                id="preferred_language"
                                value={form.data.preferred_language}
                                onChange={(event) => form.setData('preferred_language', event.target.value)}
                            >
                                {languages.map((language) => (
                                    <option key={language.value} value={language.value}>
                                        {language.label}
                                    </option>
                                ))}
                            </Select>
                        </div>
                        <div>
                            <Label htmlFor="photo">Profile photo</Label>
                            <Input
                                id="photo"
                                type="file"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                onChange={(event) => form.setData('photo', event.target.files?.[0] ?? null)}
                            />
                            {profile.photo_url ? (
                                <label className="mt-2 flex items-center gap-2 text-sm text-ink">
                                    <input
                                        type="checkbox"
                                        checked={form.data.remove_photo}
                                        onChange={(event) => form.setData('remove_photo', event.target.checked)}
                                    />
                                    Remove current photo
                                </label>
                            ) : null}
                        </div>
                    </div>
                </section>

                <section className="rounded-xl border border-line bg-white p-5">
                    <h2 className="text-lg font-semibold text-ink">Professional details</h2>
                    <div className="mt-4 space-y-3">
                        <div>
                            <Label htmlFor="department">Department</Label>
                            <Input id="department" value={form.data.department} onChange={(event) => form.setData('department', event.target.value)} />
                        </div>
                        <div>
                            <Label htmlFor="designation">Designation</Label>
                            <Input id="designation" value={form.data.designation} onChange={(event) => form.setData('designation', event.target.value)} />
                        </div>
                        <div>
                            <Label htmlFor="joined_at">Joining date</Label>
                            <Input id="joined_at" type="date" value={form.data.joined_at ?? ''} onChange={(event) => form.setData('joined_at', event.target.value)} />
                        </div>
                        <div>
                            <Label htmlFor="professional_details">Professional details</Label>
                            <Textarea id="professional_details" value={form.data.professional_details} onChange={(event) => form.setData('professional_details', event.target.value)} />
                        </div>
                        <div>
                            <Label htmlFor="qualifications">Qualifications</Label>
                            <Textarea id="qualifications" value={form.data.qualifications} onChange={(event) => form.setData('qualifications', event.target.value)} />
                        </div>
                        <div>
                            <Label htmlFor="skills">Skills</Label>
                            <Textarea id="skills" value={form.data.skills} onChange={(event) => form.setData('skills', event.target.value)} />
                        </div>
                        <div>
                            <Label htmlFor="employment_information">Employment information</Label>
                            <Textarea id="employment_information" value={form.data.employment_information} onChange={(event) => form.setData('employment_information', event.target.value)} />
                        </div>
                    </div>
                </section>

                <div className="lg:col-span-2">
                    <Button type="submit" disabled={form.processing}>
                        Save profile
                    </Button>
                </div>
            </form>

            <div className="mt-8 grid gap-6 lg:grid-cols-3">
                <section className="rounded-xl border border-line bg-white p-5">
                    <h2 className="text-lg font-semibold text-ink">Activity</h2>
                    {activities.length === 0 ? (
                        <EmptyState title="No activity yet" />
                    ) : (
                        <ul className="mt-3 space-y-3 text-sm">
                            {activities.map((item) => (
                                <li key={item.id}>
                                    <p className="font-medium text-ink">{item.type_label}</p>
                                    <p className="text-muted">{item.description}</p>
                                    <p className="text-xs text-muted">{item.created_at || '—'}</p>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
                <section className="rounded-xl border border-line bg-white p-5">
                    <h2 className="text-lg font-semibold text-ink">Login activity</h2>
                    {login_logs.length === 0 ? (
                        <EmptyState title="No login events" />
                    ) : (
                        <ul className="mt-3 space-y-3 text-sm">
                            {login_logs.map((item) => (
                                <li key={item.id}>
                                    <p className="font-medium text-ink">{item.event_label}</p>
                                    <p className="text-muted">{item.created_at || '—'} · {item.ip_address || 'No IP'}</p>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
                <section className="rounded-xl border border-line bg-white p-5">
                    <h2 className="text-lg font-semibold text-ink">Security</h2>
                    <p className="mt-2 text-sm text-muted">Role: {profile.roles.map((role) => role.name).join(', ') || '—'}</p>
                    <p className="text-sm text-muted">Status: {profile.account_status_label || 'Active'}</p>
                    {security_activities.length === 0 ? (
                        <EmptyState title="No security events" />
                    ) : (
                        <ul className="mt-3 space-y-3 text-sm">
                            {security_activities.map((item) => (
                                <li key={item.id}>
                                    <p className="font-medium text-ink">{item.action_label}</p>
                                    <p className="text-muted">{item.description}</p>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </AdminLayout>
    );
}
