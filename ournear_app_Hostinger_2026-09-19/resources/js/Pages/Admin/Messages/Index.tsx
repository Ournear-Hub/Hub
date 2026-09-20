import { Link, router, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useCallback, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import {
    Button,
    DataTable,
    Drawer,
    FilterBar,
    Input,
    Modal,
    SearchBar,
    Select,
    Textarea,
} from '@/Components/Admin/ui';
import type { DataTableColumn } from '@/Components/Admin/ui';
import type { Paginator } from '@/types/jobs';
import type { ConversationDetail, ConversationListItem } from '@/types/communication';
import type { SharedPageProps } from '@/types/page';

type Props = {
    conversations: Paginator<ConversationListItem>;
    selected: ConversationDetail | null;
    filters: {
        search: string;
        unread: string;
        conversation: string;
    };
    options: {
        admins: Array<{ id: number; name: string; email: string; label: string }>;
        contexts: string[];
    };
};

export default function MessagesIndex({ conversations, selected, filters, options }: Props) {
    const user = usePage<SharedPageProps>().props.auth.user;
    const [composeOpen, setComposeOpen] = useState(false);
    const [recipientId, setRecipientId] = useState('');
    const [subject, setSubject] = useState('');
    const [body, setBody] = useState('');
    const [contextType, setContextType] = useState('');
    const [contextId, setContextId] = useState('');
    const [reply, setReply] = useState('');

    const recipients = options.admins.filter((admin) => admin.id !== user?.id);

    const visit = (overrides: Record<string, string | undefined> = {}) => {
        const next = { ...filters, ...overrides };
        const params: Record<string, string> = {};
        Object.entries(next).forEach(([key, value]) => {
            if (value) {
                params[key] = value;
            }
        });
        router.get('/admin/messages', params, { preserveState: true, preserveScroll: true });
    };

    const closeDrawer = useCallback(() => visit({ conversation: '' }), [filters]);

    const filter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const data = Object.fromEntries(new FormData(event.currentTarget).entries()) as Record<string, string>;
        visit({
            ...data,
            unread: data.unread ? '1' : '',
            conversation: filters.conversation,
        });
    };

    const columns: Array<DataTableColumn<ConversationListItem>> = [
        { key: 'subject', header: 'Subject', className: 'font-medium text-ink', render: (row) => row.subject },
        {
            key: 'participants',
            header: 'Participants',
            render: (row) => row.participants.map((item) => item.name).join(', '),
        },
        { key: 'preview', header: 'Latest', render: (row) => row.latest_preview ?? '—' },
        { key: 'status', header: 'Status', render: (row) => (row.is_unread ? 'Unread' : 'Read') },
        { key: 'updated', header: 'Updated', render: (row) => row.updated_at ?? '—' },
        {
            key: 'actions',
            header: 'Actions',
            render: (row) => (
                <Button variant="ghost" onClick={() => visit({ conversation: String(row.id) })}>
                    Open
                </Button>
            ),
        },
    ];

    return (
        <AdminLayout title="Messages">
            <div
                className={selected ? 'pointer-events-none' : undefined}
                inert={selected ? true : undefined}
                aria-hidden={selected ? true : undefined}
            >
                <div className="mb-6 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight text-ink">Messages</h1>
                        <p className="mt-1 text-sm text-muted">
                            Admin-to-Admin internal messages. Candidate and employer portal messaging is not available yet.
                        </p>
                    </div>
                    <Button disabled={recipients.length === 0} onClick={() => setComposeOpen(true)}>
                        New message
                    </Button>
                </div>
                <FilterBar onSubmit={filter}>
                    <SearchBar defaultValue={filters.search} placeholder="Search messages" />
                    <label className="flex items-center gap-2 text-sm text-ink">
                        <input type="checkbox" name="unread" value="1" defaultChecked={filters.unread === '1'} />
                        Unread only
                    </label>
                    <Button type="submit">Filter</Button>
                </FilterBar>
                <DataTable
                    columns={columns}
                    rows={conversations.data}
                    rowKey={(row) => row.id}
                    emptyTitle="No conversations match these filters"
                    emptyDescription="Compose a message to another Admin. Messages are kept as communication history."
                    onRowClick={(row) => visit({ conversation: String(row.id) })}
                    selectedKey={selected?.id ?? null}
                />
                <Pagination links={conversations.links} />
            </div>

            <Drawer open={selected !== null} title={selected?.subject ?? 'Conversation'} size="xl" onClose={closeDrawer}>
                {selected ? (
                    <div className="space-y-4 text-sm">
                        {selected.context_url ? (
                            <p>
                                Related:{' '}
                                <Link href={selected.context_url} className="font-medium text-brand">
                                    {selected.context_label}
                                </Link>
                            </p>
                        ) : (
                            <p className="text-muted">No related recruitment record.</p>
                        )}
                        <ul className="space-y-2">
                            {selected.messages.map((message) => (
                                <li key={message.id} className="rounded-md bg-surface px-3 py-2">
                                    <p className="font-medium text-ink">{message.sender_name}</p>
                                    <p className="whitespace-pre-wrap">{message.body}</p>
                                    <p className="text-xs text-muted">{message.created_at}</p>
                                </li>
                            ))}
                        </ul>
                        <div className="space-y-2">
                            <Textarea value={reply} onChange={(event) => setReply(event.target.value)} placeholder="Reply" />
                            <Button
                                disabled={!reply.trim()}
                                onClick={() => {
                                    router.post(`/admin/messages/${selected.id}`, { body: reply });
                                    setReply('');
                                }}
                            >
                                Send reply
                            </Button>
                        </div>
                    </div>
                ) : null}
            </Drawer>

            <Modal
                open={composeOpen}
                title="New message"
                onClose={() => setComposeOpen(false)}
                footer={
                    <>
                        <Button variant="secondary" onClick={() => setComposeOpen(false)}>
                            Cancel
                        </Button>
                        <Button
                            disabled={!recipientId || !subject || !body}
                            onClick={() => {
                                router.post('/admin/messages', {
                                    recipient_id: recipientId,
                                    subject,
                                    body,
                                    context_type: contextType || undefined,
                                    context_id: contextId || undefined,
                                });
                                setComposeOpen(false);
                            }}
                        >
                            Send
                        </Button>
                    </>
                }
            >
                <div className="space-y-3 text-left text-sm">
                    <p>Only Admin users can receive in-app messages in A.8.</p>
                    <Select value={recipientId} onChange={(event) => setRecipientId(event.target.value)} aria-label="Recipient">
                        <option value="">Select recipient</option>
                        {recipients.map((admin) => (
                            <option key={admin.id} value={admin.id}>
                                {admin.label}
                            </option>
                        ))}
                    </Select>
                    <Input value={subject} onChange={(event) => setSubject(event.target.value)} placeholder="Subject" />
                    <Textarea value={body} onChange={(event) => setBody(event.target.value)} placeholder="Message" />
                    <Select value={contextType} onChange={(event) => setContextType(event.target.value)} aria-label="Related record type">
                        <option value="">No related record</option>
                        {options.contexts.map((item) => (
                            <option key={item} value={item}>
                                {item}
                            </option>
                        ))}
                    </Select>
                    {contextType ? (
                        <Input value={contextId} onChange={(event) => setContextId(event.target.value)} placeholder="Related record ID" />
                    ) : null}
                </div>
            </Modal>
        </AdminLayout>
    );
}
