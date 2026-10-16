import { router, useForm } from '@inertiajs/react';
import { useEffect, useState, type FormEvent, type ReactNode } from 'react';
import Button from '@/Components/atoms/Button';
import FormField from '@/Components/Public/FormField';
import { accountLayout } from '@/design-system/classNames';
import type { AccountProfessionalLanguage } from '@/types/account';

const selectClass =
    'w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand';

type ProficiencyOption = { value: string; label: string };

type EditTarget = null | 'add' | number;

type Props = {
    languages: AccountProfessionalLanguage[];
    proficiencies: ProficiencyOption[];
};

function LanguageRow({
    label,
    value,
}: {
    label: string;
    value: ReactNode;
}) {
    return (
        <div className="grid gap-1 sm:grid-cols-[minmax(7rem,10rem)_1fr] sm:gap-3">
            <dt className="text-sm font-medium text-muted">{label}</dt>
            <dd className="text-sm text-ink">{value}</dd>
        </div>
    );
}

export default function CandidateProfessionalLanguages({ languages, proficiencies }: Props) {
    const [editTarget, setEditTarget] = useState<EditTarget>(null);

    const form = useForm({
        name: '',
        proficiency: '',
    });

    const editingRecord = typeof editTarget === 'number' ? languages.find((item) => item.id === editTarget) : null;

    useEffect(() => {
        if (editTarget === 'add') {
            form.setData({ name: '', proficiency: '' });
            form.clearErrors();
            return;
        }
        if (editingRecord) {
            form.setData({
                name: editingRecord.name,
                proficiency: editingRecord.proficiency ?? '',
            });
            form.clearErrors();
        }
    }, [editTarget, editingRecord?.id]);

    const closeEditor = () => {
        setEditTarget(null);
        form.reset();
        form.clearErrors();
    };

    const refreshProfile = () => {
        router.reload({ only: ['profile'] });
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const onSuccess = () => {
            closeEditor();
            refreshProfile();
        };

        if (typeof editTarget === 'number') {
            form.put(`/account/profile/languages/${editTarget}`, {
                preserveScroll: true,
                onSuccess,
            });
            return;
        }

        form.post('/account/profile/languages', {
            preserveScroll: true,
            onSuccess,
        });
    };

    const remove = (item: AccountProfessionalLanguage) => {
        if (!window.confirm(`Remove ${item.name} from your professional languages?`)) {
            return;
        }
        router.delete(`/account/profile/languages/${item.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                if (editTarget === item.id) {
                    closeEditor();
                }
                refreshProfile();
            },
        });
    };

    return (
        <div className={accountLayout.profileWorkspace}>
            <header className="min-w-0">
                <h1 className="text-xl font-semibold text-ink sm:text-2xl">Languages</h1>
                <p className="mt-1 text-sm text-muted">Manage the languages you can use at work.</p>
                <p className="mt-2 text-xs text-muted">
                    Professional languages are separate from your account language setting in Personal Profile.
                </p>
            </header>

            {editTarget === null ? (
                <section className="rounded-xl border border-line bg-white p-5 sm:p-6">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <h2 className="text-base font-semibold text-ink">Your languages</h2>
                        <button
                            type="button"
                            className="text-sm font-semibold text-brand hover:underline"
                            onClick={() => setEditTarget('add')}
                        >
                            Add Language
                        </button>
                    </div>
                    {languages.length === 0 ? (
                        <p className="mt-4 text-sm text-muted">No languages added yet. Add the languages you use at work.</p>
                    ) : (
                        <ul className="mt-4 divide-y divide-line">
                            {languages.map((item) => (
                                <li key={item.id} className="flex flex-wrap items-start justify-between gap-3 py-3 first:pt-0 last:pb-0">
                                    <dl className="min-w-0 flex-1 space-y-1">
                                        <LanguageRow label="Language" value={item.name} />
                                        <LanguageRow
                                            label="Proficiency"
                                            value={item.proficiency_label ?? 'Not specified'}
                                        />
                                    </dl>
                                    <div className="flex shrink-0 gap-3">
                                        <button
                                            type="button"
                                            className="text-sm font-semibold text-brand hover:underline"
                                            onClick={() => setEditTarget(item.id)}
                                        >
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            className="text-sm font-semibold text-danger hover:underline"
                                            onClick={() => remove(item)}
                                        >
                                            Remove
                                        </button>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            ) : (
                <section className="rounded-xl border border-line bg-white p-5 sm:p-6">
                    <h2 className="text-base font-semibold text-ink">
                        {editTarget === 'add' ? 'Add language' : 'Edit language'}
                    </h2>
                    <form onSubmit={submit} className="mt-4 space-y-4">
                        <FormField
                            id="professional_language_name"
                            label="Language"
                            value={form.data.name}
                            onChange={(event) => form.setData('name', event.target.value)}
                            maxLength={80}
                            required
                        />
                        {form.errors.name ? <p className="-mt-2 text-sm text-danger">{form.errors.name}</p> : null}
                        <div>
                            <label htmlFor="professional_language_proficiency" className="mb-1 block text-sm font-medium text-ink">
                                Proficiency
                            </label>
                            <select
                                id="professional_language_proficiency"
                                className={selectClass}
                                value={form.data.proficiency}
                                onChange={(event) => form.setData('proficiency', event.target.value)}
                            >
                                <option value="">Not specified</option>
                                {proficiencies.map((item) => (
                                    <option key={item.value} value={item.value}>
                                        {item.label}
                                    </option>
                                ))}
                            </select>
                            {form.errors.proficiency ? (
                                <p className="mt-1 text-sm text-danger">{form.errors.proficiency}</p>
                            ) : null}
                        </div>
                        <div className="flex flex-wrap gap-3 border-t border-line pt-4">
                            <Button type="submit" variant="primary" loading={form.processing} disabled={form.processing}>
                                Save
                            </Button>
                            <Button type="button" variant="outline" disabled={form.processing} onClick={closeEditor}>
                                Cancel
                            </Button>
                        </div>
                    </form>
                </section>
            )}
        </div>
    );
}
