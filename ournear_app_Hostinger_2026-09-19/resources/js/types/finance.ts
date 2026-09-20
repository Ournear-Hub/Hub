export type FinanceSummary = {
    currency: string;
    billed: string;
    received: string;
    outstanding: string;
    overdue: string;
};

export type InvoiceListItem = {
    id: number;
    business_id: string | null;
    status: string;
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
    updated_at: string | null;
    employer: {
        id: number;
        business_id: string | null;
        company_name: string;
        workspace_url?: string | null;
    } | null;
    job: { id: number; business_id: string | null; title: string; slug?: string } | null;
    employee: { id: number; business_id: string | null; name: string | null } | null;
    is_replacement_billing: boolean;
};

export type PaymentItem = {
    id: number;
    business_id: string | null;
    invoice_id: number;
    invoice_business_id: string | null;
    currency: string;
    amount: string;
    paid_at: string | null;
    method: string;
    reference: string | null;
    notes: string | null;
    recorded_by: string | null;
};

export type InvoiceDetail = InvoiceListItem & {
    notes: string | null;
    issued_at: string | null;
    cancelled_at: string | null;
    created_by: string | null;
    can_issue: boolean;
    can_cancel: boolean;
    can_record_payment: boolean;
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
