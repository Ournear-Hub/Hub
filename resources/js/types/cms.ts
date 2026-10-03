export type CmsItem = {
    id: number;
    title: string;
    description: string | null;
    icon: string | null;
    image_path: string | null;
    image_url: string | null;
    bullets: string[];
    sort_order: number;
    is_active: boolean;
    media_id?: number | null;
    group_key?: string;
    type?: string;
};

export type CmsSection = {
    key: string;
    label: string;
    heading: string | null;
    subtitle: string | null;
    body: string | null;
    primary_button_label: string | null;
    secondary_button_label: string | null;
    image_path: string | null;
    image_url: string | null;
    extra: Record<string, string>;
    is_active: boolean;
    media_id?: number | null;
    supports_image?: boolean;
    items: CmsItem[];
};

export type CmsPage = {
    slug: string;
    title: string;
    preview_url: string;
    seo_title?: string | null;
    meta_description?: string | null;
    sections: CmsSection[];
};

export function cmsSection(page: CmsPage | null | undefined, key: string): CmsSection | undefined {
    return page?.sections.find((section) => section.key === key);
}

export function activeItems(section: CmsSection | undefined): CmsItem[] {
    return (section?.items ?? []).filter((item) => item.is_active);
}
