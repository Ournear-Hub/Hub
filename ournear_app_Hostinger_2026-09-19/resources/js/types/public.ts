export type SiteContact = {
    email: string;
    phone: string;
    whatsapp?: string;
    address: string;
    hours: string;
    help?: string;
};

export type SiteContent = {
    brand: string;
    tagline: string;
    copyright?: string;
    contact: SiteContact;
};

export type SeoProps = {
    site_name: string;
    default_title: string;
    default_description: string;
};

export type PageSeoProps = {
    title: string;
    description: string;
    canonical: string;
    robots: string;
    og_type: string;
    og_title: string;
    og_description: string;
    og_url: string;
    og_image: string | null;
    og_site_name: string;
    twitter_card: string;
    twitter_title: string;
    twitter_description: string;
    twitter_image: string | null;
};

export type AnalyticsProps = {
    enabled: boolean;
    id: string | null;
};

export type ServiceItem = {
    title: string;
    summary: string;
};
