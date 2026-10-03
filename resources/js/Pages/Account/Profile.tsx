import { Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';
import CandidateProfessionalSections from '@/Components/Account/CandidateProfessionalSections';
import CandidateProfileOverview from '@/Components/Account/CandidateProfileOverview';
import EmployerCompanyProfileView from '@/Components/Account/EmployerCompanyProfileView';
import ProfileShareMenu from '@/Components/Profile/ProfileShareMenu';
import SettingsNav from '@/Components/Account/SettingsNav';
import ProfileCompletionCenter from '@/Components/Profile/ProfileCompletionCenter';
import ProfileModuleContinueBar from '@/Components/Profile/ProfileModuleContinueBar';
import ProfileModuleEditorShell from '@/Components/Profile/ProfileModuleEditorShell';
import {
    buildProfileModules,
    hashToModuleId,
    nextIncompleteModule,
    type ProfileModuleId,
} from '@/Components/Profile/profileCompletionModules';
import ProfileShell from '@/Components/Profile/ProfileShell';
import FormField from '@/Components/Public/FormField';
import AccountLayout from '@/Layouts/AccountLayout';
import type {
    AccountCompanySocials,
    AccountLanguageOption,
    AccountProfile,
    AccountProfileLookups,
    AccountPublicShare,
} from '@/types/account';
import type { SharedPageProps } from '@/types/page';

type ProfileForm = {
    name: string;
    username: string;
    mobile: string;
    location: string;
    bio: string;
    professional_headline: string;
    preferred_language: string;
    company_name: string;
    contact_person: string;
    mobile_number: string;
    industry: string;
    about_company: string;
    company_size: string;
    company_website: string;
    company_details: string;
    location_ids: number[];
    benefits_text: string;
    socials: AccountCompanySocials;
    photo: File | null;
    remove_photo: boolean;
};

type ProfileProps = {
    role: 'candidate' | 'employer';
    profile: AccountProfile;
    languages: AccountLanguageOption[];
    lookups?: AccountProfileLookups;
};

const selectClass =
    'w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand';

export default function Profile({ role, profile, languages, lookups }: ProfileProps) {
    const flash = usePage<SharedPageProps>().props.flash;
    const [editing, setEditing] = useState(false);
    const [editingSection, setEditingSection] = useState<ProfileModuleId | null>(null);
    const mobileRequired = profile.verification_context?.mobile_required === true;
    const candidateHub = role === 'candidate' && editingSection === null;
    const modules = role === 'candidate' ? buildProfileModules(profile) : [];
    const nextModuleAfterSave =
        role === 'candidate' && editingSection ? nextIncompleteModule(modules, editingSection) : null;

    const openModule = (section: ProfileModuleId) => {
        setEditing(false);
        setEditingSection(section);
        window.history.replaceState(null, '', `#${section}`);
    };

    const closeModule = () => {
        setEditingSection(null);
        window.history.replaceState(null, '', window.location.pathname);
    };

    useEffect(() => {
        const hash = window.location.hash.replace('#', '');
        if (hash === 'privacy') {
            window.requestAnimationFrame(() => {
                document.getElementById('privacy')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
            return;
        }
        if (role !== 'candidate') {
            return;
        }
        const moduleId = hashToModuleId(hash);
        if (moduleId) {
            setEditing(false);
            setEditingSection(moduleId);
        }
    }, [role]);

    const form = useForm<ProfileForm>({
        name: profile.name,
        username: profile.username ?? '',
        mobile: profile.mobile ?? '',
        location: profile.location ?? '',
        bio: profile.bio ?? '',
        professional_headline: profile.professional_headline ?? '',
        preferred_language: profile.preferred_language || 'en',
        company_name: profile.company_name ?? '',
        contact_person: profile.contact_person ?? '',
        mobile_number: profile.mobile_number ?? '',
        industry: profile.industry ?? '',
        about_company: profile.about_company ?? '',
        company_size: profile.company_size ?? '',
        company_website: profile.company_website ?? '',
        company_details: profile.company_details ?? '',
        location_ids: profile.location_ids ?? [],
        benefits_text: (profile.benefits ?? []).join('\n'),
        socials: {
            linkedin: profile.socials?.linkedin ?? '',
            facebook: profile.socials?.facebook ?? '',
            twitter: profile.socials?.twitter ?? '',
            instagram: profile.socials?.instagram ?? '',
            youtube: profile.socials?.youtube ?? '',
        },
        photo: null,
        remove_photo: false,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            benefits: data.benefits_text
                .split('\n')
                .map((item) => item.trim())
                .filter(Boolean),
        }));
        form.put('/account/profile', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                if (role === 'candidate' && (editingSection === 'basic' || editingSection === 'about')) {
                    return;
                }
                if (role === 'candidate') {
                    setEditingSection(null);
                }
                if (role === 'employer') {
                    setEditing(false);
                }
            },
        });
    };

    const removePhoto = () => {
        if (!window.confirm('Remove your profile photo?')) {
            return;
        }
        router.delete('/account/profile/photo');
    };

    const showBasicSection = role === 'candidate' && editingSection === 'basic';
    const showAboutSection = role === 'candidate' && editingSection === 'about';
    const employerLocation =
        role === 'employer' && (profile.locations?.length ?? 0) > 0
            ? (profile.locations ?? []).map((location) => location.name).join(', ')
            : profile.location;
    const employerSubtitle =
        role === 'employer'
            ? [profile.industry, profile.company_size].filter((value) => Boolean(value && String(value).trim())).join(' · ') || null
            : null;
    const hideProfileForm =
        role === 'candidate' ? !(editingSection === 'basic' || editingSection === 'about') : !editing;
    const activeModule = modules.find((module) => module.id === editingSection);

    return (
        <AccountLayout
            title={role === 'employer' ? 'Company Profile' : 'Profile'}
            breadcrumbs={
                role === 'employer'
                    ? [
                          { label: 'Hiring', href: '/account/hiring' },
                          { label: 'Company Profile' },
                      ]
                    : [
                          { label: 'Account', href: '/account' },
                          { label: 'Profile' },
                      ]
            }
        >
                    <SettingsNav />
                    {flash.success && (
                        <p className="mb-4 rounded-md border border-success/20 bg-success/10 px-3 py-2 text-sm text-success">
                            {flash.success}
                        </p>
                    )}
                    <ProfileShell
                        name={role === 'employer' ? (profile.company_name || profile.name) : profile.name}
                        photoUrl={profile.photo_url}
                        headerVariant="compact"
                        businessId={profile.business_id}
                        secondaryId={
                            role === 'employer'
                                ? (profile.company_business_id ?? null)
                                : (profile.modules?.candidate ?? null)
                        }
                        secondaryIdLabel={role === 'employer' ? 'Company ID' : 'Candidate ID'}
                        subtitle={
                            role === 'employer'
                                ? employerSubtitle
                                : (profile.headline || profile.professional?.current_designation)
                        }
                        location={role === 'employer' ? employerLocation : profile.location}
                        verifications={role === 'employer' ? undefined : profile.verifications}
                        mobileRequired={mobileRequired}
                        shape={role === 'employer' ? 'rounded' : 'circle'}
                        badge={profile.ournear_verified ? 'OurNear verified' : null}
                        profileHref={role === 'candidate' ? profile.public_profile_url : null}
                        actions={
                            <>
                                {role === 'candidate' && profile.public_profile_url ? (
                                    <>
                                        <a
                                            href={profile.public_profile_url}
                                            className="inline-flex min-h-11 items-center rounded-md border border-line px-4 py-2 text-sm font-semibold text-brand hover:bg-surface"
                                        >
                                            View profile
                                        </a>
                                        <ProfileShareMenu profileUrl={profile.public_profile_url} profileName={profile.name} />
                                    </>
                                ) : null}
                                <button
                                    type="button"
                                    className="inline-flex min-h-11 rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white"
                                    onClick={() => {
                                        if (role === 'candidate') {
                                            openModule('basic');
                                            return;
                                        }
                                        setEditing(true);
                                    }}
                                >
                                    {role === 'employer' ? 'Edit company profile' : 'Edit Profile'}
                                </button>
                            </>
                        }
                        tabs={
                            role === 'candidate'
                                ? [
                                      { href: '/account/profile', label: 'Personal', active: true },
                                      { href: '/account/profile#professional', label: 'Professional' },
                                      { href: '/account/profile#privacy', label: 'Privacy' },
                                      { href: '/account/resumes', label: 'Documents' },
                                  ]
                                : undefined
                        }
                    >
                        {role === 'candidate' && candidateHub ? (
                            <>
                                <ProfileCompletionCenter profile={profile} onOpenModule={openModule} />
                                <CandidateProfileOverview profile={profile} onEdit={openModule} />
                            </>
                        ) : null}
                        {role === 'employer' && editing ? (
                            <p className="text-sm text-muted">Update company information used across hiring, jobs, and your employer identity.</p>
                        ) : null}
                    </ProfileShell>
                    {role === 'employer' && !editing ? <EmployerCompanyProfileView profile={profile} /> : null}
                    {role === 'candidate' && candidateHub && lookups ? (
                        <CandidatePrivacySection
                            share={profile.professional?.public_share}
                            username={profile.username}
                            fields={lookups.public_share_fields ?? []}
                        />
                    ) : null}
                    {flash.success && role === 'candidate' && editingSection ? (
                        <ProfileModuleContinueBar
                            message={flash.success}
                            nextModule={nextModuleAfterSave}
                            onContinueNext={() => {
                                if (nextModuleAfterSave) {
                                    openModule(nextModuleAfterSave.id);
                                } else {
                                    closeModule();
                                }
                            }}
                            onBackToHub={closeModule}
                        />
                    ) : null}
                    <form
                        id="basic"
                        onSubmit={submit}
                        className={`${hideProfileForm ? 'hidden' : ''} space-y-8 rounded-xl border border-line bg-white p-6`}
                    >
                        {role === 'candidate' && (editingSection === 'basic' || editingSection === 'about') ? (
                            <div className="mb-2 flex flex-wrap items-center gap-3 border-b border-line pb-4">
                                <button type="button" className="text-sm font-semibold text-brand" onClick={closeModule}>
                                    ← Back
                                </button>
                                <div>
                                    <p className="text-lg font-semibold text-ink">
                                        {editingSection === 'about' ? 'Professional summary' : 'Basic information'}
                                    </p>
                                    <p className="text-sm text-muted">
                                        {editingSection === 'about'
                                            ? 'Headline and bio shown on your public profile.'
                                            : 'Contact details recruiters use to reach you.'}
                                    </p>
                                </div>
                            </div>
                        ) : null}
                        {showBasicSection ? (
                        <>
                        <section className="space-y-4">
                            <h2 className="text-lg font-semibold text-ink">Profile photo</h2>
                            {profile.photo_url && !form.data.remove_photo ? (
                                <img
                                    src={profile.photo_url}
                                    alt=""
                                    className="h-24 w-24 rounded-full object-cover"
                                />
                            ) : (
                                <div className="flex h-24 w-24 items-center justify-center rounded-full bg-surface text-sm text-muted">
                                    No photo
                                </div>
                            )}
                            <input
                                id="photo"
                                name="photo"
                                type="file"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                className="w-full text-sm text-ink"
                                onChange={(event) => {
                                    form.setData('photo', event.target.files?.[0] ?? null);
                                    form.setData('remove_photo', false);
                                }}
                            />
                            <p className="text-xs text-muted">JPG, PNG, or WEBP. Maximum 5 MB.</p>
                            {form.errors.photo && <p className="text-sm text-danger">{form.errors.photo}</p>}
                            {profile.photo_url ? (
                                <button type="button" className="text-sm font-medium text-danger" onClick={removePhoto}>
                                    Remove photo
                                </button>
                            ) : null}
                        </section>

                        <section className="space-y-4">
                            <h2 className="text-lg font-semibold text-ink">Basic information</h2>
                            <FormField
                                id="name"
                                label="Full name"
                                value={form.data.name}
                                onChange={(event) => form.setData('name', event.target.value)}
                            />
                            {form.errors.name && <p className="-mt-2 text-sm text-danger">{form.errors.name}</p>}
                            <FormField
                                id="username"
                                label="Username"
                                value={form.data.username}
                                autoComplete="username"
                                onChange={(event) => form.setData('username', event.target.value)}
                            />
                            {form.errors.username && <p className="-mt-2 text-sm text-danger">{form.errors.username}</p>}
                            <div>
                                <p className="mb-1 text-sm font-medium text-ink">Email</p>
                                <p className="text-sm text-muted">{profile.email}</p>
                                <p className="mt-1 text-xs text-muted">
                                    {profile.email_verified ? 'Verified' : 'Not verified'} · manage this in{' '}
                                    <Link href="/account/settings/security" className="font-medium text-brand hover:underline">
                                        Security
                                    </Link>
                                    .
                                </p>
                            </div>
                            <FormField
                                id="mobile"
                                label="Mobile"
                                value={form.data.mobile}
                                onChange={(event) => form.setData('mobile', event.target.value)}
                            />
                            {form.errors.mobile && <p className="-mt-2 text-sm text-danger">{form.errors.mobile}</p>}
                            <p className="-mt-2 text-xs text-muted">
                                {mobileRequired
                                    ? 'Mobile verification is required for hiring and recruiter accounts.'
                                    : 'Mobile verification is optional for job seekers. You can verify in Security settings.'}
                            </p>
                            {profile.whatsapp_number ? (
                                <div>
                                    <p className="mb-1 text-sm font-medium text-ink">WhatsApp</p>
                                    <p className="text-sm text-muted">{profile.whatsapp_number}</p>
                                    <p className="mt-1 text-xs text-muted">
                                        {profile.whatsapp_verified ? 'Verified' : 'Not verified'} · optional ·{' '}
                                        <Link href="/account/settings/security" className="font-medium text-brand hover:underline">
                                            Security
                                        </Link>
                                    </p>
                                </div>
                            ) : null}
                            <FormField
                                id="location"
                                label="Location"
                                value={form.data.location}
                                onChange={(event) => form.setData('location', event.target.value)}
                            />
                            {form.errors.location && <p className="-mt-2 text-sm text-danger">{form.errors.location}</p>}
                            <div>
                                <label htmlFor="preferred_language" className="mb-1 block text-sm font-medium text-ink">
                                    Language
                                </label>
                                <select
                                    id="preferred_language"
                                    className={selectClass}
                                    value={form.data.preferred_language}
                                    onChange={(event) => form.setData('preferred_language', event.target.value)}
                                >
                                    {languages.map((language) => (
                                        <option key={language.value} value={language.value}>
                                            {language.label}
                                        </option>
                                    ))}
                                </select>
                                {form.errors.preferred_language && (
                                    <p className="mt-1 text-sm text-danger">{form.errors.preferred_language}</p>
                                )}
                            </div>
                        </section>
                        </>
                        ) : null}

                        {showAboutSection ? (
                        <section className="space-y-4">
                            <h2 className="text-lg font-semibold text-ink">About</h2>
                            <FormField
                                id="professional_headline"
                                label="Professional headline"
                                maxLength={160}
                                value={form.data.professional_headline}
                                onChange={(event) => form.setData('professional_headline', event.target.value)}
                            />
                            {form.errors.professional_headline && (
                                <p className="-mt-2 text-sm text-danger">{form.errors.professional_headline}</p>
                            )}
                            <FormField
                                id="bio"
                                label="Bio"
                                as="textarea"
                                maxLength={1000}
                                value={form.data.bio}
                                onChange={(event) => form.setData('bio', event.target.value)}
                            />
                            {form.errors.bio && <p className="-mt-2 text-sm text-danger">{form.errors.bio}</p>}
                        </section>
                        ) : null}

                        {role === 'employer' && editing ? (
                            <section className="space-y-4">
                                <h2 className="text-lg font-semibold text-ink">Company logo</h2>
                                {profile.photo_url && !form.data.remove_photo ? (
                                    <img
                                        src={profile.photo_url}
                                        alt=""
                                        className="h-20 w-20 rounded-xl border border-line object-cover"
                                    />
                                ) : (
                                    <div className="flex h-20 w-20 items-center justify-center rounded-xl border border-line bg-surface text-sm text-muted">
                                        No logo
                                    </div>
                                )}
                                <input
                                    id="employer-logo"
                                    name="photo"
                                    type="file"
                                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                    className="w-full text-sm text-ink"
                                    onChange={(event) => {
                                        form.setData('photo', event.target.files?.[0] ?? null);
                                        form.setData('remove_photo', false);
                                    }}
                                />
                                <p className="text-xs text-muted">JPG, PNG, or WEBP. Maximum 5 MB.</p>
                                {form.errors.photo ? <p className="text-sm text-danger">{form.errors.photo}</p> : null}
                                {profile.photo_url ? (
                                    <button type="button" className="text-sm font-medium text-danger" onClick={removePhoto}>
                                        Remove logo
                                    </button>
                                ) : null}
                            </section>
                        ) : null}

                        {role === 'employer' ? (
                            <section className="space-y-4">
                                <h2 className="text-lg font-semibold text-ink">Company information</h2>
                                <p className="text-sm text-muted">Used on jobs, hiring, and your company identity across OurNear.</p>
                                <FormField
                                    id="company_name"
                                    label="Company name"
                                    value={form.data.company_name}
                                    onChange={(event) => form.setData('company_name', event.target.value)}
                                />
                                <FormField
                                    id="contact_person"
                                    label="Contact person"
                                    value={form.data.contact_person}
                                    onChange={(event) => form.setData('contact_person', event.target.value)}
                                />
                                <FormField
                                    id="mobile_number"
                                    label="Company mobile"
                                    value={form.data.mobile_number}
                                    onChange={(event) => form.setData('mobile_number', event.target.value)}
                                />
                                <FormField
                                    id="industry"
                                    label="Industry"
                                    value={form.data.industry}
                                    onChange={(event) => form.setData('industry', event.target.value)}
                                />
                                <FormField
                                    id="company_size"
                                    label="Company size"
                                    value={form.data.company_size}
                                    placeholder="For example, 51–200 employees"
                                    onChange={(event) => form.setData('company_size', event.target.value)}
                                />
                                <FormField
                                    id="company_website"
                                    label="Company website"
                                    type="url"
                                    value={form.data.company_website}
                                    placeholder="https://example.com"
                                    onChange={(event) => form.setData('company_website', event.target.value)}
                                />
                                {form.errors.company_website && <p className="-mt-2 text-sm text-danger">{form.errors.company_website}</p>}
                                <FormField
                                    id="about_company"
                                    label="About company"
                                    as="textarea"
                                    value={form.data.about_company}
                                    onChange={(event) => form.setData('about_company', event.target.value)}
                                />
                                <FormField
                                    id="company_details"
                                    label="Additional company details"
                                    as="textarea"
                                    value={form.data.company_details}
                                    onChange={(event) => form.setData('company_details', event.target.value)}
                                />
                                <p className="text-xs text-muted">
                                    Company documents are optional and are not published from this profile form. The
                                    company logo uses your profile photo.{' '}
                                    <Link href="/account/company-documents" className="font-medium text-brand">
                                        Manage company documents
                                    </Link>
                                </p>
                                <fieldset>
                                    <legend className="mb-2 text-sm font-medium text-ink">Company locations</legend>
                                    <div className="grid gap-2 sm:grid-cols-2">
                                        {(lookups?.locations ?? [])
                                            .filter(
                                                (location) =>
                                                    location.selectable !== false ||
                                                    form.data.location_ids.includes(location.id),
                                            )
                                            .map((location) => (
                                                <label key={location.id} className="flex items-center gap-2 text-sm text-ink">
                                                    <input
                                                        type="checkbox"
                                                        checked={form.data.location_ids.includes(location.id)}
                                                        onChange={() => {
                                                            const next = form.data.location_ids.includes(location.id)
                                                                ? form.data.location_ids.filter((id) => id !== location.id)
                                                                : [...form.data.location_ids, location.id];
                                                            form.setData('location_ids', next);
                                                        }}
                                                    />
                                                    {location.name}
                                                </label>
                                            ))}
                                    </div>
                                </fieldset>
                                <FormField
                                    id="benefits_text"
                                    label="Benefits"
                                    as="textarea"
                                    value={form.data.benefits_text}
                                    placeholder="One benefit per line"
                                    onChange={(event) => form.setData('benefits_text', event.target.value)}
                                />
                                {(lookups?.social_fields ?? []).map((field) => (
                                    <FormField
                                        key={field.key}
                                        id={`socials_${field.key}`}
                                        label={field.label}
                                        type="url"
                                        value={form.data.socials[field.key as keyof AccountCompanySocials] ?? ''}
                                        placeholder="https://"
                                        onChange={(event) =>
                                            form.setData('socials', {
                                                ...form.data.socials,
                                                [field.key]: event.target.value,
                                            })
                                        }
                                    />
                                ))}
                            </section>
                        ) : null}

                        <div className="flex flex-wrap gap-3 pb-[calc(0.5rem+env(safe-area-inset-bottom,0px))]">
                            <button
                                type="submit"
                                disabled={form.processing}
                                className="min-h-11 rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white hover:opacity-95 disabled:bg-disabled"
                            >
                                {form.processing ? 'Saving…' : 'Save'}
                            </button>
                            {role === 'candidate' ? (
                                <button
                                    type="button"
                                    className="min-h-11 rounded-md border border-line px-4 py-2.5 text-sm font-semibold text-ink"
                                    onClick={closeModule}
                                >
                                    Cancel
                                </button>
                            ) : (
                                <button
                                    type="button"
                                    className="min-h-11 rounded-md border border-line px-4 py-2.5 text-sm font-semibold text-ink"
                                    onClick={() => setEditing(false)}
                                >
                                    Cancel
                                </button>
                            )}
                        </div>
                    </form>
                    {role === 'candidate' && lookups && editingSection && editingSection !== 'about' && editingSection !== 'basic' && editingSection !== 'resume' ? (
                        <ProfileModuleEditorShell
                            title={activeModule?.title ?? 'Profile module'}
                            description={activeModule?.description}
                            onBack={closeModule}
                        >
                            <CandidateProfessionalSections
                                professional={profile.professional}
                                lookups={lookups}
                                focus={editingSection}
                                onCancel={closeModule}
                            />
                        </ProfileModuleEditorShell>
                    ) : null}
                </AccountLayout>
    );
}

function AccountStatusPanel({
    profile,
    mobileRequired,
}: {
    profile: AccountProfile;
    mobileRequired: boolean;
}) {
    const verifications = profile.verifications;

    return (
        <section className="rounded-xl border border-line bg-canvas/50 p-4 text-sm">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <h2 className="font-semibold text-ink">Account status</h2>
                <span className="rounded-full bg-white px-2.5 py-1 text-xs font-medium text-ink">
                    {profile.account_status_label}
                </span>
            </div>
            <p className="mt-2 text-muted">
                Profile completeness: {profile.profile_completion}% (from saved profile data).
            </p>
            <div className="mt-3 flex flex-wrap gap-2">
                {verifications?.email !== undefined ? (
                    <StatusPill
                        label="Email"
                        ok={verifications.email}
                        required={!verifications.email}
                    />
                ) : null}
                {verifications?.mobile !== undefined ? (
                    <StatusPill label="Mobile" ok={verifications.mobile} required={mobileRequired && !verifications.mobile} />
                ) : null}
                {verifications?.whatsapp !== undefined ? (
                    <StatusPill label="WhatsApp" ok={verifications.whatsapp} optional />
                ) : null}
            </div>
            <p className="mt-3 text-xs text-muted">
                Manage verification in{' '}
                <Link href="/account/settings/security" className="font-medium text-brand hover:underline">
                    Security settings
                </Link>
                .
            </p>
        </section>
    );
}

function StatusPill({
    label,
    ok,
    required,
    optional,
}: {
    label: string;
    ok: boolean;
    required?: boolean;
    optional?: boolean;
}) {
    if (ok) {
        return (
            <span className="rounded-full bg-brand/10 px-2.5 py-1 text-xs font-semibold text-brand">
                {label} verified
            </span>
        );
    }
    if (required) {
        return (
            <span className="rounded-full bg-accent/15 px-2.5 py-1 text-xs font-semibold text-accent">
                {label} required
            </span>
        );
    }
    if (optional) {
        return (
            <span className="rounded-full bg-surface px-2.5 py-1 text-xs font-semibold text-muted">
                {label} optional
            </span>
        );
    }
    return (
        <span className="rounded-full bg-surface px-2.5 py-1 text-xs font-semibold text-muted">
            {label} not verified
        </span>
    );
}

function CandidatePrivacySection({
    share,
    username,
    fields,
}: {
    share?: AccountPublicShare;
    username: string | null;
    fields: Array<{ key: string; label: string }>;
}) {
    const defaults: AccountPublicShare = {
        enabled: true,
        bio: false,
        headline: false,
        location: false,
        current_role: false,
        skills: false,
        history: false,
        education: false,
        projects: false,
        certifications: false,
        languages: false,
    };
    const form = useForm<AccountPublicShare>({ ...defaults, ...share });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put('/account/profile/privacy', { preserveScroll: true });
    };

    return (
        <form id="privacy" onSubmit={submit} className="mb-8 space-y-4 rounded-xl border border-line bg-white p-6">
            <h2 className="text-lg font-semibold text-ink">Public profile</h2>
            <p className="text-sm text-muted">
                When sharing is on, visitors see your name and photo on your public profile. Resume and document files
                are never published.
            </p>
            {username ? (
                <p className="text-sm text-ink">
                    Public profile link:{' '}
                    <a href={`/profiles/${username}`} className="font-medium text-brand">
                        View public profile
                    </a>
                </p>
            ) : (
                <p className="text-sm text-muted">Set a username in the form above to enable your public profile link.</p>
            )}
            <div className="space-y-2">
                {fields.map((field) => (
                    <label key={field.key} className="flex items-center gap-2 text-sm text-ink">
                        <input
                            type="checkbox"
                            checked={Boolean(form.data[field.key as keyof AccountPublicShare])}
                            onChange={(event) => form.setData(field.key as keyof AccountPublicShare, event.target.checked)}
                        />
                        {field.label}
                    </label>
                ))}
            </div>
            <button
                type="submit"
                disabled={form.processing}
                className="rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white hover:opacity-95 disabled:bg-disabled"
            >
                Save public profile settings
            </button>
        </form>
    );
}
