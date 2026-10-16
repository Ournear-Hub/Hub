# Agent 2 — C3 invalid PDF logo browser closure

**Run ID:** `20261011T152000Z-c3-invalid-logo`  
**Date:** 2026-10-11  
**Issue:** REG-PROD-006 (remaining local browser gap)  
**Prior:** `docs/OURNEAR_QA_AGENT2_C6_UPLOAD_CLOSURE_REPORT.md` · run `20261011T142000Z-c6-upload-closure`

---

## 1. Root cause

| Layer | Finding |
| --- | --- |
| **Application (primary)** | Profile save used `router.post` with `onSuccess` that always called `setEditing(false)` for employers. When Laravel/Inertia completed a redirect-back visit with **`page.props.errors`** populated (HTTP **302** on POST, not 422), **`onError` did not run**, edit mode closed, and the logo-field error (`#employer-logo-error`) was hidden — poor employer UX. |
| **Test automation (secondary)** | Run `20261011T142000Z-c6-upload-closure` used `networkidle` too early, compared photo URLs **including `?v=` cache busters**, and treated any “Saving…” match as failure — false **FAIL** while server validation was already correct. |

Server validation and storage were never broken (`EmployerProfilePhotoTest` **PASS** throughout).

---

## 2. Correction

**File:** `resources/js/Pages/Account/Profile.tsx`

- `onSuccess(page)`: if `page.props.errors` is non-empty, map errors onto `useForm`, keep employer **edit mode open**, and **return** without clearing the logo or exiting edit.
- Logo field error: `#employer-logo-error` with `role="alert"` (already present post-build).

**Not changed:** MIME rules, authorization, storage disks, or PDF-as-image acceptance.

**Frontend build:** `npm run build` (required for manifest assets).

---

## 3. Focused verification (this run)

**Server:** `scripts/start-local-web-server.ps1` · `php scripts/probe-web-php-upload.php` **PASS**

| Check | Result |
| --- | --- |
| `EmployerProfilePhotoTest` | **2/2 PASS** |
| **C3-INVALID-PDF-UX** — PDF logo → inline error, logo path unchanged, Save usable | **PASS** |
| **C3-VALID-RECOVERY** — valid PNG after rejection → success flash + persisted logo | **PASS** |

**Artifacts:** `qa-agent/artifacts/agent2/20261011T152000Z-c3-invalid-logo/invalid_logo_results.json`, `invalid-pdf-error.png`, `diagnose.json`

**Not rerun:** valid-only logo matrix, company-document upload, employer isolation (prior **PASS**).

---

## 4. Register

| Field | Value |
| --- | --- |
| **REG-PROD-006** | **PARTIAL** — local browser acceptance for logo + documents + invalid-logo UX **PASS**; **PHP-FPM/staging multipart NOT TESTED** |
| **Release verdict** | **`insufficient_evidence`** |

---

## 5. Staging / PHP-FPM

Still required: confirm writable `upload_tmp_dir` on hosting stack and rerun focused upload scenarios (not claimed passed here).
