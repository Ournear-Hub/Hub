import { Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import SettingsEmailPanel, { type MailSettings } from '@/Pages/Admin/SettingsEmailPanel';

type WebsiteSettings = {
    company_name: string;
    office_phone: string;
    primary_whatsapp: string;
    recruitment_whatsapp: string;
    office_email: string;
    support_email: string;
    recruitment_email: string;
    reply_to_email: string;
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
    logo_filename?: string | null;
    wordmark_url?: string | null;
    wordmark_filename?: string | null;
    icon_url: string | null;
    icon_filename?: string | null;
    favicon_url: string | null;
    favicon_filename?: string | null;
    auth_splash_video_url?: string | null;
};

type MailEffective = {
    mail_enabled: boolean;
    mail_mailer: string;
    smtp_configured: boolean;
    smtp_password_configured: boolean;
    application_url: string;
    from_address: string;
    from_name: string;
    uses_queue: boolean;
};

type MediaPickerOption = {
    id: number;
    url: string | null;
    original_name: string;
};

type Props = {
    tab?: 'website' | 'branding' | 'email';
    websiteSettings: WebsiteSettings;
    branding: BrandingSettings;
    brandingMediaOptions?: MediaPickerOption[];
    mailSettings?: MailSettings;
    mailEffective?: MailEffective;
    mailPrerequisites?: Record<string, { label: string; status: string; instruction: string }>;
};

const inputClass =
    'w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand';

export default function Settings({
    tab = 'website',
    websiteSettings,
    branding,
    brandingMediaOptions = [],
    mailSettings,
    mailEffective,
    mailPrerequisites,
}: Props) {
    const { flash } = usePage<{ flash: { success?: string } }>().props;
    const websiteForm = useForm(websiteSettings);
    const brandingForm = useForm({
        website_name: branding.website_name,
        logo: null as File | null,
        wordmark: null as File | null,
        icon: null as File | null,
        favicon: null as File | null,
        remove_logo: false,
        remove_wordmark: false,
        remove_icon: false,
        remove_favicon: false,
        auth_splash: null as File | null,
        remove_auth_splash: false,
        logo_media_id: '' as string,
        wordmark_media_id: '' as string,
    });

    const openTab = (next: 'website' | 'branding' | 'email') => {
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
                        Branding &amp; assets
                    </button>
                    <button
                        type="button"
                        onClick={() => openTab('email')}
                        className={`rounded-md px-4 py-2 text-sm font-semibold ${
                            tab === 'email' ? 'bg-brand text-white' : 'bg-white text-ink ring-1 ring-line'
                        }`}
                    >
                        Email delivery
                    </button>
                    <Link
                        href="/admin/profile"
                        className="rounded-md px-4 py-2 text-sm font-semibold text-ink ring-1 ring-line hover:bg-surface"
                    >
                        Admin profile
                    </Link>
                </div>

                {tab === 'email' && mailSettings && mailEffective && mailPrerequisites ? (
                    <SettingsEmailPanel
                        mailSettings={mailSettings}
                        mailEffective={mailEffective}
                        mailPrerequisites={mailPrerequisites}
                    />
                ) : tab === 'website' ? (
                    <section className="rounded-xl border border-line bg-white p-6">
                        <h2 className="text-lg font-semibold text-ink">Website settings</h2>
                        <p className="mt-1 text-sm text-muted">
                            These values are used in the footer, contact details, and default search listing text.
                        </p>
                        {flash?.success && tab === 'website' ? (
                            <p className="mt-4 rounded-md border border-success/30 bg-success/5 px-3 py-2 text-sm text-success">
                                {flash.success}
                            </p>
                        ) : null}
                        <form onSubmit={submitWebsite} className="mt-4 max-w-lg space-y-4">
                            <Field
                                label="Company / site name"
                                value={websiteForm.data.company_name}
                                onChange={(value) => websiteForm.setData('company_name', value)}
                                error={websiteForm.errors.company_name}
                            />

                            <div className="border-t border-line pt-4">
                                <h3 className="text-base font-semibold text-ink">Contact Us page</h3>
                                <p className="mt-1 text-sm text-muted">
                                    Shown on the public Contact Us page, account Contact Us, and the mobile contact API.
                                </p>
                            </div>
                            <Field
                                label="Office address"
                                value={websiteForm.data.office_address}
                                onChange={(value) => websiteForm.setData('office_address', value)}
                                error={websiteForm.errors.office_address}
                                multiline
                            />
                            <Field
                                label="Enquiry email"
                                value={websiteForm.data.support_email}
                                onChange={(value) => websiteForm.setData('support_email', value)}
                                error={websiteForm.errors.support_email}
                                hint="Public enquiries mailbox. Falls back to primary company email when blank."
                            />
                            <Field
                                label="Contact phone number"
                                value={websiteForm.data.office_phone}
                                onChange={(value) => websiteForm.setData('office_phone', value)}
                                error={websiteForm.errors.office_phone}
                            />
                            <Field
                                label="WhatsApp number"
                                value={websiteForm.data.primary_whatsapp}
                                onChange={(value) => websiteForm.setData('primary_whatsapp', value)}
                                error={websiteForm.errors.primary_whatsapp}
                                hint="Public click-to-chat (wa.me) number with country code."
                            />

                            <Field
                                label="Website URL"
                                value={websiteForm.data.website_url}
                                onChange={(value) => websiteForm.setData('website_url', value)}
                                error={websiteForm.errors.website_url}
                                hint="Public https address of this website."
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

                            <div className="border-t border-line pt-4">
                                <h3 className="text-base font-semibold text-ink">Company communication</h3>
                                <p className="mt-1 text-sm text-muted">
                                    Public contact identities used for sending mail and company WhatsApp links. SMTP
                                    credentials are managed on the Email delivery tab.
                                </p>
                            </div>
                            <Field
                                label="Primary company email"
                                value={websiteForm.data.office_email}
                                onChange={(value) => websiteForm.setData('office_email', value)}
                                error={websiteForm.errors.office_email}
                                hint="Used as the From address for application mail when configured."
                            />
                            <Field
                                label="Recruitment / careers email"
                                value={websiteForm.data.recruitment_email}
                                onChange={(value) => websiteForm.setData('recruitment_email', value)}
                                error={websiteForm.errors.recruitment_email}
                            />
                            <Field
                                label="Default reply-to email"
                                value={websiteForm.data.reply_to_email}
                                onChange={(value) => websiteForm.setData('reply_to_email', value)}
                                error={websiteForm.errors.reply_to_email}
                                hint="Used when a more specific support or recruitment address is not set."
                            />
                            <Field
                                label="Recruitment WhatsApp number"
                                value={websiteForm.data.recruitment_whatsapp}
                                onChange={(value) => websiteForm.setData('recruitment_whatsapp', value)}
                                error={websiteForm.errors.recruitment_whatsapp}
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
                        <h2 className="text-lg font-semibold text-ink">Branding &amp; website assets</h2>
                        <p className="mt-1 text-sm text-muted">
                            Configure header and footer logos, favicon, and authentication visuals. When an asset is missing
                            or invalid, the public site falls back to the OurNear Hire text mark instead of a broken image.
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
                                label="Header / navigation logo (horizontal)"
                                hint="Optional single-image header logo. JPG, PNG, or WEBP, max 5 MB."
                                currentUrl={branding.logo_url}
                                storedFilename={branding.logo_filename}
                                alt={`${branding.website_name} header logo`}
                                error={brandingForm.errors.logo}
                                pendingFile={brandingForm.data.logo}
                                onFile={(file) => {
                                    brandingForm.setData('logo', file);
                                    if (file) {
                                        brandingForm.setData('logo_media_id', '');
                                    }
                                }}
                                removeChecked={brandingForm.data.remove_logo}
                                onRemoveChange={(checked) => brandingForm.setData('remove_logo', checked)}
                                canRemove={Boolean(branding.logo_url)}
                                previewClassName="h-16 w-16 object-contain object-left"
                                mediaOptions={brandingMediaOptions}
                                mediaValue={brandingForm.data.logo_media_id}
                                onMediaChange={(value) => {
                                    brandingForm.setData('logo_media_id', value);
                                    if (value) {
                                        brandingForm.setData('logo', null);
                                        brandingForm.setData('remove_logo', false);
                                    }
                                }}
                                mediaError={brandingForm.errors.logo_media_id}
                            />
                            <BrandingAssetField
                                label="Footer logo"
                                hint="Logo shown in the public footer (and companion wordmark on auth screens). JPG, PNG, or WEBP, max 5 MB."
                                currentUrl={branding.wordmark_url ?? null}
                                storedFilename={branding.wordmark_filename}
                                alt={`${branding.website_name} footer logo`}
                                error={brandingForm.errors.wordmark}
                                pendingFile={brandingForm.data.wordmark}
                                onFile={(file) => {
                                    brandingForm.setData('wordmark', file);
                                    if (file) {
                                        brandingForm.setData('wordmark_media_id', '');
                                    }
                                }}
                                removeChecked={brandingForm.data.remove_wordmark}
                                onRemoveChange={(checked) => brandingForm.setData('remove_wordmark', checked)}
                                canRemove={Boolean(branding.wordmark_url)}
                                previewClassName="h-16 w-auto max-w-full object-contain object-left"
                                mediaOptions={brandingMediaOptions}
                                mediaValue={brandingForm.data.wordmark_media_id}
                                onMediaChange={(value) => {
                                    brandingForm.setData('wordmark_media_id', value);
                                    if (value) {
                                        brandingForm.setData('wordmark', null);
                                        brandingForm.setData('remove_wordmark', false);
                                    }
                                }}
                                mediaError={brandingForm.errors.wordmark_media_id}
                            />
                            <BrandingAssetField
                                label="Header symbol (navigation lockup)"
                                hint="Symbol paired with the hire wordmark when no horizontal header logo is set. JPG, PNG, or WEBP, max 5 MB."
                                currentUrl={branding.icon_url}
                                storedFilename={branding.icon_filename}
                                alt={`${branding.website_name} icon`}
                                error={brandingForm.errors.icon}
                                pendingFile={brandingForm.data.icon}
                                onFile={(file) => brandingForm.setData('icon', file)}
                                removeChecked={brandingForm.data.remove_icon}
                                onRemoveChange={(checked) => brandingForm.setData('remove_icon', checked)}
                                canRemove={Boolean(branding.icon_url)}
                                previewClassName="h-16 w-16 object-contain object-left"
                            />
                            <BrandingAssetField
                                label="Favicon"
                                hint="Browser tab icon. PNG, JPG, or WEBP, max 5 MB."
                                currentUrl={branding.favicon_url}
                                storedFilename={branding.favicon_filename}
                                alt={`${branding.website_name} favicon`}
                                error={brandingForm.errors.favicon}
                                pendingFile={brandingForm.data.favicon}
                                onFile={(file) => brandingForm.setData('favicon', file)}
                                removeChecked={brandingForm.data.remove_favicon}
                                onRemoveChange={(checked) => brandingForm.setData('remove_favicon', checked)}
                                canRemove={Boolean(branding.favicon_url)}
                                previewClassName="h-12 w-12 object-contain object-left"
                            />
                            <div>
                                <p className="mb-1 text-sm font-medium text-ink">Authentication splash video</p>
                                {branding.auth_splash_video_url && !brandingForm.data.remove_auth_splash ? (
                                    <video
                                        src={branding.auth_splash_video_url}
                                        className="mb-3 max-h-40 w-full rounded-md border border-line bg-black object-contain"
                                        controls
                                        muted
                                        playsInline
                                        preload="metadata"
                                    />
                                ) : (
                                    <p className="mb-3 text-sm text-muted">
                                        No custom upload. The bundled public/brand/ournear-splash.mp4 file is used when present.
                                    </p>
                                )}
                                <input
                                    type="file"
                                    accept="video/mp4,.mp4"
                                    className={inputClass}
                                    onChange={(event) => {
                                        const file = event.target.files?.[0] ?? null;
                                        brandingForm.setData('auth_splash', file);
                                        if (file) {
                                            brandingForm.setData('remove_auth_splash', false);
                                        }
                                    }}
                                />
                                <p className="mt-1 text-xs text-muted">MP4 only, max 50 MB. Used on the unified Login/Signup left panel.</p>
                                {branding.auth_splash_video_url ? (
                                    <label className="mt-2 flex items-center gap-2 text-sm text-ink">
                                        <input
                                            type="checkbox"
                                            checked={brandingForm.data.remove_auth_splash}
                                            onChange={(event) => brandingForm.setData('remove_auth_splash', event.target.checked)}
                                        />
                                        Remove custom splash video (revert to bundled default)
                                    </label>
                                ) : null}
                                {brandingForm.errors.auth_splash ? (
                                    <p className="mt-1 text-sm text-danger">{brandingForm.errors.auth_splash}</p>
                                ) : null}
                            </div>
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
    storedFilename,
    alt,
    error,
    pendingFile,
    onFile,
    removeChecked,
    onRemoveChange,
    canRemove,
    previewClassName = 'h-16 w-auto max-w-full object-contain object-left',
    mediaOptions = [],
    mediaValue = '',
    onMediaChange,
    mediaError,
}: {
    label: string;
    hint: string;
    currentUrl: string | null;
    storedFilename?: string | null;
    alt: string;
    error?: string;
    pendingFile?: File | null;
    onFile: (file: File | null) => void;
    removeChecked: boolean;
    onRemoveChange: (checked: boolean) => void;
    canRemove: boolean;
    previewClassName?: string;
    mediaOptions?: MediaPickerOption[];
    mediaValue?: string;
    onMediaChange?: (value: string) => void;
    mediaError?: string;
}) {
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);

    useEffect(() => {
        if (!pendingFile) {
            setPreviewUrl(null);

            return;
        }

        const objectUrl = URL.createObjectURL(pendingFile);
        setPreviewUrl(objectUrl);

        return () => {
            URL.revokeObjectURL(objectUrl);
        };
    }, [pendingFile]);

    const displayUrl = removeChecked ? null : previewUrl ?? currentUrl;

    return (
        <div>
            <p className="mb-1 text-sm font-medium text-ink">{label}</p>
            {displayUrl ? (
                <div className="mb-3 space-y-1">
                    <img src={displayUrl} alt={alt} className={previewClassName} />
                    {previewUrl ? (
                        <p className="text-xs text-muted">Preview — save to persist.</p>
                    ) : storedFilename ? (
                        <p className="text-xs text-muted">Active file: {storedFilename}</p>
                    ) : null}
                </div>
            ) : (
                <p className="mb-3 text-sm text-muted">No file uploaded. The OurNear Hire text mark will be used on the public site.</p>
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
            {mediaOptions.length > 0 && onMediaChange ? (
                <div className="mt-3">
                    <label className="mb-1 block text-xs font-medium text-ink">Or select from media library</label>
                    <select
                        className={inputClass}
                        value={mediaValue}
                        onChange={(event) => onMediaChange(event.target.value)}
                    >
                        <option value="">— Keep current / use upload above —</option>
                        {mediaOptions.map((option) => (
                            <option key={option.id} value={String(option.id)}>
                                {option.original_name} (#{option.id})
                            </option>
                        ))}
                    </select>
                    {mediaError ? <p className="mt-1 text-sm text-danger">{mediaError}</p> : null}
                </div>
            ) : null}
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
