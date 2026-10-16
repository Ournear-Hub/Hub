# GAP-BRAND — Mobile / admin branding sync

| Field | Value |
| --- | --- |
| Status | **IMPLEMENTED** — `GET /api/v1/site/branding`; see `BACKEND_STAGING_COMPLETION_REPORT.md` |
| Current mobile | `HireBrandMark`, `OurNearAuthHeader` use static “OurNear Hire” + palette tokens |

## Product requirement

Logo and application name configured in the **website admin panel** should drive mobile auth/splash branding without a separate mobile-only store.

## Required backend exposure (authorize separately on Laravel)

Add a **public JSON** contract (no admin session), for example:

| Item | Example |
| --- | --- |
| Method/path | `GET {OURNEAR_API_BASE}/api/v1/...` (exact path TBD by backend) |
| Fields | `app_name`, `logo_url` (absolute HTTPS URL), optional `tagline` |
| CORS | Allow mobile/web origins as for jobs API |

Probed on local Laravel (2026-10-16): `/api/v1/branding`, `/api/v1/public/branding`, `/api/v1/settings/branding` → **404**.

## Mobile readiness

`HireBrandingService` (see `lib/core/branding/hire_branding_service.dart`) returns **fallback** branding until section 2 of the API register documents the call. Then wire `fetchBranding()` in `HireApiService` and refresh UI via `HireBrandingController`.

Do not scrape HTML or admin URLs from `OURNEAR_WEB_BASE`.
