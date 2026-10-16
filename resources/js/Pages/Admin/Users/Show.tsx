import { Link, router, useForm } from '@inertiajs/react';
import { useMemo, useState, type FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import {
    Button,
    ConfirmDialog,
    EmptyState,
    Input,
    Label,
    Select,
    StatusBadge,
    Tabs,
} from '@/Components/Admin/ui';
import type { StaffRoleOption, TagOption, User360Payload, UserSegmentOption } from '@/types/security';

type Props = {
    record: User360Payload;
    can_manage: boolean;
    can_change_status: boolean;
    actor_is_super_admin: boolean;
    options: {
        account_types: Array<{ value: string; label: string }>;
        roles: StaffRoleOption[];
        statuses: Array<{ value: string; label: string }>;
        languages: Array<{ value: string; label: string }>;
        brands: Array<{ id: number; name: string }>;
        segments: UserSegmentOption[];
        verifications: Array<{ value: string; label: string }>;
        registration_sources: Array<{ value: string; label: string }>;
        tags: TagOption[];
    };
};

const tabs = [
    { id: 'profile', label: 'Profile' },
    { id: 'activity', label: 'Activity' },
    { id: 'jobs', label: 'Jobs' },
    { id: 'applications', label: 'Applications' },
    { id: 'documents', label: 'Documents' },
    { id: 'notifications', label: 'Notifications' },
    { id: 'messages', label: 'Messages' },
    { id: 'payments', label: 'Payments' },
    { id: 'security', label: 'Security' },
];

function accountLabel(type: string): string {
    return type.charAt(0).toUpperCase() + type.slice(1);
}

function statusLabel(value: string): string {
    return value.replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
}

export default function UsersShow({ record, can_manage, can_change_status, options }: Props) {
    const user = record.user;
    const [tab, setTab] = useState('profile');
    const [pendingStatus, setPendingStatus] = useState<string | null>(null);

    const profileForm = useForm({
        name: user.name,
        username: user.username ?? '',
        email: user.email,
        mobile: user.mobile ?? '',
        location: user.location ?? '',
        preferred_language: user.preferred_language ?? 'en',
        photo: null as File | null,
        remove_photo: false,
    });

    const preferenceMap = useMemo(() => {
        const next: Record<string, boolean> = {};
        record.communication_preferences.forEach((item) => {
            next[item.channel] = item.opted_in;
        });
        return next;
    }, [record.communication_preferences]);

    const prefsForm = useForm({ channels: preferenceMap });
    const sourceForm = useForm({
        registration_source: user.registration_source ?? 'website',
    });
    const assignedTagIds = (user.tags ?? []).map((tag) => tag.id);
    const tagsForm = useForm({
        tag_ids: assignedTagIds,
    });
    const createTagForm = useForm({ name: '' });
    const [renameTagId, setRenameTagId] = useState<number | null>(null);
    const renameTagForm = useForm({ name: '' });

    const saveProfile = (event: FormEvent) => {
        event.preventDefault();
        profileForm.put(`/admin/users/${user.id}/profile`, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const savePrefs = (event: FormEvent) => {
        event.preventDefault();
        prefsForm.put(`/admin/users/${user.id}/preferences`, { preserveScroll: true });
    };

    const saveSource = (event: FormEvent) => {
        event.preventDefault();
        sourceForm.put(`/admin/users/${user.id}/source`, { preserveScroll: true });
    };

    const saveTags = (event: FormEvent) => {
        event.preventDefault();
        tagsForm.put(`/admin/users/${user.id}/tags`, { preserveScroll: true });
    };

    const createTag = (event: FormEvent) => {
        event.preventDefault();
        createTagForm.post('/admin/tags', {
            preserveScroll: true,
            onSuccess: () => createTagForm.reset(),
        });
    };

    const applyStatus = (status: string) => {
        router.patch(`/admin/users/${user.id}/status`, { account_status: status }, { preserveScroll: true });
    };

    const destructive = ['blocked', 'suspended', 'deactivated'];

    return (
        <AdminLayout
            title={user.name}
            breadcrumbs={[
                { label: 'Admin', href: '/admin' },
                { label: 'Users', href: '/admin/users' },
            ]}
        >
            <div className="mb-6 flex flex-wrap items-start justify-between gap-4 rounded-xl border border-line bg-white p-5">
                <div className="flex min-w-0 items-start gap-4">
                    {user.photo_url ? (
                        <img src={user.photo_url} alt="" className="h-16 w-16 rounded-full object-cover" />
                    ) : (
                        <div className="flex h-16 w-16 items-center justify-center rounded-full bg-surface text-lg font-semibold text-ink">
                            {user.name.slice(0, 1).toUpperCase()}
                        </div>
                    )}
                    <div className="min-w-0">
                        <h1 className="text-2xl font-semibold tracking-tight text-ink">{user.name}</h1>
                        <p className="mt-1 text-sm text-muted">
                            {user.business_id || 'OurNear ID pending'} · @{user.username || 'no-username'}
                        </p>
                        <div className="mt-2 flex flex-wrap gap-2">
                            <StatusBadge status={user.account_status_label || (user.is_active ? 'Active' : 'Inactive')} />
                            <StatusBadge status={user.email_verified ? 'Verified' : 'Unverified'} />
                            <StatusBadge status={accountLabel(user.account_type)} />
                        </div>
                        <p className="mt-2 text-sm text-muted">
                            Source: {user.registration_source_label || '—'} · Last login: {user.last_login_at || 'Never'} · Last
                            activity: {user.last_activity_at || '—'}
                        </p>
                    </div>
                </div>
                <div className="flex flex-wrap gap-2">
                    {user.account_type === 'admin' ? (
                        <Link href={`/admin/staff?user=${user.id}`}>
                            <Button variant="secondary">Admin Users</Button>
                        </Link>
                    ) : null}
                    {record.candidate ? (
                        <Link href={`/admin/candidates?candidate=${record.candidate.id}`}>
                            <Button variant="secondary">Candidate profile</Button>
                        </Link>
                    ) : null}
                    {record.employer ? (
                        <Link href={record.employer.workspace_url || `/admin/employers?employer=${record.employer.id}`}>
                            <Button variant="secondary">Employer profile</Button>
                        </Link>
                    ) : null}
                </div>
            </div>

            <Tabs tabs={tabs} active={tab} onChange={setTab}>
                {tab === 'profile' ? (
                    <div className="grid gap-6 lg:grid-cols-2">
                        <section className="rounded-xl border border-line bg-white p-5">
                            <h2 className="text-lg font-semibold text-ink">Personal details (unified account)</h2>
                            <p className="mt-1 text-sm text-muted">
                                Shared identity across OurNear applications. Professional candidate and employer data stay in
                                separate profiles linked to this account.
                            </p>
                            {can_manage ? (
                                <form className="mt-4 space-y-3" onSubmit={saveProfile}>
                                    <div>
                                        <Label htmlFor="name">Full name</Label>
                                        <Input id="name" value={profileForm.data.name} onChange={(event) => profileForm.setData('name', event.target.value)} />
                                        {profileForm.errors.name ? <p className="mt-1 text-sm text-danger">{profileForm.errors.name}</p> : null}
                                    </div>
                                    <div>
                                        <Label htmlFor="username">Username</Label>
                                        <Input id="username" value={profileForm.data.username} onChange={(event) => profileForm.setData('username', event.target.value)} />
                                        {profileForm.errors.username ? <p className="mt-1 text-sm text-danger">{profileForm.errors.username}</p> : null}
                                    </div>
                                    <div>
                                        <Label htmlFor="email">Email</Label>
                                        <Input id="email" type="email" value={profileForm.data.email} onChange={(event) => profileForm.setData('email', event.target.value)} />
                                        {profileForm.errors.email ? <p className="mt-1 text-sm text-danger">{profileForm.errors.email}</p> : null}
                                    </div>
                                    <div>
                                        <Label htmlFor="mobile">Mobile</Label>
                                        <Input id="mobile" value={profileForm.data.mobile} onChange={(event) => profileForm.setData('mobile', event.target.value)} />
                                    </div>
                                    <div>
                                        <Label htmlFor="location">Location</Label>
                                        <Input id="location" value={profileForm.data.location} onChange={(event) => profileForm.setData('location', event.target.value)} />
                                    </div>
                                    <div>
                                        <Label htmlFor="preferred_language">Preferred language</Label>
                                        <Select
                                            id="preferred_language"
                                            value={profileForm.data.preferred_language}
                                            onChange={(event) => profileForm.setData('preferred_language', event.target.value)}
                                        >
                                            {options.languages.map((language) => (
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
                                            onChange={(event) => profileForm.setData('photo', event.target.files?.[0] ?? null)}
                                        />
                                        {user.photo_url ? (
                                            <label className="mt-2 flex items-center gap-2 text-sm text-ink">
                                                <input
                                                    type="checkbox"
                                                    checked={profileForm.data.remove_photo}
                                                    onChange={(event) => profileForm.setData('remove_photo', event.target.checked)}
                                                />
                                                Remove current photo
                                            </label>
                                        ) : null}
                                    </div>
                                    <Button type="submit" disabled={profileForm.processing}>
                                        Save profile
                                    </Button>
                                </form>
                            ) : (
                                <dl className="mt-4 grid gap-3 text-sm">
                                    <div>
                                        <dt className="text-muted">Email</dt>
                                        <dd>{user.email}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted">Mobile</dt>
                                        <dd>{user.mobile || '—'}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted">Location</dt>
                                        <dd>{user.location || '—'}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted">Language</dt>
                                        <dd>{user.preferred_language || 'en'}</dd>
                                    </div>
                                </dl>
                            )}
                            <dl className="mt-4 grid gap-2 text-sm">
                                <div>
                                    <dt className="text-muted">Registered</dt>
                                    <dd>{user.created_at || '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-muted">Profile completion</dt>
                                    <dd>{user.profile_completion ?? 0}%</dd>
                                </div>
                                <div>
                                    <dt className="text-muted">Brand</dt>
                                    <dd>{user.brand?.name || 'OurNear'}</dd>
                                </div>
                                <div>
                                    <dt className="text-muted">Email verification</dt>
                                    <dd>{user.email_verified ? 'Verified' : 'Unverified'}</dd>
                                </div>
                                <div>
                                    <dt className="text-muted">Mobile verification</dt>
                                    <dd>{user.mobile_verified ? 'Verified' : 'Unverified'}</dd>
                                </div>
                            </dl>
                        </section>

                        <section className="rounded-xl border border-line bg-white p-5 lg:col-span-2">
                            <h2 className="text-lg font-semibold text-ink">Professional profiles</h2>
                            <p className="mt-1 text-sm text-muted">
                                Role-specific résumé, company, and hiring data linked to this unified account.
                            </p>
                            <ul className="mt-4 flex flex-wrap gap-3 text-sm">
                                <li>
                                    {record.candidate ? (
                                        <Link
                                            href={`/admin/candidates?candidate=${record.candidate.id}`}
                                            className="font-semibold text-brand hover:underline"
                                        >
                                            Candidate profile
                                        </Link>
                                    ) : (
                                        <span className="text-muted">No candidate profile</span>
                                    )}
                                </li>
                                <li>
                                    {record.employer ? (
                                        <Link
                                            href={record.employer.workspace_url || `/admin/employers?employer=${record.employer.id}`}
                                            className="font-semibold text-brand hover:underline"
                                        >
                                            Employer / company profile
                                        </Link>
                                    ) : (
                                        <span className="text-muted">No employer profile</span>
                                    )}
                                </li>
                            </ul>
                        </section>

                        <section className="rounded-xl border border-line bg-white p-5 lg:col-span-2">
                            <h2 className="text-lg font-semibold text-ink">Application access and roles</h2>
                            <p className="mt-1 text-sm text-muted">
                                Platform account type and staff roles. Access to hiring, admin, and mobile APIs is enforced
                                separately on the server.
                            </p>
                            <dl className="mt-4 grid gap-2 text-sm sm:grid-cols-2">
                                <div>
                                    <dt className="text-muted">Account type</dt>
                                    <dd>{accountLabel(user.account_type)}</dd>
                                </div>
                                <div>
                                    <dt className="text-muted">Staff roles</dt>
                                    <dd>
                                        {(user.roles ?? []).length > 0
                                            ? (user.roles ?? []).map((role) => role.name).join(', ')
                                            : '—'}
                                    </dd>
                                </div>
                            </dl>
                        </section>

                        <div className="space-y-6">
                            {can_change_status ? (
                                <section className="rounded-xl border border-line bg-white p-5">
                                    <h2 className="text-lg font-semibold text-ink">Account status</h2>
                                    <p className="mt-1 text-sm text-muted">Deactivated accounts keep their history.</p>
                                    <div className="mt-4 flex flex-wrap gap-2">
                                        {options.statuses.map((status) => (
                                            <Button
                                                key={status.value}
                                                variant={user.account_status === status.value ? 'primary' : 'secondary'}
                                                onClick={() => {
                                                    if (destructive.includes(status.value)) {
                                                        setPendingStatus(status.value);
                                                    } else {
                                                        applyStatus(status.value);
                                                    }
                                                }}
                                            >
                                                {status.label}
                                            </Button>
                                        ))}
                                    </div>
                                </section>
                            ) : null}

                            <section className="rounded-xl border border-line bg-white p-5">
                                <h2 className="text-lg font-semibold text-ink">Registration source</h2>
                                <p className="mt-1 text-sm text-muted">Acquisition source for marketing and analytics. This is not a communication preference.</p>
                                {can_manage ? (
                                    <form className="mt-4 space-y-3" onSubmit={saveSource}>
                                        <Select
                                            value={sourceForm.data.registration_source}
                                            onChange={(event) => sourceForm.setData('registration_source', event.target.value)}
                                            aria-label="Registration source"
                                        >
                                            {options.registration_sources.map((source) => (
                                                <option key={source.value} value={source.value}>
                                                    {source.label}
                                                </option>
                                            ))}
                                        </Select>
                                        {sourceForm.errors.registration_source ? (
                                            <p className="text-sm text-danger">{sourceForm.errors.registration_source}</p>
                                        ) : null}
                                        <Button type="submit" disabled={sourceForm.processing}>
                                            Save source
                                        </Button>
                                    </form>
                                ) : (
                                    <p className="mt-3 text-sm text-ink">{user.registration_source_label || '—'}</p>
                                )}
                            </section>

                            <section className="rounded-xl border border-line bg-white p-5">
                                <h2 className="text-lg font-semibold text-ink">Tags</h2>
                                <p className="mt-1 text-sm text-muted">Reusable labels. Segments stay as saved filters and are not tags.</p>
                                <div className="mt-3 flex flex-wrap gap-2">
                                    {(user.tags ?? []).length === 0 ? (
                                        <p className="text-sm text-muted">No tags assigned.</p>
                                    ) : (
                                        (user.tags ?? []).map((tag) => (
                                            <span key={tag.id} className="inline-flex items-center gap-2">
                                                <StatusBadge status={tag.is_active ? tag.name : `${tag.name} (inactive)`} />
                                                {can_manage && !tag.is_active ? (
                                                    <button
                                                        type="button"
                                                        className="text-xs font-medium text-brand hover:underline"
                                                        onClick={() =>
                                                            tagsForm.setData(
                                                                'tag_ids',
                                                                tagsForm.data.tag_ids.filter((id) => id !== tag.id),
                                                            )
                                                        }
                                                    >
                                                        Remove
                                                    </button>
                                                ) : null}
                                            </span>
                                        ))
                                    )}
                                </div>
                                {can_manage ? (
                                    <>
                                        <form className="mt-4 space-y-2" onSubmit={saveTags}>
                                            {options.tags.filter((tag) => tag.is_active).length === 0 ? (
                                                <p className="text-sm text-muted">Create a tag below before assigning one.</p>
                                            ) : (
                                                options.tags
                                                    .filter((tag) => tag.is_active)
                                                    .map((tag) => (
                                                        <label key={tag.id} className="flex items-center gap-2 text-sm text-ink">
                                                            <input
                                                                type="checkbox"
                                                                checked={tagsForm.data.tag_ids.includes(tag.id)}
                                                                onChange={(event) => {
                                                                    const next = event.target.checked
                                                                        ? [...tagsForm.data.tag_ids, tag.id]
                                                                        : tagsForm.data.tag_ids.filter((id) => id !== tag.id);
                                                                    tagsForm.setData('tag_ids', next);
                                                                }}
                                                            />
                                                            {tag.name}
                                                        </label>
                                                    ))
                                            )}
                                            {tagsForm.errors.tag_ids ? <p className="text-sm text-danger">{tagsForm.errors.tag_ids}</p> : null}
                                            <Button type="submit" disabled={tagsForm.processing}>
                                                Save tags
                                            </Button>
                                        </form>
                                        <div className="mt-6 border-t border-line pt-4">
                                            <h3 className="text-sm font-semibold text-ink">Tag library</h3>
                                            <form className="mt-3 flex flex-wrap gap-2" onSubmit={createTag}>
                                                <Input
                                                    value={createTagForm.data.name}
                                                    onChange={(event) => createTagForm.setData('name', event.target.value)}
                                                    placeholder="New tag name"
                                                    aria-label="New tag name"
                                                />
                                                <Button type="submit" disabled={createTagForm.processing}>
                                                    Create tag
                                                </Button>
                                            </form>
                                            {createTagForm.errors.name ? <p className="mt-1 text-sm text-danger">{createTagForm.errors.name}</p> : null}
                                            <ul className="mt-4 space-y-3">
                                                {options.tags.map((tag) => (
                                                    <li key={tag.id} className="flex flex-wrap items-center gap-2 text-sm">
                                                        {renameTagId === tag.id ? (
                                                            <form
                                                                className="flex flex-wrap gap-2"
                                                                onSubmit={(event) => {
                                                                    event.preventDefault();
                                                                    renameTagForm.put(`/admin/tags/${tag.id}`, {
                                                                        preserveScroll: true,
                                                                        onSuccess: () => setRenameTagId(null),
                                                                    });
                                                                }}
                                                            >
                                                                <Input
                                                                    value={renameTagForm.data.name}
                                                                    onChange={(event) => renameTagForm.setData('name', event.target.value)}
                                                                    aria-label={`Rename ${tag.name}`}
                                                                />
                                                                <Button type="submit" disabled={renameTagForm.processing}>
                                                                    Save
                                                                </Button>
                                                                <Button type="button" variant="secondary" onClick={() => setRenameTagId(null)}>
                                                                    Cancel
                                                                </Button>
                                                            </form>
                                                        ) : (
                                                            <>
                                                                <span className="font-medium text-ink">{tag.name}</span>
                                                                <StatusBadge status={tag.is_active ? 'Active' : 'Inactive'} />
                                                                <Button
                                                                    type="button"
                                                                    variant="secondary"
                                                                    onClick={() => {
                                                                        setRenameTagId(tag.id);
                                                                        renameTagForm.setData('name', tag.name);
                                                                    }}
                                                                >
                                                                    Rename
                                                                </Button>
                                                                <Button
                                                                    type="button"
                                                                    variant="secondary"
                                                                    onClick={() =>
                                                                        router.patch(
                                                                            `/admin/tags/${tag.id}/status`,
                                                                            { is_active: !tag.is_active },
                                                                            { preserveScroll: true },
                                                                        )
                                                                    }
                                                                >
                                                                    {tag.is_active ? 'Deactivate' : 'Activate'}
                                                                </Button>
                                                            </>
                                                        )}
                                                    </li>
                                                ))}
                                            </ul>
                                        </div>
                                    </>
                                ) : null}
                            </section>

                            <section className="rounded-xl border border-line bg-white p-5">
                                <h2 className="text-lg font-semibold text-ink">Communication preferences</h2>
                                <p className="mt-1 text-sm text-muted">
                                    Marketing channels stay off unless the user has opted in. System notices can remain enabled.
                                </p>
                                {record.communication_preferences.length === 0 ? (
                                    <EmptyState title="No preferences stored yet" description="Defaults will be created the next time this profile is opened." />
                                ) : can_manage ? (
                                    <form className="mt-4 space-y-2" onSubmit={savePrefs}>
                                        {record.communication_preferences.map((item) => (
                                            <label key={item.channel} className="flex items-start gap-2 text-sm text-ink">
                                                <input
                                                    type="checkbox"
                                                    className="mt-1"
                                                    checked={Boolean(prefsForm.data.channels[item.channel])}
                                                    onChange={(event) =>
                                                        prefsForm.setData('channels', {
                                                            ...prefsForm.data.channels,
                                                            [item.channel]: event.target.checked,
                                                        })
                                                    }
                                                />
                                                <span>
                                                    <span className="font-medium">{item.channel_label}</span>
                                                    <span className="ml-2 text-muted">
                                                        {item.opted_in ? 'Opted in' : 'Opted out'}
                                                        {item.source ? ` · ${item.source}` : ''}
                                                    </span>
                                                </span>
                                            </label>
                                        ))}
                                        <Button type="submit" disabled={prefsForm.processing}>
                                            Save preferences
                                        </Button>
                                    </form>
                                ) : (
                                    <ul className="mt-4 space-y-2 text-sm">
                                        {record.communication_preferences.map((item) => (
                                            <li key={item.channel}>
                                                {item.channel_label}: {item.opted_in ? 'Opted in' : 'Opted out'}
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </section>

                            {record.candidate ? (
                                <section className="rounded-xl border border-line bg-white p-5">
                                    <h2 className="text-lg font-semibold text-ink">Candidate profile</h2>
                                    <p className="mt-1 text-sm text-muted">
                                        <Link
                                            href={record.candidate.workspace_url || `/admin/candidates?candidate=${record.candidate.id}`}
                                            className="font-medium text-brand hover:underline"
                                        >
                                            {record.candidate.business_id}
                                        </Link>
                                    </p>
                                    <p className="mt-2 text-sm text-muted">
                                        {record.candidate.location || 'No location'} · {record.candidate.experience || 'Experience not set'}
                                    </p>
                                </section>
                            ) : null}

                            {record.employer ? (
                                <section className="rounded-xl border border-line bg-white p-5">
                                    <h2 className="text-lg font-semibold text-ink">Employer profile</h2>
                                    <p className="mt-1 text-sm text-muted">
                                        <Link
                                            href={record.employer.workspace_url || `/admin/employers?employer=${record.employer.id}`}
                                            className="font-medium text-brand hover:underline"
                                        >
                                            {record.employer.business_id}
                                        </Link>
                                    </p>
                                    <p className="mt-2 text-sm font-medium text-ink">{record.employer.company_name}</p>
                                    <p className="text-sm text-muted">{record.employer.industry}</p>
                                </section>
                            ) : null}
                        </div>
                    </div>
                ) : null}

                {tab === 'activity' ? (
                    record.activities.length === 0 ? (
                        <EmptyState title="No user activity yet" description="Registration, logins, profile changes, and recruitment events will appear here." />
                    ) : (
                        <ul className="divide-y divide-line rounded-xl border border-line bg-white">
                            {record.activities.map((item) => (
                                <li key={item.id} className="px-4 py-3">
                                    <p className="text-sm font-medium text-ink">{item.type_label}</p>
                                    <p className="text-sm text-muted">{item.description}</p>
                                    <p className="mt-1 text-xs text-muted">
                                        {item.created_at || '—'}
                                        {item.actor_name ? ` · ${item.actor_name}` : ''}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    )
                ) : null}

                {tab === 'jobs' ? (
                    record.jobs.length === 0 ? (
                        <EmptyState title="No jobs linked yet" description="Jobs appear here after this user applies. Registering does not require a job application." />
                    ) : (
                        <ul className="divide-y divide-line rounded-xl border border-line bg-white">
                            {record.jobs.map((job) => (
                                <li key={job.id} className="px-4 py-3">
                                    <Link href={job.href} className="font-medium text-brand hover:underline">
                                        {job.title}
                                    </Link>
                                    <p className="text-sm text-muted">{job.business_id}</p>
                                </li>
                            ))}
                        </ul>
                    )
                ) : null}

                {tab === 'applications' ? (
                    record.applications.length === 0 ? (
                        <EmptyState title="No applications yet" description="This person can exist as a platform user without applying for a job." />
                    ) : (
                        <ul className="divide-y divide-line rounded-xl border border-line bg-white">
                            {record.applications.map((application) => (
                                <li key={application.id} className="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                                    <div>
                                        <Link href={`/admin/applications?application=${application.id}`} className="font-medium text-brand hover:underline">
                                            {application.job?.title || 'Application'}
                                        </Link>
                                        <p className="text-sm text-muted">{application.business_id}</p>
                                    </div>
                                    <StatusBadge status={statusLabel(application.status)} />
                                </li>
                            ))}
                        </ul>
                    )
                ) : null}

                {tab === 'documents' ? (
                    record.documents.length === 0 ? (
                        <EmptyState title="No documents uploaded" description="Resumes attached to applications will be listed here." />
                    ) : (
                        <ul className="divide-y divide-line rounded-xl border border-line bg-white">
                            {record.documents.map((document) => (
                                <li key={`${document.type}-${document.id}`} className="flex items-center justify-between gap-3 px-4 py-3">
                                    <div>
                                        <p className="font-medium text-ink">{document.label}</p>
                                        <p className="text-sm text-muted">{document.type}</p>
                                    </div>
                                    <div className="flex flex-wrap gap-3">
                                        <a
                                            href={document.view_href ?? `${document.href}${document.href.includes('?') ? '&' : '?'}disposition=inline`}
                                            className="text-sm font-medium text-brand hover:underline"
                                            target="_blank"
                                            rel="noreferrer"
                                        >
                                            View
                                        </a>
                                        <a href={document.download_href ?? document.href} className="text-sm font-medium text-brand hover:underline">
                                            Download
                                        </a>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )
                ) : null}

                {tab === 'notifications' ? (
                    record.notifications.length === 0 ? (
                        <EmptyState title="No notifications" description="System notices sent to this user will appear here." />
                    ) : (
                        <ul className="divide-y divide-line rounded-xl border border-line bg-white">
                            {record.notifications.map((item) => (
                                <li key={item.id} className="px-4 py-3">
                                    <p className="text-sm font-medium text-ink">{item.title}</p>
                                    {item.body ? <p className="text-sm text-muted">{item.body}</p> : null}
                                    <p className="mt-1 text-xs text-muted">{item.created_at || '—'} · {item.read_at ? 'Read' : 'Unread'}</p>
                                </li>
                            ))}
                        </ul>
                    )
                ) : null}

                {tab === 'messages' ? (
                    record.messages.length === 0 ? (
                        <EmptyState title="No messages" description="Conversations linked to this user will appear here." />
                    ) : (
                        <ul className="divide-y divide-line rounded-xl border border-line bg-white">
                            {record.messages.map((item) => (
                                <li key={item.id} className="px-4 py-3">
                                    <Link href={item.href} className="font-medium text-brand hover:underline">
                                        {item.subject || 'Conversation'}
                                    </Link>
                                    <p className="text-sm text-muted">{item.updated_at || '—'}</p>
                                </li>
                            ))}
                        </ul>
                    )
                ) : null}

                {tab === 'payments' ? (
                    record.payments.length === 0 ? (
                        <EmptyState title="No payments" description="Invoices linked to this user, employer, or placement will appear here." />
                    ) : (
                        <ul className="divide-y divide-line rounded-xl border border-line bg-white">
                            {record.payments.map((item) => (
                                <li key={item.id} className="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                                    <div>
                                        <Link href={`/admin/finance?invoice=${item.id}`} className="font-medium text-brand hover:underline">
                                            {item.business_id || `Invoice #${item.id}`}
                                        </Link>
                                        <p className="text-sm text-muted">{item.billed_at || '—'} · {item.total_amount}</p>
                                    </div>
                                    <StatusBadge status={statusLabel(item.status)} />
                                </li>
                            ))}
                        </ul>
                    )
                ) : null}

                {tab === 'security' ? (
                    <div className="grid gap-6 lg:grid-cols-2">
                        <section className="rounded-xl border border-line bg-white p-5">
                            <h2 className="text-lg font-semibold text-ink">Login history</h2>
                            {record.login_logs.length === 0 ? (
                                <EmptyState title="No login events" description="Successful and failed sign-in events will appear here." />
                            ) : (
                                <ul className="mt-3 space-y-3 text-sm">
                                    {record.login_logs.map((item) => (
                                        <li key={item.id}>
                                            <p className="font-medium text-ink">{item.event_label}</p>
                                            <p className="text-muted">{item.created_at || '—'} · {item.ip_address || 'No IP'}</p>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>
                        <section className="rounded-xl border border-line bg-white p-5">
                            <h2 className="text-lg font-semibold text-ink">Security activity</h2>
                            {record.security_activities.length === 0 ? (
                                <EmptyState title="No security activity" description="Password resets, blocks, and role changes will appear here." />
                            ) : (
                                <ul className="mt-3 space-y-3 text-sm">
                                    {record.security_activities.map((item) => (
                                        <li key={item.id}>
                                            <p className="font-medium text-ink">{item.action_label}</p>
                                            <p className="text-muted">{item.description}</p>
                                            <p className="text-xs text-muted">{item.created_at || '—'}</p>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>
                    </div>
                ) : null}
            </Tabs>

            <ConfirmDialog
                open={pendingStatus !== null}
                title="Change account status"
                description="This keeps the user history. Blocked, suspended, and deactivated accounts cannot sign in."
                confirmLabel="Change status"
                danger
                onClose={() => setPendingStatus(null)}
                onConfirm={() => {
                    if (pendingStatus) {
                        applyStatus(pendingStatus);
                    }
                    setPendingStatus(null);
                }}
            />
        </AdminLayout>
    );
}
