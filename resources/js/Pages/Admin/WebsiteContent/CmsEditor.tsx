import { router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import type { CmsPage } from '@/types/cms';
import type { MediaOption } from '@/types/media';

type Props = {
    cmsPage: CmsPage;
    activeSection: string;
    mediaOptions: MediaOption[];
};

const inputClass =
    'w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand';

const toPayload = (page: CmsPage) => ({
    seo_title: page.seo_title ?? '',
    meta_description: page.meta_description ?? '',
    sections: page.sections.map((section) => ({
        section_key: section.key,
        heading: section.heading ?? '',
        subtitle: section.subtitle ?? '',
        body: section.body ?? '',
        primary_button_label: section.primary_button_label ?? '',
        secondary_button_label: section.secondary_button_label ?? '',
        image_path: section.image_path ?? '',
        media_id: section.media_id ?? null,
        is_active: section.is_active,
        extra: section.extra ?? {},
        items: section.items.map((item) => ({
            id: item.id,
            title: item.title,
            description: item.description ?? '',
            icon: item.icon ?? '',
            image_path: item.image_path ?? '',
            is_active: item.is_active,
            bullets: (item.bullets ?? []).join('\n'),
        })),
    })),
});

export default function CmsEditor({ cmsPage, activeSection, mediaOptions }: Props) {
    const [sectionKey, setSectionKey] = useState(activeSection);
    const form = useForm(toPayload(cmsPage));
    const sectionIndex = form.data.sections.findIndex((section) => section.section_key === sectionKey);
    const current = form.data.sections[sectionIndex];
    const previewSection = cmsPage.sections.find((section) => section.key === sectionKey);
    const selectedMedia = mediaOptions.find((option) => option.id === current?.media_id);

    const updateSection = (patch: Partial<(typeof form.data.sections)[number]>) => {
        form.setData(
            'sections',
            form.data.sections.map((section, index) => (index === sectionIndex ? { ...section, ...patch } : section)),
        );
    };

    const updateExtra = (key: string, value: string) => {
        updateSection({ extra: { ...current.extra, [key]: value } });
    };

    const updateItem = (itemIndex: number, patch: Partial<(typeof current.items)[number]>) => {
        updateSection({
            items: current.items.map((item, index) => (index === itemIndex ? { ...item, ...patch } : item)),
        });
    };

    const save = (event: FormEvent) => {
        event.preventDefault();
        form.put(`/admin/website-content/${cmsPage.slug}`);
    };

    const resetDefaults = () => {
        if (
            !window.confirm(
                'Restore all website content to the OurNear defaults? Saved edits will be replaced. Database tables will not be deleted.',
            )
        ) {
            return;
        }

        router.post(`/admin/website-content/${cmsPage.slug}/reset`, { confirm: 1 });
    };

    if (!current) {
        return <p className="text-sm text-muted">No section selected.</p>;
    }

    return (
        <form onSubmit={save}>
            <div className="mb-4 flex flex-wrap items-center justify-end gap-2">
                <a
                    href={cmsPage.preview_url}
                    target="_blank"
                    rel="noreferrer"
                    className="rounded-md border border-line bg-white px-4 py-2 text-sm font-semibold text-ink"
                >
                    Preview Website
                </a>
                <button
                    type="button"
                    onClick={resetDefaults}
                    className="rounded-md border border-line bg-white px-4 py-2 text-sm font-semibold text-ink"
                >
                    Reset to Default
                </button>
                <button
                    type="submit"
                    disabled={form.processing}
                    className="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white disabled:bg-disabled"
                >
                    {form.processing ? 'Saving…' : 'Save Changes'}
                </button>
            </div>

            {Object.keys(form.errors).length > 0 ? (
                <p className="mb-4 rounded-md border border-danger/20 bg-danger/10 px-3 py-2 text-sm text-danger">
                    Please correct the highlighted fields. Changes were not saved.
                </p>
            ) : null}

            <div className="mb-4 grid gap-4 rounded-xl border border-line bg-white p-5 md:grid-cols-2">
                <Field
                    label="SEO title"
                    value={form.data.seo_title}
                    onChange={(value) => form.setData('seo_title', value)}
                    hint="Optional. Overrides the default site title for this page."
                    error={form.errors.seo_title}
                />
                <Field
                    label="Meta description"
                    value={form.data.meta_description}
                    onChange={(value) => form.setData('meta_description', value)}
                    hint="Optional. Used in search results for this page."
                    error={form.errors.meta_description}
                    multiline
                />
            </div>

            <div className="grid gap-4 xl:grid-cols-[220px_minmax(0,1fr)_280px]">
                <aside className="rounded-xl border border-line bg-white p-3">
                    <h2 className="px-2 text-xs font-semibold uppercase tracking-wide text-muted">Page Sections</h2>
                    <nav className="mt-2 space-y-1">
                        {cmsPage.sections.map((section) => (
                            <button
                                key={section.key}
                                type="button"
                                onClick={() => setSectionKey(section.key)}
                                className={`block w-full rounded-md px-3 py-2 text-left text-sm ${
                                    section.key === sectionKey ? 'bg-brand text-white' : 'text-ink hover:bg-surface'
                                }`}
                            >
                                {section.label}
                            </button>
                        ))}
                    </nav>
                </aside>

                <div className="space-y-4 rounded-xl border border-line bg-white p-5">
                    <div className="flex items-center justify-between gap-3">
                        <h2 className="text-lg font-semibold text-ink">{previewSection?.label}</h2>
                        <label className="flex items-center gap-2 text-sm text-ink">
                            <input
                                type="checkbox"
                                checked={current.is_active}
                                onChange={(event) => updateSection({ is_active: event.target.checked })}
                            />
                            Active
                        </label>
                    </div>

                    <Field
                        label="Heading"
                        value={current.heading}
                        onChange={(value) => updateSection({ heading: value })}
                        error={form.errors[`sections.${sectionIndex}.heading`]}
                        multiline={current.section_key === 'hero' && cmsPage.slug === 'home'}
                    />
                    {current.section_key === 'hero' && cmsPage.slug === 'services' ? (
                        <Field
                            label="Subtitle"
                            value={current.subtitle}
                            onChange={(value) => updateSection({ subtitle: value })}
                            error={form.errors[`sections.${sectionIndex}.subtitle`]}
                        />
                    ) : null}
                    <Field
                        label="Description"
                        value={current.body}
                        onChange={(value) => updateSection({ body: value })}
                        error={form.errors[`sections.${sectionIndex}.body`]}
                        multiline
                    />
                    {current.section_key === 'hero' || current.section_key === 'cta' ? (
                        <div className="grid gap-4 md:grid-cols-2">
                            <Field
                                label="CTA Button Text"
                                value={current.primary_button_label}
                                onChange={(value) => updateSection({ primary_button_label: value })}
                            />
                            {current.section_key === 'hero' ? (
                                <Field
                                    label="Secondary Button Text"
                                    value={current.secondary_button_label}
                                    onChange={(value) => updateSection({ secondary_button_label: value })}
                                />
                            ) : null}
                        </div>
                    ) : null}

                    {previewSection?.supports_image ? (
                        <div>
                            <label className="mb-1 block text-sm font-medium text-ink">Image</label>
                            <select
                                className={inputClass}
                                value={current.media_id ?? ''}
                                onChange={(event) =>
                                    updateSection({
                                        media_id: event.target.value === '' ? null : Number(event.target.value),
                                    })
                                }
                            >
                                <option value="">No custom image (use page default)</option>
                                {mediaOptions.map((option) => (
                                    <option key={option.id} value={option.id}>
                                        {option.original_name}
                                    </option>
                                ))}
                            </select>
                            <p className="mt-1 text-xs text-muted">
                                Upload images in Media & Social, then assign them here. The public page works without an
                                assigned image.
                            </p>
                            {selectedMedia?.url ? (
                                <img src={selectedMedia.url} alt="" className="mt-3 max-h-40 rounded-md object-cover" />
                            ) : null}
                        </div>
                    ) : null}

                    {cmsPage.slug === 'about' && current.section_key === 'who_we_are' ? (
                        <div className="grid gap-4">
                            <Field label="Mission" value={current.extra.mission ?? ''} onChange={(value) => updateExtra('mission', value)} multiline />
                            <Field label="Vision" value={current.extra.vision ?? ''} onChange={(value) => updateExtra('vision', value)} multiline />
                            <Field label="Values" value={current.extra.values ?? ''} onChange={(value) => updateExtra('values', value)} multiline />
                        </div>
                    ) : null}

                    {current.section_key === 'founder' ? (
                        <div className="grid gap-4">
                            <Field label="Founder name" value={current.extra.founder_name ?? ''} onChange={(value) => updateExtra('founder_name', value)} />
                            <Field label="Founder title / role" value={current.extra.founder_title ?? ''} onChange={(value) => updateExtra('founder_title', value)} />
                            <Field
                                label="Founder description"
                                value={current.extra.founder_description ?? ''}
                                onChange={(value) => updateExtra('founder_description', value)}
                                multiline
                            />
                        </div>
                    ) : null}

                    {current.section_key === 'details' ? (
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="md:col-span-2 rounded-md border border-line bg-surface px-3 py-2 text-sm text-muted">
                                Office phone, email, address, and company WhatsApp numbers are managed in{' '}
                                <a href="/admin/settings?tab=website" className="font-medium text-brand hover:text-accent">
                                    Website Settings
                                </a>
                                . SMTP passwords and WhatsApp API credentials are not stored in CMS.
                            </div>
                            <Field label="Office timings" value={current.extra.hours ?? ''} onChange={(value) => updateExtra('hours', value)} />
                            <div className="md:col-span-2">
                                <Field
                                    label="Help / introductory content"
                                    value={current.extra.help ?? ''}
                                    onChange={(value) => updateExtra('help', value)}
                                    multiline
                                />
                            </div>
                        </div>
                    ) : null}

                    {current.items.length > 0 ? (
                        <div className="space-y-4">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Items</h3>
                            {current.items.map((item, itemIndex) => (
                                <ItemEditor
                                    key={item.id}
                                    item={item}
                                    error={form.errors[`sections.${sectionIndex}.items.${itemIndex}.title`]}
                                    onChange={(patch) => updateItem(itemIndex, patch)}
                                />
                            ))}
                        </div>
                    ) : null}
                </div>

                <aside className="rounded-xl border border-line bg-white p-4">
                    <h2 className="text-xs font-semibold uppercase tracking-wide text-muted">Live Preview</h2>
                    <div className="mt-3 overflow-hidden rounded-lg bg-brand p-4 text-white">
                        <p className="text-lg font-semibold whitespace-pre-line">{current.heading || previewSection?.label}</p>
                        {current.subtitle ? <p className="mt-1 text-sm text-accent">{current.subtitle}</p> : null}
                        <p className="mt-2 text-sm text-white/80">{current.body}</p>
                    </div>
                    <div className="mt-3 space-y-2">
                        {current.items
                            .filter((item) => item.is_active)
                            .map((item) => (
                                <article key={item.id} className="rounded-md border border-line p-3">
                                    <p className="text-sm font-semibold text-ink">{item.title}</p>
                                    {item.description ? <p className="mt-1 text-xs text-muted">{item.description}</p> : null}
                                </article>
                            ))}
                    </div>
                    {current.section_key === 'details' ? (
                        <div className="mt-3 space-y-1 text-sm text-muted">
                            <p>{current.extra.hours}</p>
                        </div>
                    ) : null}
                </aside>
            </div>
        </form>
    );
}

function Field({
    label,
    value,
    onChange,
    multiline = false,
    error,
    hint,
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
    multiline?: boolean;
    error?: string;
    hint?: string;
}) {
    return (
        <div>
            <label className="mb-1 block text-sm font-medium text-ink">{label}</label>
            {multiline ? (
                <textarea className={`${inputClass} min-h-28`} value={value} onChange={(event) => onChange(event.target.value)} />
            ) : (
                <input className={inputClass} value={value} onChange={(event) => onChange(event.target.value)} />
            )}
            {hint ? <p className="mt-1 text-xs text-muted">{hint}</p> : null}
            {error ? <p className="mt-1 text-sm text-danger">{error}</p> : null}
        </div>
    );
}

function ItemEditor({
    item,
    onChange,
    error,
}: {
    item: {
        id: number;
        title: string;
        description: string;
        icon: string;
        image_path: string;
        is_active: boolean;
        bullets: string;
    };
    onChange: (patch: Partial<typeof item>) => void;
    error?: string;
}) {
    return (
        <article className="rounded-lg border border-line p-4">
            <div className="flex items-start justify-between gap-3">
                <div className="flex-1">
                    <Field label="Title" value={item.title} onChange={(value) => onChange({ title: value })} error={error} />
                </div>
                <label className="mt-7 flex items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        checked={item.is_active}
                        onChange={(event) => onChange({ is_active: event.target.checked })}
                    />
                    Active
                </label>
            </div>
            <div className="mt-3">
                <Field
                    label="Description"
                    value={item.description}
                    onChange={(value) => onChange({ description: value })}
                    multiline
                />
            </div>
            <div className="mt-3">
                <Field label="Icon reference" value={item.icon} onChange={(value) => onChange({ icon: value })} />
            </div>
            <div className="mt-3">
                <Field
                    label="Bullet points"
                    value={item.bullets}
                    onChange={(value) => onChange({ bullets: value })}
                    multiline
                    hint="One point per line."
                />
            </div>
        </article>
    );
}
