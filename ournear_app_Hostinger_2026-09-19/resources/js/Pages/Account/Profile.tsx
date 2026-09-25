import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import CandidateProfessionalSections from '@/Components/Account/CandidateProfessionalSections';
import SettingsNav from '@/Components/Account/SettingsNav';
import FormField from '@/Components/Public/FormField';
import PageContainer from '@/Components/Public/PageContainer';
import AccountLayout from '@/Layouts/AccountLayout';
import type { AccountLanguageOption, AccountProfile, AccountProfileLookups } from '@/types/account';
import type { SharedPageProps } from '@/types/page';

type ProfileForm = {
    name: string;
    username: string;
    mobile: string;
    location: string;
    bio: string;
    preferred_language: string;
    company_name: string;
    contact_person: string;
    mobile_number: string;
    industry: string;
    about_company: string;
    company_size: string;
    company_website: string;
    company_details: string;
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
    const form = useForm<ProfileForm>({
        name: profile.name,
        username: profile.username ?? '',
        mobile: profile.mobile ?? '',
        location: profile.location ?? '',
        bio: profile.bio ?? '',
        preferred_language: profile.preferred_language || 'en',
        company_name: profile.company_name ?? '',
        contact_person: profile.contact_person ?? '',
        mobile_number: profile.mobile_number ?? '',
        industry: profile.industry ?? '',
        about_company: profile.about_company ?? '',
        company_size: profile.company_size ?? '',
        company_website: profile.company_website ?? '',
        company_details: profile.company_details ?? '',
        photo: null,
        remove_photo: false,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put('/account/profile', {
            forceFormData: true,
        });
    };

    const removePhoto = () => {
        if (!window.confirm('Remove your profile photo?')) {
            return;
        }
        router.delete('/account/profile/photo');
    };

    const incomplete = profile.completion_items.filter((item) => !item.complete);

    return (
        <AccountLayout title="Profile">
            
                    <SettingsNav />
                    {flash.success && (
                        <p className="mb-4 rounded-md border border-success/20 bg-success/10 px-3 py-2 text-sm text-success">
                            {flash.success}
                        </p>
                    )}
                    <div className="mb-6 rounded-xl border border-line bg-white p-6">
                        <h2 className="text-lg font-semibold text-ink">Profile completion</h2>
                        <p className="mt-2 text-3xl font-semibold text-brand">{profile.profile_completion}%</p>
                        {incomplete.length > 0 ? (
                            <ul className="mt-3 list-disc space-y-1 pl-5 text-sm text-muted">
                                {incomplete.map((item) => (
                                    <li key={item.key}>{item.label} is incomplete</li>
                                ))}
                            </ul>
                        ) : (
                            <p className="mt-2 text-sm text-success">Your profile is complete.</p>
                        )}
                    </div>
                    <form onSubmit={submit} className="space-y-8 rounded-xl border border-line bg-white p-6">
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

                        <section className="space-y-4">
                            <h2 className="text-lg font-semibold text-ink">About</h2>
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

                        {role === 'employer' ? (
                            <section className="space-y-4">
                                <h2 className="text-lg font-semibold text-ink">Employer details</h2>
                                <p className="text-sm text-muted">These remain on your employer profile.</p>
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
                                    Company documents are optional and are not published from this profile form.
                                </p>
                            </section>
                        ) : null}

                        <button
                            type="submit"
                            disabled={form.processing}
                            className="rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white hover:opacity-95 disabled:bg-disabled"
                        >
                            Save profile
                        </button>
                    </form>
                    {role === 'candidate' && lookups ? (
                        <div id="professional" className="mt-8">
                            <CandidateProfessionalSections
                                professional={profile.professional}
                                lookups={lookups}
                            />
                            <div className="mt-8 rounded-xl border border-line bg-white p-6">
                                <h2 className="text-lg font-semibold text-ink">Resume and documents</h2>
                                <p className="mt-2 text-sm text-muted">
                                    Manage your resume library and private documents without changing application snapshots.
                                </p>
                                <div className="mt-4 flex flex-wrap gap-4 text-sm">
                                    <Link href="/account/resumes" className="font-medium text-brand">
                                        Resumes
                                    </Link>
                                    <Link href="/account/resumes/builder" className="font-medium text-brand">
                                        Resume builder
                                    </Link>
                                    <Link href="/account/documents" className="font-medium text-brand">
                                        Documents
                                    </Link>
                                </div>
                            </div>
                        </div>
                    ) : null}
                </AccountLayout>
    );
}
