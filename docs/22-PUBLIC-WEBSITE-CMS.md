# Public website and CMS

## Public pages — IMPLEMENTED

Home, jobs, about, services, for-employers, contact, legal, freelancer intake, public profiles. Controllers: `PublicSiteController`, `PublicProfileController`, `FreelancerIntakeController`, `PublicDiscoveryController` (robots/sitemap).

## Public platform destinations — IMPLEMENTED

Primary marketing navigation (`resources/js/content/navigation.ts`), in this order: Jobs (`/jobs`), Hiring (`/hire`), Talent Pool (`/talent-pool`), Staffing (`/staffing`), Companies (`/companies`), About Us (`/about`). Contact remains `/contact` (footer; not primary nav). `/freelancers`, `/services`, `/for-employers` still exist but are not primary nav.

| URI | Name | Controller | Page | Notes |
| --- | --- | --- | --- | --- |
| `/companies` | `companies.index` | `PublicPlatformController@companies` | `Companies/Index` | Lists `employer_profiles` (keyword/industry), open job counts via `publiclyVisible()` |
| `/hire` | `hire` | `hire` | `Platform/Hire` | Marketing; not Admin hiring |
| `/talent-pool` | `talent-pool` | `talentPool` | `Platform/TalentPool` | Candidates with public share `enabled`; headline/location gated by share flags |
| `/staffing` | `staffing` | `staffing` | `Platform/Staffing` | Marketing; not Admin employees module |

Do **not** confuse `/hire` (public) with `/admin/hiring` (staff Command Center).

Search & Discovery as a dedicated product (recommendations, faceted talent search beyond this listing) remains [PLANNED].

Content: `WebsitePage`, `WebsiteSection`, `ContentItem`, `WebsiteSetting`, `PublicSeoService`, `BrandingAssetService`.

Admin: `WebsiteContentController`, `MediaSocialController` (`website.manage`).

## SEO

`/robots.txt`, `/sitemap.xml`. Page SEO fields on website settings/pages.

## Enquiries

`EmployerEnquiry`, `ContactEnquiry` — public POST throttled; admin status/notes/convert to CRM lead.

## Analytics tag

Optional `ANALYTICS_*` env — not an internal BI warehouse. Admin analytics UI: `OperationalAnalyticsService` + `Admin\AnalyticsController` (operational metrics from DB).
