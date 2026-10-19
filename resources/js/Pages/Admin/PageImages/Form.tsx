import { Link, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';

type Slot = {
    page: string;
    page_label: string;
    key: string;
    label: string;
};

type SavedImage = {
    id: number;
    page_slug: string;
    slot_key: string;
    slot_label: string;
    title: string;
    alt_text: string;
    is_active: boolean;
    url: string | null;
    filename: string | null;
};

type Props = {
    slots: Slot[];
    image: SavedImage | null;
    selected: { page_slug: string; slot_key: string };
};

const fieldClass = 'mt-1 w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink';

export default function Form({ slots, image, selected }: Props) {
    const pages = Array.from(new Map(slots.map((slot) => [slot.page, slot.page_label])).entries());
    const form = useForm({
        page_slug: image?.page_slug || selected.page_slug || slots[0]?.page || '',
        slot_key: image?.slot_key || selected.slot_key || '',
        title: image?.title ?? '',
        alt_text: image?.alt_text ?? '',
        is_active: image?.is_active ?? true,
        image: null as File | null,
    });
    const [preview, setPreview] = useState<string | null>(image?.url ?? null);
    const sectionOptions = slots.filter((slot) => slot.page === form.data.page_slug);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => {
            const payload: Record<string, unknown> = { ...data };
            if (!data.image) {
                delete payload.image;
            }
            if (image) {
                payload._method = 'put';
            }
            return payload;
        });
        form.post(image ? `/admin/page-images/${image.id}` : '/admin/page-images', { forceFormData: true });
    };

    return (
        <AdminLayout title={image ? 'Replace page image' : 'Upload page image'}>
            <h1 className="text-xl font-bold text-ink">{image ? 'Replace page image' : 'Upload page image'}</h1>
            <form className="mt-4 max-w-xl space-y-4" onSubmit={submit}>
                <label className="block text-sm font-semibold text-ink">
                    Page
                    <select
                        className={fieldClass}
                        value={form.data.page_slug}
                        disabled={image !== null}
                        onChange={(event) => {
                            const page = event.target.value;
                            const first = slots.find((slot) => slot.page === page);
                            form.setData({ ...form.data, page_slug: page, slot_key: first?.key ?? '' });
                        }}
                    >
                        {pages.map(([value, label]) => (
                            <option key={value} value={value}>
                                {label}
                            </option>
                        ))}
                    </select>
                </label>
                <label className="block text-sm font-semibold text-ink">
                    Section
                    <select
                        className={fieldClass}
                        value={form.data.slot_key}
                        disabled={image !== null}
                        onChange={(event) => form.setData('slot_key', event.target.value)}
                    >
                        {sectionOptions.map((slot) => (
                            <option key={slot.key} value={slot.key}>
                                {slot.label}
                            </option>
                        ))}
                    </select>
                </label>
                {form.errors.slot_key ? <p className="text-sm text-accent">{form.errors.slot_key}</p> : null}
                <label className="block text-sm font-semibold text-ink">
                    Image title
                    <input className={fieldClass} value={form.data.title} onChange={(event) => form.setData('title', event.target.value)} />
                </label>
                {form.errors.title ? <p className="text-sm text-accent">{form.errors.title}</p> : null}
                <label className="block text-sm font-semibold text-ink">
                    Image file
                    <input
                        className={fieldClass}
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        onChange={(event) => {
                            const file = event.target.files?.[0] ?? null;
                            form.setData('image', file);
                            setPreview(file ? URL.createObjectURL(file) : image?.url ?? null);
                        }}
                    />
                </label>
                {form.errors.image ? <p className="text-sm text-accent">{form.errors.image}</p> : null}
                {preview ? (
                    <img src={preview} alt="Selected image preview" className="aspect-[4/3] w-full max-w-sm rounded-lg object-cover" />
                ) : (
                    <p className="text-sm text-muted">Choose a file to preview it before saving.</p>
                )}
                <label className="block text-sm font-semibold text-ink">
                    Alt text
                    <input className={fieldClass} value={form.data.alt_text} onChange={(event) => form.setData('alt_text', event.target.value)} />
                </label>
                {form.errors.alt_text ? <p className="text-sm text-accent">{form.errors.alt_text}</p> : null}
                <label className="flex items-center gap-2 text-sm font-semibold text-ink">
                    <input
                        type="checkbox"
                        checked={form.data.is_active}
                        onChange={(event) => form.setData('is_active', event.target.checked)}
                    />
                    Active on the public page
                </label>
                <div className="flex gap-3">
                    <button type="submit" className="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white" disabled={form.processing}>
                        Save
                    </button>
                    <Link href="/admin/page-images" className="rounded-md border border-line px-4 py-2 text-sm font-semibold text-ink">
                        Cancel
                    </Link>
                </div>
            </form>
        </AdminLayout>
    );
}
