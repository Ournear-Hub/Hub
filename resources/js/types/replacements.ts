export type ReplacementLineage = {
    id: number;
    business_id: string | null;
    status?: string;
    name?: string | null;
};

export type ReplacementListItem = {
    id: number;
    business_id: string | null;
    status: string;
    reason: string;
    requested_at: string | null;
    updated_at: string | null;
    eligibility?: string | null;
    closure_date?: string | null;
    operational_stage?: string | null;
    original_candidate?: { id: number | null; business_id: string | null; name: string | null } | null;
    original_placement?: ReplacementLineage | null;
    employer: {
        id: number;
        business_id: string | null;
        company_name: string;
        workspace_url?: string | null;
    } | null;
    job: { id: number; business_id: string | null; title: string; slug?: string } | null;
    original_employee: { id: number; business_id: string | null; name: string | null } | null;
    replacement_candidate: { name: string | null; application_id: string | null };
    replacement_employee: { id: number; business_id: string | null; name: string | null } | null;
};

export type ReplacementDetail = ReplacementListItem & {
    notes: string | null;
    requested_by: string | null;
    completed_at: string | null;
    cancelled_at: string | null;
    can_identify_candidate: boolean;
    can_cancel: boolean;
    original_placement: ReplacementLineage | null;
    original_application: ReplacementLineage | null;
    original_submission: ReplacementLineage | null;
    replacement_application: ReplacementLineage | null;
    replacement_submission: ReplacementLineage | null;
    replacement_placement: ReplacementLineage | null;
    chain?: Array<{ key: string; label: string; value?: string | null; href?: string | null; status?: string | null }>;
    workflow?: Array<{ key: string; label: string; value?: string | null; complete?: boolean; current?: boolean; maps_to?: string }>;
    links?: Record<string, string | null | undefined>;
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

export type EligibleReplacementApplication = {
    id: number;
    business_id: string | null;
    name: string | null;
    status: string;
};
