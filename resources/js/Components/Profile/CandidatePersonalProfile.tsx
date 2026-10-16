import { Link } from '@inertiajs/react';
import { type ReactNode } from 'react';
import Avatar from '@/Components/atoms/Avatar';
import Button from '@/Components/atoms/Button';
import ProfilePhotoUpload from '@/Components/Profile/ProfilePhotoUpload';
import FormField from '@/Components/Public/FormField';
import type { AccountLanguageOption, AccountProfile } from '@/types/account';

export type PersonalSectionId = 'summary' | 'basic' | 'location' | 'contact';

type PersonalFormSlice = {
    name: string;
    username: string;
    mobile: string;
    location: string;
    preferred_language: string;
};

type Props = {
    profile: AccountProfile;
    languages: AccountLanguageOption[];
    form: PersonalFormSlice;
    formErrors: Partial<Record<keyof PersonalFormSlice | 'photo', string>>;
    editingSection: PersonalSectionId | null;
    saving: boolean;
    onEditSection: (section: PersonalSectionId) => void;
    onCancelSection: () => void;
    onChange: (patch: Partial<PersonalFormSlice>) => void;
    onSaveSection: (section: PersonalSectionId) => void;
    viewOnly?: boolean;
};

const selectClass =
    'w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand';

function SectionCard({
    title,
    description,
    editing,
    onEdit,
    onCancel,
    onSave,
    saving,
    cancelOnly = false,
    children,
    view,
}: {
    title: string;
    description?: string;
    editing: boolean;
    onEdit?: () => void;
    onCancel: () => void;
    onSave: () => void;
    saving: boolean;
    cancelOnly?: boolean;
    view: ReactNode;
    children: ReactNode;
}) {
    return (
        <section className="rounded-xl border border-line bg-white p-5 sm:p-6">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 className="text-base font-semibold text-ink">{title}</h2>
                    {description ? <p className="mt-1 text-sm text-muted">{description}</p> : null}
                </div>
                {!editing && onEdit ? (
                    <button type="button" className="text-sm font-semibold text-brand hover:underline" onClick={onEdit}>
                        Edit
                    </button>
                ) : null}
            </div>
            <div className="mt-4">{editing ? children : view}</div>
            {editing ? (
                <div className="mt-5 flex flex-wrap gap-3 border-t border-line pt-4">
                    {!cancelOnly ? (
                        <Button type="button" variant="primary" loading={saving} disabled={saving} onClick={onSave}>
                            Save
                        </Button>
                    ) : null}
                    <Button type="button" variant="outline" disabled={saving} onClick={onCancel}>
                        {cancelOnly ? 'Done' : 'Cancel'}
                    </Button>
                </div>
            ) : null}
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

export default function CandidatePersonalProfile({
    profile,
    languages,
    form,
    formErrors,
    editingSection,
    saving,
    onEditSection,
    onCancelSection,
    onChange,
    onSaveSection,
    viewOnly = false,
}: Props) {
    const languageLabel =
        languages.find((language) => language.value === profile.preferred_language)?.label ?? profile.preferred_language;

    return (
        <div className="space-y-4">
            <SectionCard
                title="Profile summary"
                description="Your name and photo appear on applications and your public profile when sharing is enabled."
                editing={editingSection === 'summary'}
                onEdit={viewOnly ? undefined : () => onEditSection('summary')}
                onCancel={onCancelSection}
                onSave={() => onSaveSection('summary')}
                saving={saving}
                cancelOnly
                view={
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                        <Avatar name={profile.name} src={profile.photo_url ?? undefined} size="xl" shape="circle" />
                        <div className="min-w-0 sm:flex-1">
                            <p className="text-lg font-semibold text-ink">{profile.name}</p>
                            {profile.username ? (
                                <p className="text-sm text-muted">@{profile.username}</p>
                            ) : null}
                            {profile.location ? (
                                <p className="mt-1 text-sm text-muted">{profile.location}</p>
                            ) : null}
                        </div>
                    </div>
                }
            >
                <ProfilePhotoUpload
                    name={profile.name}
                    photoUrl={profile.photo_url}
                    onSuccess={() => onCancelSection()}
                />
                {formErrors.photo ? <p className="mt-2 text-sm text-danger">{formErrors.photo}</p> : null}
            </SectionCard>

            <SectionCard
                title="Basic personal information"
                editing={editingSection === 'basic'}
                onEdit={viewOnly ? undefined : () => onEditSection('basic')}
                onCancel={onCancelSection}
                onSave={() => onSaveSection('basic')}
                saving={saving}
                view={
                    <dl className="space-y-3">
                        <FieldRow label="Full name" value={profile.name || '—'} />
                        <FieldRow label="Username" value={profile.username ? `@${profile.username}` : '—'} />
                    </dl>
                }
            >
                <div className="space-y-4">
                    <FormField
                        id="personal_name"
                        label="Full name"
                        value={form.name}
                        onChange={(event) => onChange({ name: event.target.value })}
                    />
                    {formErrors.name ? <p className="-mt-2 text-sm text-danger">{formErrors.name}</p> : null}
                    <FormField
                        id="personal_username"
                        label="Username"
                        value={form.username}
                        autoComplete="username"
                        onChange={(event) => onChange({ username: event.target.value })}
                    />
                    {formErrors.username ? <p className="-mt-2 text-sm text-danger">{formErrors.username}</p> : null}
                </div>
            </SectionCard>

            <SectionCard
                title="Location and preferences"
                editing={editingSection === 'location'}
                onEdit={viewOnly ? undefined : () => onEditSection('location')}
                onCancel={onCancelSection}
                onSave={() => onSaveSection('location')}
                saving={saving}
                view={
                    <dl className="space-y-3">
                        <FieldRow label="Location" value={profile.location || '—'} />
                        <FieldRow label="Preferred language" value={languageLabel || '—'} />
                    </dl>
                }
            >
                <div className="space-y-4">
                    <FormField
                        id="personal_location"
                        label="Location"
                        value={form.location}
                        onChange={(event) => onChange({ location: event.target.value })}
                    />
                    {formErrors.location ? <p className="-mt-2 text-sm text-danger">{formErrors.location}</p> : null}
                    <div>
                        <label htmlFor="personal_preferred_language" className="mb-1 block text-sm font-medium text-ink">
                            Preferred language
                        </label>
                        <select
                            id="personal_preferred_language"
                            className={selectClass}
                            value={form.preferred_language}
                            onChange={(event) => onChange({ preferred_language: event.target.value })}
                        >
                            {languages.map((language) => (
                                <option key={language.value} value={language.value}>
                                    {language.label}
                                </option>
                            ))}
                        </select>
                        {formErrors.preferred_language ? (
                            <p className="mt-1 text-sm text-danger">{formErrors.preferred_language}</p>
                        ) : null}
                    </div>
                </div>
            </SectionCard>

            <SectionCard
                title="Contact information"
                description="Email and WhatsApp are managed in Security settings. Updating mobile here does not verify your number."
                editing={editingSection === 'contact'}
                onEdit={viewOnly ? undefined : () => onEditSection('contact')}
                onCancel={onCancelSection}
                onSave={() => onSaveSection('contact')}
                saving={saving}
                view={
                    <dl className="space-y-3">
                        <FieldRow label="Email" value={profile.email} />
                        <FieldRow
                            label="Email status"
                            value={profile.email_verified ? 'Verified' : 'Not verified'}
                        />
                        <FieldRow label="Mobile" value={profile.mobile || '—'} />
                        <FieldRow
                            label="Mobile status"
                            value={profile.mobile_verified ? 'Verified' : 'Not verified'}
                        />
                        {profile.whatsapp_number ? (
                            <>
                                <FieldRow label="WhatsApp" value={profile.whatsapp_number} />
                                <FieldRow
                                    label="WhatsApp status"
                                    value={profile.whatsapp_verified ? 'Verified' : 'Not verified'}
                                />
                            </>
                        ) : null}
                    </dl>
                }
            >
                <div className="space-y-4">
                    <div>
                        <p className="mb-1 text-sm font-medium text-ink">Email</p>
                        <p className="text-sm text-muted">{profile.email}</p>
                        <p className="mt-1 text-xs text-muted">
                            Change or verify email in{' '}
                            <Link href="/account/settings/verification" className="font-medium text-brand hover:underline">
                                Security settings
                            </Link>
                            .
                        </p>
                    </div>
                    <FormField
                        id="personal_mobile"
                        label="Mobile"
                        value={form.mobile}
                        onChange={(event) => onChange({ mobile: event.target.value })}
                    />
                    {formErrors.mobile ? <p className="-mt-2 text-sm text-danger">{formErrors.mobile}</p> : null}
                    {profile.whatsapp_number ? (
                        <div>
                            <p className="mb-1 text-sm font-medium text-ink">WhatsApp</p>
                            <p className="text-sm text-muted">{profile.whatsapp_number}</p>
                            <p className="mt-1 text-xs text-muted">
                                Manage in{' '}
                                <Link href="/account/settings/verification" className="font-medium text-brand hover:underline">
                                    Security settings
                                </Link>
                                .
                            </p>
                        </div>
                    ) : null}
                </div>
            </SectionCard>
        </div>
    );
}
