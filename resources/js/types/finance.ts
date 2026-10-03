export type FinanceSummary = {
    currency: string;
    billed: string;
    received: string;
    outstanding: string;
    overdue: string;
};

export type FinanceDashboard = {
    currency: string;
    total_invoices: number;
    pending: number;
    paid: number;
    failed: number;
    failed_available: boolean;
    refunded: number;
    refunded_available: boolean;
    cancelled: number;
    draft: number;
    total_invoiced_amount: string;
    total_paid_amount: string;
    total_pending_amount: string;
    recorded_payments: number;
    gateway_configured: boolean;
    gateway_message: string;
};

export type FinanceChainStep = {
    key: string;
    label: string;
    value?: string | null;
    href?: string | null;
    status?: string | null;
};

export type FinanceParty = {
    id: number | null;
    business_id: string | null;
    name?: string | null;
    company_name?: string;
    title?: string;
    slug?: string;
    url?: string | null;
    status?: string;
    workspace_url?: string | null;
};

export type InvoiceListItem = {
    id: number;
    business_id: string | null;
    status: string;
    invoice_type?: string;
    currency: string;
    amount: string;
    gst_rate: string;
    gst_amount: string;
    total_amount: string;
    received_amount: string;
    outstanding_amount: string;
    is_overdue: boolean;
    billed_at: string | null;
    due_at: string | null;
    created_at?: string | null;
    updated_at: string | null;
    candidate?: FinanceParty | null;
    employer: {
        id: number;
        business_id: string | null;
        company_name: string;
        workspace_url?: string | null;
    } | null;
    job: { id: number; business_id: string | null; title: string; slug?: string } | null;
    application?: { id: number; business_id: string | null } | null;
    placement?: { id: number; business_id: string | null; status?: string } | null;
    employee: { id: number; business_id: string | null; name: string | null } | null;
    is_replacement_billing: boolean;
};

export type PaymentItem = {
    id: number;
    business_id: string | null;
    invoice_id: number;
    invoice_business_id: string | null;
    invoice_status?: string | null;
    currency: string;
    amount: string;
    status?: string;
    status_note?: string;
    gateway_configured?: boolean;
    gateway_message?: string;
    gateway_reference?: string | null;
    paid_at: string | null;
    created_at?: string | null;
    method: string;
    reference: string | null;
    notes: string | null;
    recorded_by: string | null;
    candidate?: FinanceParty | null;
    application?: { id: number; business_id: string | null } | null;
    placement?: { id: number; business_id: string | null; status?: string } | null;
    job?: { id: number; business_id: string | null; title: string; slug?: string } | null;
    employer?: {
        id: number;
        business_id: string | null;
        company_name: string;
        workspace_url?: string | null;
    } | null;
    chain?: FinanceChainStep[];
    activities?: Array<{
        id: number;
        type: string;
        body: string | null;
        from_status: string | null;
        to_status: string | null;
        actor_name: string | null;
        created_at: string | null;
    }>;
};

export type InvoiceDetail = InvoiceListItem & {
    notes: string | null;
    issued_at: string | null;
    cancelled_at: string | null;
    created_by: string | null;
    can_issue: boolean;
    can_cancel: boolean;
    can_record_payment: boolean;
    chain?: FinanceChainStep[];
    application: { id: number; business_id: string | null } | null;
    submission: { id: number; business_id: string | null } | null;
    placement: { id: number; business_id: string | null; status?: string } | null;
    replacement: { id: number; business_id: string | null; status?: string } | null;
    payments: PaymentItem[];
    activities: Array<{
        id: number;
        type: string;
        body: string | null;
        from_status: string | null;
        to_status: string | null;
        actor_name: string | null;
        created_at: string | null;
    }>;
};
