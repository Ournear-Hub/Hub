import { useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Button, Card, Input, Textarea } from '@/Components/Admin/ui';
import type { ScorecardTemplateOption } from '@/types/applications';

type Props = {
    templates: ScorecardTemplateOption[];
    can_manage: boolean;
};

export default function ScorecardTemplatesIndex({ templates, can_manage }: Props) {
    const form = useForm({
        name: '',
        description: '',
        criteria: [{ key: 'overall', label: 'Overall impression' }],
        is_active: true,
    });

    return (
        <AdminLayout title="Scorecard templates">
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight text-ink">Scorecard templates</h1>
                    <p className="mt-1 text-sm text-muted">
                        Qualitative interview templates. Submitted scores never change hiring or ATS status.
                    </p>
                </div>

                {can_manage ? (
                    <form
                        className="space-y-3 rounded-xl border border-line bg-white p-4"
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.post('/admin/scorecard-templates', { onSuccess: () => form.reset() });
                        }}
                    >
                        <Input
                            placeholder="Template name"
                            value={form.data.name}
                            onChange={(event) => form.setData('name', event.target.value)}
                            required
                        />
                        <Textarea
                            placeholder="Description"
                            value={form.data.description}
                            onChange={(event) => form.setData('description', event.target.value)}
                        />
                        {form.data.criteria.map((criterion, index) => (
                            <div key={index} className="grid gap-2 md:grid-cols-2">
                                <Input
                                    placeholder="Key"
                                    value={criterion.key}
                                    onChange={(event) => {
                                        const criteria = [...form.data.criteria];
                                        criteria[index] = { ...criteria[index], key: event.target.value };
                                        form.setData('criteria', criteria);
                                    }}
                                />
                                <Input
                                    placeholder="Label"
                                    value={criterion.label}
                                    onChange={(event) => {
                                        const criteria = [...form.data.criteria];
                                        criteria[index] = { ...criteria[index], label: event.target.value };
                                        form.setData('criteria', criteria);
                                    }}
                                />
                            </div>
                        ))}
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() =>
                                form.setData('criteria', [
                                    ...form.data.criteria,
                                    { key: `criterion_${form.data.criteria.length + 1}`, label: '' },
                                ])
                            }
                        >
                            Add criterion
                        </Button>
                        <div>
                            <Button type="submit" disabled={form.processing}>
                                Create template
                            </Button>
                        </div>
                    </form>
                ) : null}

                <div className="grid gap-3 md:grid-cols-2">
                    {templates.map((template) => (
                        <Card key={template.id}>
                            <h2 className="text-base font-semibold text-ink">{template.name}</h2>
                            <p className="mt-1 text-sm text-muted">{template.description || 'No description.'}</p>
                            <ul className="mt-3 list-disc pl-5 text-sm text-ink">
                                {template.criteria.map((criterion) => (
                                    <li key={criterion.key}>{criterion.label}</li>
                                ))}
                            </ul>
                        </Card>
                    ))}
                </div>
            </div>
        </AdminLayout>
    );
}
