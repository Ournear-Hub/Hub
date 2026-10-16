# OurNear Hire — Phase D: Admin-managed website branding

**Date:** 2026-10-10  
**Environment:** Local `http://127.0.0.1:8000`  
**Verdict:** **COMPLETE** (PHPUnit + build/typecheck; browser upload exercised via HTTP multipart in PHPUnit)

---

## Root cause

`wordmark_path` and `BrandMark` already supported **logo + companion wordmark**, but **Admin → Branding** only exposed logo/icon/favicon uploads. `WebsiteSettingService::updateBranding()` did not persist `wordmark_path`, so the companion PNG path could not be managed end-to-end.

---

## Reused (no duplicate system)

| Layer | Existing artifact |
| --- | --- |
| Storage | `BrandingAssetService` (`public` disk, `branding/` prefix) |
| Settings row | `website_settings.logo_path`, `wordmark_path`, `icon_path`, `favicon_path` |
| Admin UI | `Admin/Settings.tsx` branding tab |
| Request | `UpdateBrandingRequest` |
| Shared props | `HandleInertiaRequests` → `branding` |
| Display | `BrandMark` + `PublicLayout` favicon |

---

## Files changed

| File | Change |
| --- | --- |
| `app/Services/WebsiteSettingService.php` | Persist `wordmark` upload/remove in `updateBranding()` |
| `app/Http/Requests/Admin/UpdateBrandingRequest.php` | `wordmark` + `remove_wordmark` validation |
| `resources/js/Pages/Admin/Settings.tsx` | Companion wordmark field, client preview, copy |
| `resources/js/Components/BrandMark.tsx` | Logo + wordmark layout; hide redundant text when assets present |
| `resources/js/Layouts/AdminAuthLayout.tsx` | `usePublicBranding` on admin login header |
| `tests/Feature/WebsiteBrandingSettingsTest.php` | Upload, auth, invalid file, replace/remove |

---

## Validation results

| Command | Result |
| --- | --- |
| `php artisan test tests/Feature/WebsiteBrandingSettingsTest.php` | **4 passed**, 46 assertions |
| `npm run typecheck` | **0** |
| `npm run build` | **0** (~99s) |

**PHPUnit coverage:** admin upload logo + wordmark → DB paths → `/storage/branding/…` URLs → Inertia on admin branding tab + public home; recruiter **403**; invalid MIME rejected without path change; logo replace + wordmark remove deletes old wordmark file.

**Browser matrix (manual follow-up optional):** Admin branding tab file picker + live preview is implemented in UI; this pass relied on multipart PHPUnit for save/reload persistence proof.

---

## Integration surfaces

| Surface | Mechanism |
| --- | --- |
| Public header/footer | `BrandMark` + `usePublicBranding` |
| Account (candidate/employer) | `AccountLayout` → `PublicLayout` → `Header` |
| Admin sidebar | `BrandMark` + `usePublicBranding` |
| Admin login | `AdminAuthLayout` + `usePublicBranding` |
| Favicon | `PublicLayout` `Head` from `branding.favicon_url` / `icon_url` |

---

## Asset note for owner

The platform does **not** ship production logo/wordmark PNGs. Upload **logo mark** (square-ish, ~64–128 px) and **companion wordmark** (horizontal PNG, e.g. **320×80** or similar wide aspect) via **Admin → Settings → Branding**. Until both are uploaded, headers fall back to the OurNear / Hire text mark.

---

## Remaining gaps

- Dedicated Playwright journey for admin file picker UX at 390/768/1440 (optional; not blocking Phase D closure).
- **C1** cross-role apply journey — next authorized phase (separate report).
