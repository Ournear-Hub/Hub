export type StaffRoleOption = {
    id: number;
    name: string;
    slug: string;
    is_locked?: boolean;
    assignable?: boolean;
};

export type UserRoleSummary = {
    id: number;
    name: string;
    slug: string;
};

export type UserListItem = {
    id: number;
    name: string;
    email: string;
    account_type: string;
    is_active: boolean;
    roles: UserRoleSummary[];
    created_at: string | null;
    updated_at: string | null;
};

export type UserDetail = UserListItem & {
    permissions: string[];
    is_super_admin: boolean;
};

export type PermissionItem = {
    id: number;
    name: string;
    group: string;
    label: string;
    group_label?: string;
};

export type PermissionGroup = {
    group: string;
    permissions: PermissionItem[];
};

export type RoleDetail = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    is_locked: boolean;
    users_count: number;
    permissions: PermissionItem[];
};

export type LoginLogItem = {
    id: number;
    user_id: number | null;
    user_name: string | null;
    email: string;
    event: string;
    event_label: string;
    ip_address: string | null;
    user_agent: string | null;
    succeeded: boolean;
    metadata: Record<string, unknown>;
    created_at: string | null;
};

export type ActivityLogItem = {
    id: number;
    actor_id: number | null;
    actor_name: string | null;
    subject_type: string | null;
    subject_id: number | null;
    action: string;
    action_label: string;
    description: string;
    ip_address: string | null;
    metadata: Record<string, unknown>;
    created_at: string | null;
};
