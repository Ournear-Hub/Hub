import { Link, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';

type WebsiteSettings = {
    company_name: string;
    office_phone: string;
    office_email: string;
    website_url: string;
    office_address: string;
    copyright_text: string;
    default_seo_title: string;
    default_meta_description: string;
    notification_emails: string;
};

type BrandingSettings = {
    website_name: string;
    logo_url: string | null;
    icon_url: string | null;
    favicon_url: string | null;
};

type Props = {
    tab?: 'website' | 'branding';
    websiteSettings: WebsiteSettings;
    branding: BrandingSettings;
};

const inputClass =
    'w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand';

export default function Settings({ tab = 'website', websiteSettings, branding }: Props) {
    const websiteForm = useForm(websiteSettings);
    const brandingForm = useForm({
        website_name: branding.website_name,
        logo: null as File | null,
        icon: null as File | null,
        favicon: null as File | null,
        remove_logo: false,
        remove_icon: false,
        remove_favicon: false,
    });

    const openTab = (next: 'website' | 'branding') => {
        router.get('/admin/settings', { tab: next }, { preserveState: false });
    };

    const submitWebsite = (event: FormEvent) => {
        event.preventDefault();
        websiteForm.put('/admin/settings/website');
    };

    const submitBranding = (event: FormEvent) => {
        event.preventDefault();
        brandingForm.put('/admin/settings/branding', { forceFormData: true });
    };

    return (
        <AdminLayout title="Website Settings">
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight text-ink">Website Settings</h1>
                    <p className="mt-1 text-sm text-muted">
                        Website name, company details, logo, and public branding. Admin profile is managed separately.
                    </p>
                </div>

                <div className="flex flex-wrap gap-2 border-b border-line pb-3">
                    <button
                        type="button"
                        onClick={() => openTab('website')}
                        className={`rounded-md px-4 py-2 text-sm font-semibold ${
                            tab === 'website' ? 'bg-brand text-white' : 'bg-white text-ink ring-1 ring-line'
                        }`}
                    >
                        Website
                    </button>
                    <button
                        type="button"
                        onClick={() => openTab('branding')}
                        className={`rounded-md px-4 py-2 text-sm font-semibold ${
                            tab === 'branding' ? 'bg-brand text-white' : 'bg-white text-ink ring-1 ring-line'
                        }`}
                    >
                        Branding
                    </button>
                    <Link
                        href="/admin/profile"
                        className="rounded-md px-4 py-2 text-sm font-semibold text-ink ring-1 ring-line hover:bg-surface"
                    >
                        Admin profile
                    </Link>
                </div>

                {tab === 'website' ? (
                    <section className="rounded-xl border border-line bg-white p-6">
                        <h2 className="text-lg font-semibold text-ink">Website settings</h2>
                        <p className="mt-1 text-sm text-muted">
                            These values are used in the footer, contact details, and default search listing text.
                        </p>
                        <form onSubmit={submitWebsite} className="mt-4 max-w-lg space-y-4">
                            <Field
                                label="Company / site name"
                                value={websiteForm.data.company_name}
                                onChange={(value) => websiteForm.setData('company_name', value)}
                                error={websiteForm.errors.company_name}
                            />
                            <Field
                                label="Office phone"
                                value={websiteForm.data.office_phone}
                                onChange={(value) => websiteForm.setData('office_phone', value)}
                                error={websiteForm.errors.office_phone}
                            />
                            <Field
                                label="Office email"
                                value={websiteForm.data.office_email}
                                onChange={(value) => websiteForm.setData('office_email', value)}
                                error={websiteForm.errors.office_email}
                            />
                            <Field
                                label="Website URL"
                                value={websiteForm.data.website_url}
                                onChange={(value) => websiteForm.setData('website_url', value)}
                                error={websiteForm.errors.website_url}
                                hint="Public https address of this website."
                            />
                            <Field
                                label="Office address"
                                value={websiteForm.data.office_address}
                                onChange={(value) => websiteForm.setData('office_address', value)}
                                error={websiteForm.errors.office_address}
                                multiline
                            />
                            <Field
                                label="Footer copyright"
                                value={websiteForm.data.copyright_text}
                                onChange={(value) => websiteForm.setData('copyright_text', value)}
                                error={websiteForm.errors.copyright_text}
                                hint="Use {year} to insert the current year."
                            />
                            <Field
                                label="Default SEO title"
                                value={websiteForm.data.default_seo_title}
                                onChange={(value) => websiteForm.setData('default_seo_title', value)}
                                error={websiteForm.errors.default_seo_title}
                            />
                            <Field
                                label="Default meta description"
                                value={websiteForm.data.default_meta_description}
                                onChange={(value) => websiteForm.setData('default_meta_description', value)}
                                error={websiteForm.errors.default_meta_description}
                                multiline
                            />
                            <Field
                                label="Notification emails"
                                value={websiteForm.data.notification_emails}
                                onChange={(value) => websiteForm.setData('notification_emails', value)}
                                error={websiteForm.errors.notification_emails}
                                multiline
                                hint="One email per line. These addresses are notified when an application or enquiry is submitted."
                            />
                            <button
                                type="submit"
                                disabled={websiteForm.processing}
                                className="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white disabled:bg-disabled"
                            >
                                Save website settings
                            </button>
                        </form>
                    </section>
                ) : (
                    <section className="rounded-xl border border-line bg-white p-6">
                        <h2 className="text-lg font-semibold text-ink">Branding</h2>
                        <p className="mt-1 text-sm text-muted">
                            These assets appear on the public website. Logo and website name are independent.
                        </p>
                        <form onSubmit={submitBranding} className="mt-4 max-w-lg space-y-6">
                            <div>
                                <label htmlFor="website_name" className="mb-1 block text-sm font-medium text-ink">
                                    Website name
                                </label>
                                <input
                                    id="website_name"
                                    value={brandingForm.data.website_name}
                                    className={inputClass}
                                    onChange={(event) => brandingForm.setData('website_name', event.target.value)}
                                />
                                {brandingForm.errors.website_name && (
                                    <p className="mt-1 text-sm text-danger">{brandingForm.errors.website_name}</p>
                                )}
                            </div>
                            <BrandingAssetField
                                label="Horizontal logo"
                                hint="Used in the public header and footer. PNG, JPG, or WEBP, max 5 MB."
                                currentUrl={branding.logo_url}
                                alt={`${branding.website_name} logo`}
                                error={brandingForm.errors.logo}
                                onFile={(file) => brandingForm.setData('logo', file)}
                                removeChecked={brandingForm.data.remove_logo}
                                onRemoveChange={(checked) => brandingForm.setData('remove_logo', checked)}
                                canRemove={Boolean(branding.logo_url)}
                            />
                            <BrandingAssetField
                                label="Logo icon"
                                hint="Used where an icon-only mark is needed. PNG, JPG, or WEBP, max 5 MB."
                                currentUrl={branding.icon_url}
                                alt={`${branding.website_name} icon`}
                                error={brandingForm.errors.icon}
                                onFile={(file) => brandingForm.setData('icon', file)}
                                removeChecked={brandingForm.data.remove_icon}
                                onRemoveChange={(checked) => brandingForm.setData('remove_icon', checked)}
                                canRemove={Boolean(branding.icon_url)}
                            />
                            <BrandingAssetField
                                label="Favicon"
                                hint="Browser tab icon. PNG, JPG, or WEBP, max 5 MB."
                                currentUrl={branding.favicon_url}
                                alt={`${branding.website_name} favicon`}
                                error={brandingForm.errors.favicon}
                                onFile={(file) => brandingForm.setData('favicon', file)}
                                removeChecked={brandingForm.data.remove_favicon}
                                onRemoveChange={(checked) => brandingForm.setData('remove_favicon', checked)}
                                canRemove={Boolean(branding.favicon_url)}
                            />
                            <button
                                type="submit"
                                disabled={brandingForm.processing}
                                className="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white disabled:bg-disabled"
                            >
                                Save branding
                            </button>
                        </form>
                    </section>
                )}
            </div>
        </AdminLayout>
    );
}

function Field({
    label,
    value,
    onChange,
    error,
    hint,
    multiline = false,
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;
    hint?: string;
    multiline?: boolean;
}) {
    return (
        <div>
            <label className="mb-1 block text-sm font-medium text-ink">{label}</label>
            {multiline ? (
                <textarea className={`${inputClass} min-h-24`} value={value} onChange={(event) => onChange(event.target.value)} />
            ) : (
                <input className={inputClass} value={value} onChange={(event) => onChange(event.target.value)} />
            )}
            {hint ? <p className="mt-1 text-xs text-muted">{hint}</p> : null}
            {error ? <p className="mt-1 text-sm text-danger">{error}</p> : null}
        </div>
    );
}

function BrandingAssetField({
    label,
    hint,
    currentUrl,
    alt,
    error,
    onFile,
    removeChecked,
    onRemoveChange,
    canRemove,
}: {
    label: string;
    hint: string;
    currentUrl: string | null;
    alt: string;
    error?: string;
    onFile: (file: File | null) => void;
    removeChecked: boolean;
    onRemoveChange: (checked: boolean) => void;
    canRemove: boolean;
}) {
    return (
        <div>
            <p className="mb-1 text-sm font-medium text-ink">{label}</p>
            {currentUrl ? (
                <img src={currentUrl} alt={alt} className="mb-3 h-16 w-auto max-w-full object-contain object-left" />
            ) : (
                <p className="mb-3 text-sm text-muted">No file uploaded. The existing OurNear Talent mark will be used.</p>
            )}
            <input
                type="file"
                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                className={inputClass}
                onChange={(event) => {
                    const file = event.target.files?.[0] ?? null;
                    onFile(file);
                    if (file) {
                        onRemoveChange(false);
                    }
                }}
            />
            {hint ? <p className="mt-1 text-xs text-muted">{hint}</p> : null}
            {canRemove ? (
                <label className="mt-2 flex items-center gap-2 text-sm text-ink">
                    <input
                        type="checkbox"
                        checked={removeChecked}
                        onChange={(event) => onRemoveChange(event.target.checked)}
                    />
                    Remove current file
                </label>
            ) : null}
            {error ? <p className="mt-1 text-sm text-danger">{error}</p> : null}
        </div>
    );
}
