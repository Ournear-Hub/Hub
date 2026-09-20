export type MediaOption = {
    id: number;
    url: string | null;
    original_name: string;
};

export type MediaAssetAdmin = {
    id: number;
    url: string | null;
    original_name: string;
    mime_type: string;
    extension: string;
    size_bytes: number;
    size_label: string;
    created_at: string | null;
    in_use: boolean;
    used_on: string[];
};

export type SocialLinkAdmin = {
    id: number;
    platform: string;
    label: string;
    url: string;
    is_enabled: boolean;
    sort_order: number;
};

export type PublicSocialLink = {
    platform: string;
    label: string;
    href: string;
};
