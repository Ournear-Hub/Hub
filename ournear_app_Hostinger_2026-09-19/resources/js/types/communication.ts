export type AdminAlertItem = {
    id: string;
    event: string;
    title: string;
    body: string;
    url: string | null;
    context?: Record<string, unknown>;
    read_at: string | null;
    created_at: string | null;
    is_unread: boolean;
};

export type ConversationListItem = {
    id: number;
    subject: string;
    context_type: string | null;
    context_id: number | null;
    context_url: string | null;
    context_label: string | null;
    is_unread: boolean;
    updated_at: string | null;
    latest_preview: string | null;
    participants: Array<{ id: number; name: string }>;
};

export type ConversationMessage = {
    id: number;
    sender_id: number;
    sender_name: string | null;
    body: string;
    created_at: string | null;
};

export type ConversationDetail = ConversationListItem & {
    created_by: string | null;
    messages: ConversationMessage[];
};
