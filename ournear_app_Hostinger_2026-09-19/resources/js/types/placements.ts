export type PlacementLineage = {
    id: number;
    business_id: string | null;
    status?: string;
};

export type PlacementListItem = {
    id: number;
    business_id: string | null;
    status: string;
    created_at: string | null;
    updated_at: string | null;
    expected_joining_date: string | null;
    actual_joining_date: string | null;
    employer: {
        id: number;
        business_id: string | null;
        company_name: string;
        contact_person?: string | null;
        workspace_url?: string | null;
    } | null;
    job: { id: number; business_id: string | null; title: string; slug?: string } | null;
    candidate: { id: number | null; business_id: string | null; name: string | null };
    application: PlacementLineage | null;
    submission: PlacementLineage | null;
    employee: PlacementLineage | null;
};

export type PlacementOffer = {
    id: number;
    offered_at: string | null;
    salary: string | null;
    notes: string | null;
    accepted_at: string | null;
    declined_at: string | null;
    decline_notes: string | null;
    withdrawn_at: string | null;
    created_by: string | null;
};

export type PlacementDetail = PlacementListItem & {
    notes: string | null;
    joining_notes: string | null;
    joining_confirmed_at: string | null;
    joining_confirmed_by: string | null;
    created_by: string | null;
    can_offer: boolean;
    can_accept: boolean;
    can_decline: boolean;
    can_confirm_joining: boolean;
    can_join: boolean;
    can_withdraw: boolean;
    offer: PlacementOffer | null;
    has_resume: boolean;
    resume_url: string | null;
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

export type EmployeeListItem = {
    id: number;
    business_id: string | null;
    joined_at: string | null;
    created_at: string | null;
    employer: PlacementListItem['employer'];
    job: PlacementListItem['job'];
    candidate: PlacementListItem['candidate'];
    application: PlacementLineage | null;
    submission: PlacementLineage | null;
    placement: PlacementLineage | null;
};
