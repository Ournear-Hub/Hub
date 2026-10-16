import { router, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Button, Card, Input, Select, Textarea } from '@/Components/Admin/ui';

type TemplateRow = {
    id: number;
    template_key: string;
    name: string;
    description: string | null;
    channel: string;
    channel_label: string;
    audience: string;
    audience_label: string;
    subject: string | null;
    body: string;
    variables: string[];
    is_active: boolean;
    is_system_required: boolean;
    updated_at: string | null;
};

type Option = { value: string; label: string };

type Props = {
    templates: TemplateRow[];
    filters: { q: string; channel: string; audience: string; status: string };
    options: { channels: Option[]; audiences: Option[] };
    variable_catalog: string[];
    can_manage: boolean;
};

function csrfToken(): string {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta?.getAttribute('content') ?? '';
}

export default function MessageTemplatesIndex({
    templates,
    filters,
    options,
    variable_catalog,
    can_manage,
}: Props) {
    const [preview, setPreview] = useState<{ subject: string | null; body: string } | null>(null);
    const [previewError, setPreviewError] = useState<string | null>(null);
    const [editingId, setEditingId] = useState<number | null>(null);

    const createForm = useForm({
        template_key: '',
        name: '',
        description: '',
        channel: 'in_app',
        audience: 'candidate',
        subject: '',
        body: '',
        is_active: true,
    });

    const editForm = useForm({
        name: '',
        description: '',
        channel: 'in_app',
        audience: 'candidate',
        subject: '',
        body: '',
        is_active: true,
    });

    const editingTemplate = useMemo(
        () => templates.find((template) => template.id === editingId) ?? null,
        [editingId, templates],
    );

    const applyFilters = (next: Partial<Props['filters']>) => {
        router.get(
            '/admin/message-templates',
            { ...filters, ...next },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const runPreview = async (channel: string, subject: string | null, body: string) => {
        setPreviewError(null);
        setPreview(null);
        const response = await fetch('/admin/message-templates/preview', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({ channel, subject, body }),
        });

        if (! response.ok) {
            const payload = await response.json().catch(() => ({}));
            setPreviewError(payload.message ?? 'Preview could not be rendered.');
            return;
        }

        setPreview(await response.json());
    };

    return (
        <AdminLayout title="Message templates">
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight text-ink">Message templates</h1>
                    <p className="mt-1 text-sm text-muted">
                        Central registry for recruitment in-app and email copy. Placeholders use {'{{variable}}'} syntax.
                    </p>
                </div>

                <div className="grid gap-3 md:grid-cols-4">
                    <Input
                        type="search"
                        defaultValue={filters.q}
                        placeholder="Search templates"
                        onKeyDown={(event) => {
                            if (event.key === 'Enter') {
                                event.preventDefault();
                                applyFilters({ q: (event.target as HTMLInputElement).value });
                            }
                        }}
                    />
                    <Select
                        value={filters.channel}
                        onChange={(event) => applyFilters({ channel: event.target.value })}
                    >
                        <option value="">All channels</option>
                        {options.channels.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </Select>
                    <Select
                        value={filters.audience}
                        onChange={(event) => applyFilters({ audience: event.target.value })}
                    >
                        <option value="">All audiences</option>
                        {options.audiences.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </Select>
                    <Select
                        value={filters.status}
                        onChange={(event) => applyFilters({ status: event.target.value })}
                    >
                        <option value="">Any status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </Select>
                </div>

                <Card className="p-4">
                    <h2 className="text-sm font-semibold text-ink">Supported variables</h2>
                    <p className="mt-1 text-sm text-muted">{variable_catalog.map((v) => `{{${v}}}`).join(', ')}</p>
                </Card>

                {can_manage ? (
                    <form
                        className="space-y-3 rounded-xl border border-line bg-white p-4"
                        onSubmit={(event) => {
                            event.preventDefault();
                            createForm.post('/admin/message-templates', { onSuccess: () => createForm.reset() });
                        }}
                    >
                        <h2 className="text-base font-semibold text-ink">Create optional template</h2>
                        <Input
                            placeholder="Stable key (e.g. recruitment.custom.in_app)"
                            value={createForm.data.template_key}
                            onChange={(event) => createForm.setData('template_key', event.target.value)}
                            required
                        />
                        <Input
                            placeholder="Name"
                            value={createForm.data.name}
                            onChange={(event) => createForm.setData('name', event.target.value)}
                            required
                        />
                        <Textarea
                            placeholder="Description"
                            value={createForm.data.description}
                            onChange={(event) => createForm.setData('description', event.target.value)}
                        />
                        <div className="grid gap-3 md:grid-cols-2">
                            <Select
                                value={createForm.data.channel}
                                onChange={(event) => createForm.setData('channel', event.target.value)}
                            >
                                {options.channels.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </Select>
                            <Select
                                value={createForm.data.audience}
                                onChange={(event) => createForm.setData('audience', event.target.value)}
                            >
                                {options.audiences.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </Select>
                        </div>
                        {createForm.data.channel === 'email' ? (
                            <Input
                                placeholder="Email subject"
                                value={createForm.data.subject}
                                onChange={(event) => createForm.setData('subject', event.target.value)}
                                required
                            />
                        ) : null}
                        <Textarea
                            placeholder="Body"
                            value={createForm.data.body}
                            onChange={(event) => createForm.setData('body', event.target.value)}
                            required
                        />
                        {createForm.errors.body ? (
                            <p className="text-sm text-red-600">{createForm.errors.body}</p>
                        ) : null}
                        {createForm.errors.template_key ? (
                            <p className="text-sm text-red-600">{createForm.errors.template_key}</p>
                        ) : null}
                        <div className="flex flex-wrap gap-2">
                            <Button type="submit" disabled={createForm.processing}>
                                Create template
                            </Button>
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() =>
                                    runPreview(
                                        createForm.data.channel,
                                        createForm.data.channel === 'email' ? createForm.data.subject : null,
                                        createForm.data.body,
                                    )
                                }
                            >
                                Preview (sample data)
                            </Button>
                        </div>
                    </form>
                ) : null}

                {previewError ? <p className="text-sm text-red-600">{previewError}</p> : null}
                {preview ? (
                    <Card className="space-y-2 p-4">
                        <h2 className="text-sm font-semibold text-ink">Preview (not sent)</h2>
                        {preview.subject ? <p className="text-sm text-ink">Subject: {preview.subject}</p> : null}
                        <p className="whitespace-pre-wrap text-sm text-muted">{preview.body}</p>
                    </Card>
                ) : null}

                <div className="grid gap-3">
                    {templates.map((template) => (
                        <Card key={template.id} className="space-y-3 p-4">
                            <div className="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <h2 className="text-base font-semibold text-ink">{template.name}</h2>
                                    <p className="text-xs text-muted">{template.template_key}</p>
                                    <p className="mt-1 text-sm text-muted">{template.description}</p>
                                    <p className="mt-2 text-xs text-muted">
                                        {template.channel_label} · {template.audience_label} ·{' '}
                                        {template.is_active ? 'Active' : 'Inactive'}
                                        {template.is_system_required ? ' · System required' : ''}
                                    </p>
                                </div>
                                {can_manage ? (
                                    <div className="flex flex-wrap gap-2">
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            onClick={() => {
                                                setEditingId(template.id);
                                                editForm.setData({
                                                    name: template.name,
                                                    description: template.description ?? '',
                                                    channel: template.channel,
                                                    audience: template.audience,
                                                    subject: template.subject ?? '',
                                                    body: template.body,
                                                    is_active: template.is_active,
                                                });
                                            }}
                                        >
                                            Edit
                                        </Button>
                                        {! template.is_system_required ? (
                                            <Button
                                                type="button"
                                                variant="secondary"
                                                onClick={() =>
                                                    router.post(`/admin/message-templates/${template.id}/active`, {
                                                        is_active: ! template.is_active,
                                                    })
                                                }
                                            >
                                                {template.is_active ? 'Deactivate' : 'Activate'}
                                            </Button>
                                        ) : null}
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            onClick={() =>
                                                runPreview(
                                                    template.channel,
                                                    template.subject,
                                                    template.body,
                                                )
                                            }
                                        >
                                            Preview
                                        </Button>
                                    </div>
                                ) : null}
                            </div>
                            {editingTemplate?.id === template.id && can_manage ? (
                                <form
                                    className="space-y-3 border-t border-line pt-3"
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        editForm.put(`/admin/message-templates/${template.id}`, {
                                            onSuccess: () => setEditingId(null),
                                        });
                                    }}
                                >
                                    <Input
                                        value={editForm.data.name}
                                        onChange={(event) => editForm.setData('name', event.target.value)}
                                        required
                                    />
                                    <Textarea
                                        value={editForm.data.description}
                                        onChange={(event) => editForm.setData('description', event.target.value)}
                                    />
                                    {editForm.data.channel === 'email' ? (
                                        <Input
                                            value={editForm.data.subject}
                                            onChange={(event) => editForm.setData('subject', event.target.value)}
                                            required
                                        />
                                    ) : null}
                                    <Textarea
                                        value={editForm.data.body}
                                        onChange={(event) => editForm.setData('body', event.target.value)}
                                        required
                                    />
                                    <label className="flex items-center gap-2 text-sm text-ink">
                                        <input
                                            type="checkbox"
                                            checked={editForm.data.is_active}
                                            disabled={template.is_system_required}
                                            onChange={(event) => editForm.setData('is_active', event.target.checked)}
                                        />
                                        Active
                                    </label>
                                    {editForm.errors.body ? (
                                        <p className="text-sm text-red-600">{editForm.errors.body}</p>
                                    ) : null}
                                    <Button type="submit" disabled={editForm.processing}>
                                        Save changes
                                    </Button>
                                </form>
                            ) : (
                                <p className="whitespace-pre-wrap text-sm text-ink">{template.body}</p>
                            )}
                            <p className="text-xs text-muted">
                                Variables: {template.variables.map((v) => `{{${v}}}`).join(', ')}
                            </p>
                        </Card>
                    ))}
                </div>
            </div>
        </AdminLayout>
    );
}
