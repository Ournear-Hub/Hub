export type RelatedRecord = {
    id?: number | null;
    business_id?: string | null;
    name?: string | null;
    title?: string | null;
    company_name?: string | null;
    url?: string | null;
};

export type AdminAlertItem = {
    id: string;
    event: string;
    event_label?: string;
    category?: string | null;
    title: string;
    body: string;
    url: string | null;
    context?: Record<string, unknown>;
    related?: Record<string, RelatedRecord | null>;
    chain?: Array<{
        key: string;
        label: string;
        value?: string | null;
        href?: string | null;
        status?: string | null;
    }>;
    recipient?: { id: number; name: string; email: string } | null;
    read_at: string | null;
    created_at: string | null;
    is_unread: boolean;
    can_toggle_read?: boolean;
};

export type ConversationListItem = {
    id: number;
    subject: string;
    context_type: string | null;
    context_id: number | null;
    context_url: string | null;
    context_label: string | null;
    chain?: Array<{
        key: string;
        label: string;
        value?: string | null;
        href?: string | null;
        status?: string | null;
    }>;
    is_unread: boolean;
    unread_count?: number;
    updated_at: string | null;
    latest_preview: string | null;
    attachments_available?: boolean;
    participants: Array<{ id: number; name: string }>;
};

export type ConversationMessage = {
    id: number;
    sender_id: number;
    sender_name: string | null;
    body: string;
    created_at: string | null;
    attachments_available?: boolean;
    read_state?: string | null;
};

export type ConversationDetail = ConversationListItem & {
    created_by: string | null;
    can_reply?: boolean;
    viewer_last_read_at?: string | null;
    messages: ConversationMessage[];
};
