import { Head, usePage } from '@inertiajs/react';
import Footer from '@/Components/Public/Footer';
import Header from '@/Components/Public/Header';
import SocialBar from '@/Components/Public/SocialBar';
import type { LayoutProps } from '@/types/layout';
import type { SharedPageProps } from '@/types/page';

export default function PublicLayout({ title, description, children }: LayoutProps) {
    const page = usePage<SharedPageProps>().props;
    const seo = page.seo;
    const pageSeo = page.pageSeo;
    const documentTitle = page.cms?.seo_title || pageSeo?.title || title || seo?.default_title;
    const documentDescription =
        pageSeo?.description || description || page.cms?.meta_description || seo?.default_description || '';

    return (
        <div className="flex min-h-screen flex-col overflow-x-hidden bg-canvas text-ink">
            <Head title={documentTitle}>
                {page.branding?.favicon_url ? (
                    <link head-key="favicon" rel="icon" href={page.branding.favicon_url} />
                ) : page.branding?.icon_url ? (
                    <link head-key="favicon" rel="icon" href={page.branding.icon_url} />
                ) : null}
                {documentDescription ? (
                    <meta head-key="description" name="description" content={documentDescription} />
                ) : null}
                {pageSeo?.canonical ? <link head-key="canonical" rel="canonical" href={pageSeo.canonical} /> : null}
                {pageSeo?.robots ? <meta head-key="robots" name="robots" content={pageSeo.robots} /> : null}
                {pageSeo?.og_type ? <meta head-key="og:type" property="og:type" content={pageSeo.og_type} /> : null}
                {pageSeo?.og_title ? <meta head-key="og:title" property="og:title" content={pageSeo.og_title} /> : null}
                {pageSeo?.og_description ? (
                    <meta head-key="og:description" property="og:description" content={pageSeo.og_description} />
                ) : null}
                {pageSeo?.og_url ? <meta head-key="og:url" property="og:url" content={pageSeo.og_url} /> : null}
                {pageSeo?.og_site_name ? (
                    <meta head-key="og:site_name" property="og:site_name" content={pageSeo.og_site_name} />
                ) : null}
                {pageSeo?.og_image ? <meta head-key="og:image" property="og:image" content={pageSeo.og_image} /> : null}
                {pageSeo?.twitter_card ? (
                    <meta head-key="twitter:card" name="twitter:card" content={pageSeo.twitter_card} />
                ) : null}
                {pageSeo?.twitter_title ? (
                    <meta head-key="twitter:title" name="twitter:title" content={pageSeo.twitter_title} />
                ) : null}
                {pageSeo?.twitter_description ? (
                    <meta
                        head-key="twitter:description"
                        name="twitter:description"
                        content={pageSeo.twitter_description}
                    />
                ) : null}
                {pageSeo?.twitter_image ? (
                    <meta head-key="twitter:image" name="twitter:image" content={pageSeo.twitter_image} />
                ) : null}
            </Head>
            <SocialBar />
            <Header />
            <main className="flex-1">{children}</main>
            <Footer />
        </div>
    );
}
