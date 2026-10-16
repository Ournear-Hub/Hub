import { Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';
import CandidateProfessionalSections from '@/Components/Account/CandidateProfessionalSections';
import CandidateProfileOverview from '@/Components/Account/CandidateProfileOverview';
import EmployerCompanyProfileView from '@/Components/Account/EmployerCompanyProfileView';
import ProfileShareMenu from '@/Components/Profile/ProfileShareMenu';
import ProfileCompletionCenter from '@/Components/Profile/ProfileCompletionCenter';
import CandidateUnifiedProfileNav from '@/Components/Profile/CandidateUnifiedProfileNav';
import ProfileModuleContinueBar from '@/Components/Profile/ProfileModuleContinueBar';
import ProfileModuleEditorShell from '@/Components/Profile/ProfileModuleEditorShell';
import CompanyLogoUpload from '@/Components/Profile/CompanyLogoUpload';
import CandidatePersonalProfile, {
    type PersonalSectionId,
} from '@/Components/Profile/CandidatePersonalProfile';
import CandidateProfessionalProfile, {
    type ProfessionalEditTarget,
} from '@/Components/Profile/CandidateProfessionalProfile';
import CandidateProfessionalLanguages from '@/Components/Profile/CandidateProfessionalLanguages';
import CandidateJobPreferences from '@/Components/Profile/CandidateJobPreferences';
import {
    buildProfileModules,
    hashToModuleId,
    nextIncompleteModule,
    type ProfileModuleId,
} from '@/Components/Profile/profileCompletionModules';
import { BackNavButton } from '@/Components/molecules/BackNavLink';
import ProfileShell from '@/Components/Profile/ProfileShell';
import FormField from '@/Components/Public/FormField';
import AccountLayout from '@/Layouts/AccountLayout';
import RecruiterSettingsNav from '@/Components/Account/RecruiterSettingsNav';
import type {
    AccountCompanySocials,
    AccountLanguageOption,
    AccountProfile,
    AccountProfileLookups,
    AccountPublicShare,
} from '@/types/account';
import { useMobileProfileViewOnly } from '@/lib/mobileProfileViewOnly';
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
    whatsapp_number: string;
    industry: string;
    industry_id: string;
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
    companyWorkspace?: boolean;
    profile: AccountProfile;
    languages: AccountLanguageOption[];
    lookups?: AccountProfileLookups;
};

const selectClass =
    'w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand';

const PROFESSIONAL_PROFILE_MODULES: ProfileModuleId[] = [
    'professional',
    'experience',
    'education',
    'skills',
    'projects',
    'certifications',
];

function resolvePickedPhoto(formElement: HTMLFormElement, formPhoto: File | null): File | null {
    if (formPhoto instanceof File) {
        return formPhoto;
    }

    const nativePhoto = new FormData(formElement).get('photo');
    if (nativePhoto instanceof File && nativePhoto.size > 0) {
        return nativePhoto;
    }

    const photoInput = formElement.querySelector('#photo') as HTMLInputElement | null;
    const fromInput = photoInput?.files?.[0] ?? null;

    return fromInput instanceof File && fromInput.size > 0 ? fromInput : null;
}

function buildProfileSubmitFormData(
    formElement: HTMLFormElement,
    data: ProfileForm,
    role: ProfileProps['role'],
): FormData {
    if (role === 'employer') {
        const fd = new FormData();
        fd.set('_company_profile', '1');
        fd.set('company_name', data.company_name);
        fd.set('contact_person', data.contact_person);
        fd.set('mobile_number', data.mobile_number);
        fd.set('whatsapp_number', data.whatsapp_number);
        fd.set('industry_id', data.industry_id);
        fd.set('about_company', data.about_company);
        fd.set('company_size', data.company_size);
        fd.set('company_website', data.company_website);
        fd.set('company_details', data.company_details);
        data.location_ids.forEach((locationId) => fd.append('location_ids[]', String(locationId)));
        data.benefits_text
            .split('\n')
            .map((item) => item.trim())
            .filter(Boolean)
            .forEach((benefit) => fd.append('benefits[]', benefit));

        return fd;
    }

    const fd = new FormData(formElement);
    fd.delete('benefits_text');
    fd.delete('photo');

    fd.set('name', data.name);
    fd.set('username', data.username);
    fd.set('mobile', data.mobile);
    fd.set('location', data.location);
    fd.set('bio', data.bio);
    fd.set('professional_headline', data.professional_headline);
    fd.set('preferred_language', data.preferred_language);
    fd.set('remove_photo', data.remove_photo ? '1' : '0');

    data.benefits_text
        .split('\n')
        .map((item) => item.trim())
        .filter(Boolean)
        .forEach((benefit) => fd.append('benefits[]', benefit));

    const pickedPhoto = resolvePickedPhoto(formElement, data.photo);
    if (pickedPhoto instanceof File) {
        fd.set('photo', pickedPhoto, pickedPhoto.name);
    }

    return fd;
}

export default function Profile({ role, companyWorkspace = false, profile, languages, lookups }: ProfileProps) {
    const inertiaPage = usePage<SharedPageProps>();
    const flash = inertiaPage.props.flash;
    const pageUrl = inertiaPage.url;
    const mobileViewOnly = useMobileProfileViewOnly();
    const [editing, setEditing] = useState(false);
    const [editingSection, setEditingSection] = useState<ProfileModuleId | null>(null);
    const [personalView, setPersonalView] = useState(false);
    const [professionalView, setProfessionalView] = useState(false);
    const [languagesView, setLanguagesView] = useState(false);
    const [preferencesView, setPreferencesView] = useState(false);
    const [personalEditSection, setPersonalEditSection] = useState<PersonalSectionId | null>(null);
    const [savingProfile, setSavingProfile] = useState(false);
    const mobileRequired = profile.verification_context?.mobile_required === true;
    const modules = role === 'candidate' ? buildProfileModules(profile) : [];
    const nextModuleAfterSave =
        role === 'candidate' && editingSection ? nextIncompleteModule(modules, editingSection) : null;

    useEffect(() => {
        if (!mobileViewOnly) {
            return;
        }
        setEditing(false);
        setEditingSection(null);
        setPersonalEditSection(null);
    }, [mobileViewOnly]);

    useEffect(() => {
        const syncFromHash = () => {
            const hashFromWindow = window.location.hash.replace('#', '');
            const hashFromPage = pageUrl.includes('#') ? (pageUrl.split('#')[1]?.split('?')[0] ?? '') : '';
            const hash = hashFromWindow || hashFromPage;
            if (hash === 'privacy') {
                setEditingSection(null);
                setPersonalView(false);
                setProfessionalView(false);
                setLanguagesView(false);
                setPreferencesView(false);
                window.requestAnimationFrame(() => {
                    document.getElementById('privacy')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
                return;
            }
            if (role !== 'candidate') {
                return;
            }
            if (hash === 'preferences') {
                setPreferencesView(true);
                setPersonalView(false);
                setProfessionalView(false);
                setLanguagesView(false);
                setPersonalEditSection(null);
                setEditing(false);
                setEditingSection(null);
                return;
            }
            if (hash === 'personal') {
                setPersonalView(true);
                setProfessionalView(false);
                setLanguagesView(false);
                setPreferencesView(false);
                setEditingSection(null);
                setPersonalEditSection(null);
                return;
            }
            if (hash === 'professional') {
                setPersonalView(false);
                setProfessionalView(true);
                setLanguagesView(false);
                setPreferencesView(false);
                setPersonalEditSection(null);
                setEditing(false);
                setEditingSection(null);
                return;
            }
            if (hash === 'languages') {
                setPersonalView(false);
                setProfessionalView(false);
                setLanguagesView(true);
                setPreferencesView(false);
                setPersonalEditSection(null);
                setEditing(false);
                setEditingSection(null);
                return;
            }
            const moduleId = hashToModuleId(hash);
            if (moduleId) {
                if (moduleId === 'basic') {
                    setPersonalView(true);
                    setProfessionalView(false);
                    setLanguagesView(false);
                    setPreferencesView(false);
                    setPersonalEditSection('basic');
                    setEditingSection(null);
                    return;
                }
                if (moduleId === 'languages') {
                    setPersonalView(false);
                    setProfessionalView(false);
                    setLanguagesView(true);
                    setPreferencesView(false);
                    setPersonalEditSection(null);
                    setEditing(false);
                    setEditingSection(null);
                    return;
                }
                if (moduleId === 'preferences') {
                    setPreferencesView(true);
                    setPersonalView(false);
                    setProfessionalView(false);
                    setLanguagesView(false);
                    setPersonalEditSection(null);
                    setEditing(false);
                    setEditingSection(null);
                    return;
                }
                setPersonalView(false);
                setLanguagesView(false);
                setPreferencesView(false);
                setPersonalEditSection(null);
                setEditing(false);
                if (PROFESSIONAL_PROFILE_MODULES.includes(moduleId)) {
                    setProfessionalView(true);
                } else if (moduleId !== 'about') {
                    setProfessionalView(false);
                }
                setEditingSection(moduleId);
            } else if (hash === '') {
                setPersonalView(false);
                setProfessionalView(false);
                setLanguagesView(false);
                setPreferencesView(false);
                setPersonalEditSection(null);
            }
        };

        syncFromHash();
        window.addEventListener('hashchange', syncFromHash);
        window.addEventListener('popstate', syncFromHash);
        return () => {
            window.removeEventListener('hashchange', syncFromHash);
            window.removeEventListener('popstate', syncFromHash);
        };
    }, [role, pageUrl]);

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
        whatsapp_number: profile.whatsapp_number ?? '',
        industry: profile.industry ?? '',
        industry_id: profile.industry_id ? String(profile.industry_id) : '',
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

    const candidateHub =
        role === 'candidate' &&
        editingSection === null &&
        !personalView &&
        !professionalView &&
        !languagesView &&
        !preferencesView;

    const syncFormFromProfile = () => {
        form.setData({
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
            whatsapp_number: profile.whatsapp_number ?? '',
            industry: profile.industry ?? '',
            industry_id: profile.industry_id ? String(profile.industry_id) : '',
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
        form.clearErrors();
    };

    useEffect(() => {
        if (role === 'employer' && !editing && !savingProfile) {
            syncFormFromProfile();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps -- sync when server profile props refresh after save
    }, [profile, role, editing, savingProfile]);

    const openPersonalView = (editSection: PersonalSectionId | null = null) => {
        setEditing(false);
        setEditingSection(null);
        setProfessionalView(false);
        setLanguagesView(false);
        setPreferencesView(false);
        setPersonalView(true);
        setPersonalEditSection(mobileViewOnly ? null : editSection);
        syncFormFromProfile();
        window.history.replaceState(null, '', '#personal');
    };

    const openProfessionalView = () => {
        setEditing(false);
        setEditingSection(null);
        setPersonalView(false);
        setPersonalEditSection(null);
        setLanguagesView(false);
        setPreferencesView(false);
        setProfessionalView(true);
        window.history.replaceState(null, '', '#professional');
    };

    const openLanguagesView = () => {
        setEditing(false);
        setEditingSection(null);
        setPersonalView(false);
        setPersonalEditSection(null);
        setProfessionalView(false);
        setPreferencesView(false);
        setLanguagesView(true);
        window.history.replaceState(null, '', '#languages');
    };

    const openPreferencesView = () => {
        setEditing(false);
        setEditingSection(null);
        setPersonalView(false);
        setPersonalEditSection(null);
        setProfessionalView(false);
        setLanguagesView(false);
        setPreferencesView(true);
        window.history.replaceState(null, '', '#preferences');
    };

    const openProfessionalSection = (section: ProfessionalEditTarget) => {
        setEditing(false);
        setPersonalView(false);
        setPersonalEditSection(null);
        setProfessionalView(true);
        setEditingSection(section);
        if (section === 'professional') {
            if (window.location.hash !== '#professional') {
                window.history.replaceState(null, '', '#professional');
            }
            return;
        }
        window.history.replaceState(null, '', `#${section}`);
    };

    const closePersonalSection = () => {
        setPersonalEditSection(null);
        syncFormFromProfile();
    };

    const openModule = (section: ProfileModuleId) => {
        if (mobileViewOnly) {
            return;
        }
        if (section === 'basic') {
            openPersonalView('basic');
            return;
        }
        if (section === 'languages') {
            openLanguagesView();
            return;
        }
        if (section === 'preferences') {
            openPreferencesView();
            return;
        }
        setPersonalView(false);
        setLanguagesView(false);
        setPreferencesView(false);
        setPersonalEditSection(null);
        setEditing(false);
        if (PROFESSIONAL_PROFILE_MODULES.includes(section)) {
            setProfessionalView(true);
            setEditingSection(section);
            if (section === 'professional') {
                window.history.replaceState(null, '', '#professional');
                return;
            }
        } else if (section !== 'about') {
            setProfessionalView(false);
        }
        setEditingSection(section);
        window.history.replaceState(null, '', `#${section}`);
    };

    const closeModule = () => {
        setEditingSection(null);
        syncFormFromProfile();
        if (personalView) {
            window.history.replaceState(null, '', '#personal');
            return;
        }
        if (professionalView) {
            window.history.replaceState(null, '', '#professional');
            return;
        }
        if (languagesView) {
            window.history.replaceState(null, '', '#languages');
            return;
        }
        if (preferencesView) {
            window.history.replaceState(null, '', '#preferences');
            return;
        }
        window.history.replaceState(null, '', window.location.pathname);
    };

    const submitPersonalSection = (_section: PersonalSectionId) => {
        const fd = new FormData();
        fd.set('name', form.data.name);
        fd.set('username', form.data.username);
        fd.set('preferred_language', form.data.preferred_language);
        fd.set('mobile', form.data.mobile);
        fd.set('location', form.data.location);
        fd.set('remove_photo', '0');

        form.clearErrors();
        setSavingProfile(true);
        router.post('/account/profile', fd, {
            preserveScroll: true,
            onSuccess: (page) => {
                const serverErrors = page.props.errors ?? {};
                if (Object.keys(serverErrors).length > 0) {
                    Object.entries(serverErrors).forEach(([key, message]) => {
                        form.setError(key as keyof ProfileForm, String(message));
                    });
                    return;
                }
                closePersonalSection();
            },
            onError: (errors) => {
                Object.entries(errors).forEach(([key, message]) => {
                    form.setError(key as keyof ProfileForm, message);
                });
            },
            onFinish: () => {
                setSavingProfile(false);
            },
        });
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const formElement = event.currentTarget;
        const payload = buildProfileSubmitFormData(formElement, form.data, role);

        form.clearErrors();
        setSavingProfile(true);
        router.post('/account/profile', payload, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: (page) => {
                const serverErrors = page.props.errors ?? {};
                if (Object.keys(serverErrors).length > 0) {
                    Object.entries(serverErrors).forEach(([key, message]) => {
                        form.setError(key as keyof ProfileForm, String(message));
                    });
                    if (role === 'employer') {
                        setEditing(true);
                    }
                    return;
                }

                form.setData('photo', null);
                if (role === 'candidate' && (editingSection === 'basic' || editingSection === 'about')) {
                    if (professionalView && editingSection === 'about') {
                        setEditingSection(null);
                    }
                    return;
                }
                if (role === 'candidate') {
                    setEditingSection(null);
                }
                if (role === 'employer') {
                    setEditing(false);
                    const saved = (page.props as unknown as ProfileProps).profile;
                    form.setData({
                        ...form.data,
                        company_name: saved.company_name ?? '',
                        contact_person: saved.contact_person ?? '',
                        mobile_number: saved.mobile_number ?? '',
                        whatsapp_number: saved.whatsapp_number ?? '',
                        industry: saved.industry ?? '',
                        industry_id: saved.industry_id ? String(saved.industry_id) : '',
                        about_company: saved.about_company ?? '',
                        company_size: saved.company_size ?? '',
                        company_website: saved.company_website ?? '',
                        company_details: saved.company_details ?? '',
                        location_ids: saved.location_ids ?? [],
                        benefits_text: (saved.benefits ?? []).join('\n'),
                        socials: {
                            linkedin: saved.socials?.linkedin ?? '',
                            facebook: saved.socials?.facebook ?? '',
                            twitter: saved.socials?.twitter ?? '',
                            instagram: saved.socials?.instagram ?? '',
                            youtube: saved.socials?.youtube ?? '',
                        },
                        photo: null,
                        remove_photo: false,
                    });
                }
            },
            onError: (errors) => {
                Object.entries(errors).forEach(([key, message]) => {
                    form.setError(key as keyof ProfileForm, message);
                });
                if (role === 'employer') {
                    setEditing(true);
                }
            },
            onFinish: () => {
                setSavingProfile(false);
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
    const hideProfileForm = mobileViewOnly
        ? true
        : role === 'candidate'
          ? personalView
              ? true
              : professionalView
                ? editingSection !== 'about'
                : !(editingSection === 'basic' || editingSection === 'about')
          : !editing;
    const activeModule = modules.find((module) => module.id === editingSection);
    const profileNavSection =
        role === 'candidate'
            ? preferencesView
                ? 'preferences'
                : languagesView
                  ? 'languages'
                  : personalView || editingSection === 'basic' || (editingSection === 'about' && !professionalView)
                    ? 'personal'
                    : professionalView ||
                        PROFESSIONAL_PROFILE_MODULES.includes(editingSection as ProfileModuleId) ||
                        (editingSection === 'about' && professionalView)
                      ? 'professional'
                      : candidateHub
                        ? 'hub'
                        : 'hub'
            : 'hub';
    const publicShareEnabled = profile.public_profile_share_enabled !== false;

    return (
        <AccountLayout
            hideSidebarOurnearId={role === 'candidate'}
            title={
                role === 'employer'
                    ? 'Company Profile'
                    : personalView
                      ? 'Personal Profile'
                      : preferencesView
                        ? 'Job Preferences'
                        : languagesView
                          ? 'Languages'
                          : professionalView
                            ? 'Professional Profile'
                            : 'Profile'
            }
            breadcrumbs={
                role === 'employer'
                    ? [
                          { label: 'Hiring', href: '/account/hiring' },
                          { label: 'Company Profile', href: companyWorkspace ? '/account/company' : '/account/profile' },
                      ]
                    : [
                          { label: 'Personal Dashboard', href: '/account' },
                          {
                              label: personalView
                                  ? 'Personal Profile'
                                  : preferencesView
                                    ? 'Job Preferences'
                                    : languagesView
                                      ? 'Languages'
                                      : professionalView
                                        ? 'Professional Profile'
                                        : 'Profile',
                          },
                      ]
            }
        >
            {role === 'employer' ? <RecruiterSettingsNav /> : null}
                    {role === 'candidate' ? (
                        <>
                            <CandidateUnifiedProfileNav
                                active={profileNavSection}
                                publicProfileUrl={profile.public_profile_url}
                                shareEnabled={publicShareEnabled}
                                onOpenPersonal={() => openPersonalView()}
                                onOpenProfessional={() => openProfessionalView()}
                                onOpenOverview={() => {
                                    setPersonalView(false);
                                    setProfessionalView(false);
                                    setLanguagesView(false);
                                    setPreferencesView(false);
                                    setPersonalEditSection(null);
                                    setEditingSection(null);
                                    window.history.replaceState(null, '', window.location.pathname);
                                }}
                            />
                            {!publicShareEnabled ? (
                                <p className="mb-4 rounded-lg border border-line bg-surface px-4 py-3 text-sm text-muted">
                                    Public profile sharing is <span className="font-semibold text-ink">off</span>. You
                                    can still preview your profile; employers only see it when sharing is enabled under
                                    Privacy.
                                </p>
                            ) : null}
                        </>
                    ) : null}
                    {flash.success && (
                        <p className="mb-4 rounded-md border border-success/20 bg-success/10 px-3 py-2 text-sm text-success">
                            {flash.success}
                        </p>
                    )}
                    {flash.error ? (
                        <p className="mb-4 rounded-md border border-danger/20 bg-danger/10 px-3 py-2 text-sm text-danger">
                            {flash.error}
                        </p>
                    ) : null}
                    {mobileViewOnly ? (
                        <p className="mb-4 rounded-lg border border-line bg-surface px-4 py-3 text-sm text-muted">
                            You are viewing this profile on a mobile browser. Editing is available on desktop; job
                            posting and application review remain available here.
                        </p>
                    ) : null}
                    {role === 'candidate' && personalView ? (
                        <CandidatePersonalProfile
                            profile={profile}
                            languages={languages}
                            form={{
                                name: form.data.name,
                                username: form.data.username,
                                mobile: form.data.mobile,
                                location: form.data.location,
                                preferred_language: form.data.preferred_language,
                            }}
                            formErrors={{
                                name: form.errors.name,
                                username: form.errors.username,
                                mobile: form.errors.mobile,
                                location: form.errors.location,
                                preferred_language: form.errors.preferred_language,
                                photo: form.errors.photo,
                            }}
                            editingSection={personalEditSection}
                            saving={savingProfile || form.processing}
                            onEditSection={(section) => {
                                syncFormFromProfile();
                                setPersonalEditSection(section);
                            }}
                            onCancelSection={closePersonalSection}
                            onChange={(patch) => form.setData({ ...form.data, ...patch })}
                            onSaveSection={submitPersonalSection}
                            viewOnly={mobileViewOnly}
                        />
                    ) : null}
                    {role === 'candidate' && professionalView && lookups && !editingSection ? (
                        <CandidateProfessionalProfile
                            profile={profile}
                            lookups={lookups}
                            onEditSection={openProfessionalSection}
                            viewOnly={mobileViewOnly}
                        />
                    ) : null}
                    {role === 'candidate' && languagesView && lookups ? (
                        <CandidateProfessionalLanguages
                            languages={profile.professional?.languages ?? []}
                            proficiencies={lookups.language_proficiencies ?? []}
                        />
                    ) : null}
                    {role === 'candidate' && preferencesView && lookups ? (
                        <CandidateJobPreferences professional={profile.professional} lookups={lookups} />
                    ) : null}
                    {!personalView && !professionalView && !languagesView && !preferencesView ? (
                    <ProfileShell
                        name={
                            role === 'employer'
                                ? (profile.company_name?.trim() || 'Company workspace')
                                : profile.name
                        }
                        photoUrl={role === 'employer' ? (profile.logo_url ?? null) : profile.photo_url}
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
                                        <Link
                                            href={profile.public_profile_url}
                                            className="inline-flex min-h-11 items-center rounded-md border border-line px-4 py-2 text-sm font-semibold text-brand hover:bg-surface"
                                        >
                                            View profile
                                        </Link>
                                        <ProfileShareMenu profileUrl={profile.public_profile_url} profileName={profile.name} />
                                    </>
                                ) : null}
                                {!mobileViewOnly ? (
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
                                ) : null}
                            </>
                        }
                        tabs={undefined}
                    >
                        {role === 'candidate' && candidateHub ? (
                            <>
                                <ProfileCompletionCenter
                                    profile={profile}
                                    onOpenModule={mobileViewOnly ? () => {} : openModule}
                                />
                                <CandidateProfileOverview
                                    profile={profile}
                                    onEdit={mobileViewOnly ? () => {} : openModule}
                                />
                            </>
                        ) : null}
                        {role === 'employer' && editing && !mobileViewOnly ? (
                            <p className="text-sm text-muted">Update company information used across hiring, jobs, and your employer identity.</p>
                        ) : null}
                    </ProfileShell>
                    ) : null}
                    {role === 'employer' && !editing ? <EmployerCompanyProfileView profile={profile} /> : null}
                    {role === 'candidate' && candidateHub && lookups && !mobileViewOnly ? (
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
                        className={`${hideProfileForm ? 'hidden' : ''} space-y-5 rounded-xl border border-line bg-white p-4 sm:p-5`}
                    >
                        {role === 'candidate' &&
                        (editingSection === 'basic' || editingSection === 'about') &&
                        !(professionalView && editingSection === 'about') ? (
                            <div className="mb-2 flex flex-wrap items-center gap-3 border-b border-line pb-4">
                                <BackNavButton label="Back" onClick={closeModule} />
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
                        {role === 'candidate' && professionalView && editingSection === 'about' ? (
                            <div className="mb-2 flex flex-wrap items-center gap-3 border-b border-line pb-4">
                                <BackNavButton label="Back" onClick={closeModule} />
                                <div>
                                    <p className="text-lg font-semibold text-ink">Professional summary</p>
                                    <p className="text-sm text-muted">Headline and bio shown on your public profile.</p>
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
                                    <Link href="/account/settings/verification" className="font-medium text-brand hover:underline">
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

                        {role === 'employer' && editing && !mobileViewOnly ? (
                            <section className="space-y-4">
                                <h2 className="text-lg font-semibold text-ink">Company logo</h2>
                                <CompanyLogoUpload
                                    companyName={form.data.company_name || profile.company_name || 'Company'}
                                    logoUrl={profile.logo_url ?? null}
                                />
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
                                    id="whatsapp_number"
                                    label="WhatsApp number"
                                    value={form.data.whatsapp_number}
                                    placeholder="Optional company WhatsApp contact"
                                    onChange={(event) => form.setData('whatsapp_number', event.target.value)}
                                />
                                {form.errors.whatsapp_number ? (
                                    <p className="-mt-2 text-sm text-danger">{form.errors.whatsapp_number}</p>
                                ) : null}
                                <div>
                                    <label htmlFor="industry_id" className="mb-1 block text-sm font-medium text-ink">
                                        Industry
                                    </label>
                                    <select
                                        id="industry_id"
                                        className={selectClass}
                                        value={form.data.industry_id}
                                        onChange={(event) => form.setData('industry_id', event.target.value)}
                                    >
                                        <option value="">Select industry</option>
                                        {(lookups?.industries ?? [])
                                            .filter(
                                                (industry) =>
                                                    industry.selectable !== false ||
                                                    String(industry.id) === form.data.industry_id,
                                            )
                                            .map((industry) => (
                                                <option key={industry.id} value={industry.id}>
                                                    {industry.name}
                                                </option>
                                            ))}
                                    </select>
                                    {form.errors.industry_id && (
                                        <p className="mt-1 text-sm text-danger">{form.errors.industry_id}</p>
                                    )}
                                </div>
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
                                    value={form.data.company_website}
                                    placeholder="example.com or https://example.com"
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
                                    Company documents are optional and are not published from this profile form. Upload
                                    your company logo in the section above (separate from your personal profile photo).{' '}
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
                                <fieldset className="space-y-3 rounded-lg border border-dashed border-line bg-surface/60 p-4">
                                    <legend className="px-1 text-sm font-semibold text-ink">Social media links</legend>
                                    <p className="text-xs text-muted">
                                        Editing social links is temporarily disabled while we improve validation. Saved links
                                        remain visible on your public company page when sharing allows.
                                    </p>
                                    {(lookups?.social_fields ?? []).map((field) => {
                                        const value =
                                            form.data.socials[field.key as keyof AccountCompanySocials] ?? '';
                                        return (
                                            <FormField
                                                key={field.key}
                                                id={`socials_${field.key}`}
                                                label={field.label}
                                                type="url"
                                                value={value}
                                                placeholder="https://"
                                                disabled
                                                readOnly
                                                className="opacity-80"
                                            />
                                        );
                                    })}
                                </fieldset>
                            </section>
                        ) : null}

                        <div className="flex flex-wrap gap-3 pb-[calc(0.5rem+env(safe-area-inset-bottom,0px))]">
                            <button
                                type="submit"
                                disabled={form.processing || savingProfile}
                                className="min-h-11 rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white hover:opacity-95 disabled:bg-disabled"
                            >
                                {form.processing || savingProfile ? 'Saving…' : 'Save'}
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
                    {role === 'candidate' &&
                    lookups &&
                    editingSection &&
                    editingSection !== 'about' &&
                    editingSection !== 'basic' &&
                    editingSection !== 'resume' &&
                    editingSection !== 'languages' ? (
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
                <Link href="/account/settings/verification" className="font-medium text-brand hover:underline">
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
        <form id="privacy" onSubmit={submit} className="mb-6 space-y-3 rounded-xl border border-line bg-white p-4 sm:p-5">
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
