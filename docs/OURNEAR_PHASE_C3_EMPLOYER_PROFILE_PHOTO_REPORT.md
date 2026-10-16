# Phase C3 — Employer profile photo / company logo

**Date:** 2026-10-11  
**Agent:** 1 (development)  
**Status:** **PHASE C3 IMPLEMENTED — AWAITING AGENT 2 QA**  
**Commit / push / deploy:** **None**

---

## 1. Finding (inspection)

The backlog item **REG-PROD-006** assumed greenfield work. Code inspection shows the **employer company logo already uses the canonical account photo pipeline** shared with candidates:

| Requirement | Implementation |
| --- | --- |
| Upload UI | `resources/js/Pages/Account/Profile.tsx` — employer **Company logo** section (`photo` file input, remove via `DELETE /account/profile/photo`) |
| Profile save with logo | Same page — `form.put('/account/profile', { forceFormData: true })` sends `photo` / `remove_photo` |
| Dedicated photo route | `POST account/profile/photo` → `ProfileController::updatePhoto` |
| Validation | `UpdateAccountPhotoRequest`, `UpdateProfileRequest` — `UserPhotoService` max 5120 KB, jpg/png/webp |
| Storage | `UserPhotoService::store()` → `public` disk under `users/`; `delete()` on replace/remove |
| Persistence | `users.photo_path` via `AccountProfileService::applyPhoto()` |
| Display after reload | `UnifiedProfilePresenter` / `toAccountProfileArray()` → `photo_url` via authorized `users.photo.show` |
| Fallback | UI “No logo” placeholder when `photo_url` null |
| Authorization | `UserPolicy::viewPhoto` — owner or admin with permission; update only own user via `authorize('update', $user)` |
| Public jobs/companies | `EmployerProfile` / job cards use `UserPhotoService::publicUrl($employer->user?->photo_path)` |

**Genuine gap:** No **employer-specific** automated regression (candidate coverage exists in `CorrectionPassTest::test_profile_photo_uses_authorized_route_and_blocks_unauthorized_access`).

**No application code changes** were required for persistence behavior.

---

## 2. Files changed (this batch)

| File | Change |
| --- | --- |
| `tests/Feature/EmployerProfilePhotoTest.php` | **Added** — employer upload, profile PUT replace (old file deleted), remove, invalid file rejection, cross-employer photo access forbidden |
| `docs/OURNEAR_PHASE_C3_EMPLOYER_PROFILE_PHOTO_REPORT.md` | **Added** (this report) |
| `docs/OURNEAR_RELEASE_ISSUE_REGISTER.md` | Builder status for REG-PROD-006 |

---

## 3. Validation

| Command | Result |
| --- | --- |
| `php artisan test --filter=EmployerProfilePhotoTest` | **PASS** — 2 tests, 35 assertions (6.56s, Agent 1 verify 2026-10-11) |
| `npm run typecheck` | **PASS** (exit 0) |
| `npm run build` | **PASS** (exit 0, Vite ~74s) |

**Related existing tests (not re-run this pass):** `CorrectionPassTest` (candidate photo + authorized route), `AccountUserFoundationTest` (account photo foundation), `EmployerAccountWorkspaceTest` (profile PUT validation).

**Database-dependent / browser:** PHPUnit uses the project test harness (in-memory/sqlite as configured); **no browser persistence evidence** from Agent 1. Agent 2 must confirm upload → reload → re-login on a live app stack. **REG-ENV-001** status in the issue register may differ by machine; do not treat Agent 1 runs as QA environment proof.

---

## 4. Remaining blockers

- **Agent 2** must independently verify employer logo upload, refresh persistence, and responsive UI on `/account/profile` (edit mode).
- **REG-BRW-004** (C2 screening compare panel) and release verdict **`insufficient_evidence`** unchanged.

---

## 5. Agent 2 handoff — browser scenarios

1. Sign in as **QA employer A** (fixtures). Open **Account → Profile** → edit → **Company logo** — upload JPG/PNG ≤ 5 MB → save → **reload** → logo visible.
2. **Replace** logo — confirm new image shows; no broken `/users/{id}/photo` URL.
3. **Remove logo** — confirm placeholder “No logo” and null `photo_url` after reload.
4. Upload **invalid** file (e.g. PDF) — inline validation error, no persistence.
5. **Employer B** cannot view **Employer A** photo URL (403) when logged in as B.
6. Optional: public **company** / **job** card shows logo when sharing enabled and photo set.

---

*End of Phase C3 report.*
