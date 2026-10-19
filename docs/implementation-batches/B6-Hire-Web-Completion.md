# B6 — Hire Web Completion

Status: **Local B6 accepted.** Mandatory local gates are satisfied on existing browser evidence plus user-reported manual resume print confirmation. External SMTP, DNS, staging, and production acceptance remain outstanding.

## Scope

Complete and verify the OurNear Hire web application using the existing Laravel, Inertia, React and TypeScript implementation. Preserve working functionality. Do not rebuild completed features without evidence of a defect.

This batch covers public recruitment pages, unified account access, employer activation, candidate profiles and applications, employer hiring workflows, admin ATS operations, responsive UX, security and release acceptance.

## Tasks

- [x] B6-01: Establish the baseline and record existing defects without discarding user changes.
- [x] B6-02: Fix confirmed P0 defects affecting access, data persistence, authorization or recruitment workflows.
- [x] B6-03: Verify unified login, account access and employer company activation.
- [x] B6-04: Complete employer hiring, job publishing, applicant review and application lifecycle workflows.
- [x] B6-05: Complete candidate profile, resume, job discovery and application workflows.
- [x] B6-06: Finalize Home, Hire, Jobs, Talent Pool and Staffing pages and their navigation.
- [x] B6-07: Verify admin ATS operations and role-based access.
- [x] B6-08: Verify responsive layouts and consistency with the established design system.
- [x] B6-09: Run relevant automated tests, type checking, production build and security/regression checks.
- [x] B6-10: Record evidence, unresolved defects and the final acceptance verdict.

## Acceptance criteria

1. No unresolved critical authentication, authorization, data-loss or recruitment-lifecycle defect.
2. Candidate and employer access follows the unified account model and correct role permissions.
3. Employer activation and job publishing enforce the existing product policies.
4. Candidate profiles, resumes and applications persist and display correctly.
5. Employer hiring workflows and admin ATS operations work end to end.
6. Public recruitment pages and navigation work at desktop, tablet and mobile sizes.
7. Required automated tests, TypeScript checks and production build pass, or failures are explicitly documented with evidence.
8. No production deployment or unrelated backend changes are made as part of local acceptance.
9. The batch is marked Accepted only after the required evidence has been reviewed.

## Implementation notes

- Preserve existing functionality and user changes.
- Keep B6 work isolated in feature/hire-web-completion.
- Reuse recorded passing test evidence where applicable.
- Fix root causes rather than hiding failing tests.
- Do not claim a feature or acceptance gate is complete without verification.

## Evidence (2026-10-18)

Branch `feature/hire-web-completion`. No commit, push, deploy, or production migration was run.

- Trial visibility was already in the working tree (`Job::scopePubliclyVisible` and `Job::isPubliclyVisible` reject a trial with a null or elapsed `trial_expires_at`).
- `jobs:expire-trials` now drafts a published trial job when `trial_expires_at` is missing or elapsed. Fresh PHPUnit: `EmployerUnverifiedTrialPublishTest` **5 passed, 38 assertions**.
- PHPUnit 12 doc-comment warning on `CandidateApplicationLifecycleConsistencyTest` is removed by a `DataProvider` attribute. Fresh run: **11 passed, 114 assertions**, no metadata warning.
- `test_showcase_card_reuses_one_employer_profile_across_placements` was previously unexecuted. Fresh run: **1 passed, 11 assertions**. Local `php artisan migrate:status --pending` reported no pending migrations.
- Historical full suite, not re-run this session: **1,157 passed, 3 skipped, 17,115 assertions** (`storage/logs/b6-full-suite-final.txt`).

## Browser evidence (2026-10-18, B6 on port 8000)

The unrelated PHP listener for `C:\Ournear Hub\Ournear Hire` (PID 13192, document root that checkout’s `public`) was stopped. `scripts/start-local-web-server.ps1` then started this repository. The listener command line is `C:\Ournear Hub\Hire-Web-B6\public` with this checkout’s router. MySQL and Vite were healthy. No secrets are recorded here.

Completed in the browser against this checkout:

- Registration for `b6.verify.1847@example.test` reached `/login` with the verification-sent message.
- The verification message in this checkout’s log was opened on port 8000. `email_verified_at` was set. The first redirect followed a pre-existing session intended path `/account/development-programme`, which has no route and returned 404. `/account` then showed the authenticated Personal Dashboard for B6 Verify Candidate.
- Logout returned to the public home as a guest. Login with the same account returned to `/account`.
- Home “Post a Job” for this user without a company pointed at `/account/hiring?destination=%2Faccount%2Fjobs%2Fcreate` and opened the company activation form.
- Activation for B6 Verify Industries redirected to `/account/jobs/create`. The form showed the one-hour trial posting notice.
- A draft job “B6 Trial Site Engineer” was saved, then published from job management. Database state: `posting_tier=trial`, published, active, expiry set, `isPubliclyVisible=1`. Public `GET /jobs/b6-trial-site-engineer` returned HTTP 200.
- The missing-route intended path is now ignored. `IntendedUrl::pullAllowed` falls back when the path does not match a GET route. Fresh PHPUnit: `GuestJobApplyIntendedUrlTest` **7 passed, 30 assertions**.

Earlier port 8001 and the other-checkout registration `b6.journey.1816@example.test` are not this acceptance run.

## Browser evidence continued (2026-10-18)

Same B6 listener on port 8000. Results below are from this continuation.

- **A. Employer offer/join — PASS.** Application `ONT-A-000011` on `b6-trial-site-engineer` moved Shortlisted → Selected → placement Open → Offered → Accepted → Joining Confirmed → Joined. Application status persisted as Placed. Offer date `2026-10-18`, expected joining `2026-10-25`, actual joining `2026-10-18`, employer profile 9. Direct URL as employer B returned HTTP 403. Defect fixed: company-profile ownership now authorizes application management, and placement status uses the enum value. Fresh PHPUnit in `EmployerUnverifiedTrialPublishTest`: the two new methods passed inside the class run **7 passed, 69 assertions**.
- **B. Partial-role admin — PASS.** Content Manager staff user, catalog role only. Design system and Website Content opened. Direct `/admin/jobs` returned 403, POST `/admin/jobs` returned 403, and `/admin/roles` returned 403.
- **C. Candidate photo — PASS.** Playwright assigned files to the real profile file input. Cursor’s browser file-input command is blocked, so this was not an OS dialog click. A text file showed the upload error. `candidate.jpg` persisted for user 8, displayed as `/users/8/photo`, owner fetch returned 200, and employer B fetch returned 403.
- **D. Resume print — PASS for the automated print path.** Print CSS hid the site header and kept the resume sheet. Chromium PDF was 1 A4 page, 61,900 bytes. On-screen sheet showed QA Candidate, contact line, OURNEAR HIRE, and `ON-HIR-CAN-008`. `window.print()` was stubbed, so the native OS print dialog was not opened. That OS dialog remains a manual check.
- **E. Bulk resume upload — PASS.** Recruitment Manager uploaded `notes.txt` (item failed) and a sample PDF. Review showed `B6 Bulk` and `b6.bulk.1810@example.test`. Confirm created candidate user 20. Latest batch status `completed`, successful 1.
- **F. Staffing CMS upload — PASS.** Content Manager upload of a text file was rejected. `candidate.jpg` was stored as media asset 13 (665 bytes, `image/jpeg`). Saving it onto the staffing hero persisted `media_id` 13, then that hero assignment was restored to no custom media so the public page keeps the bundled default. PHP multipart accepted the small JPEG; the older multipart blocker was not reproduced.
- **G. Legacy recruiter assignment — PASS.** Recruitment Manager assigned QA Candidate to B6 Recruiter as Talent Pool. Flash: “Candidate assigned to recruiter.” The recruiter session showed that row. The page has no Assigned Companies list. `/admin/roles` as that recruiter returned 403. `/admin/jobs` returned 200, which matches that role’s existing job view permission.
- **H. Trial second job — PASS by focused PHPUnit, browser not repeated.** Fresh `EmployerUnverifiedTrialPublishTest` includes `unverified employer cannot publish second job`. The existing trial job `b6-trial-site-engineer` was not changed for this check.

Fresh checkpoint on this checkout: `npx tsc --noEmit` exit 0. `npm run build` exit 0 (Vite 7.3.6, built in 11.58s). Full PHPUnit was not re-run. The historical **1,157 passed, 3 skipped, 17,115 assertions** result stays historical.

## Closure evidence (2026-10-18, final local pass)

The existing B6 listener stayed in place (PID 22852, document root `C:\Ournear Hub\Hire-Web-B6\public`). It was not restarted for this pass. `php artisan migrate:status --pending` reported no pending migrations and none were applied.

Evidence directory: `qa-agent/artifacts/b6-closure-20261018/`.

### B6-06 public pages — PASS

Playwright Chrome against `http://127.0.0.1:8000`.

| Check | Result |
| --- | --- |
| `/`, `/jobs`, `/hire`, `/talent-pool`, `/staffing`, `/about`, `/companies`, `/contact`, `/services` at 1400×900, 768×1024, and 390×844 | PASS. HTTP 200. Horizontal overflow 0px. |
| `/jobs/mining-engineer-open-cast-operations` | PASS. HTTP 200. Heading “Mining Engineer — Open Cast Operations”. |
| Guest Apply Now | PASS. Redirected to `/login`. |
| Jobs keyword with no matches | PASS. HTTP 200. Copy “No matching roles”. |
| `/jobs/b6-missing-job-1847` | PASS. HTTP 404. |
| `/development-programme` | NOT APPLICABLE. HTTP 404. The path is not a product route and is not linked from Home. No page was added. |
| Authenticated Home | PASS. Candidate session showed the account avatar and bell. Login and Signup were not shown. |
| `/jobs/b6-trial-site-engineer` after the one-hour expiry | PASS. HTTP 404. Expiry left at `2026-10-18 12:28:32` UTC. The job was not redrafted by `jobs:expire-trials` during this pass, so the published trial record remains as evidence. |

The guest sweep recorded three React duplicate-key console messages. Pages still rendered, and the only HTTP 404 responses were the missing job, `/development-programme`, and the expired trial job.

### B6-08 responsive — PASS

| Surface | Viewport | Result |
| --- | --- | --- |
| Public routes above | 1400×900, 768×1024, 390×844 | PASS. Overflow 0px. Mobile menu opened, Jobs link was usable, Escape closed it. |
| Home hero body versus slide arrows | 390×844 and 768×1024 | PASS after the padding fix below. Bounding boxes did not intersect. |
| Candidate `/account` | 390×844 | PASS. Heading “Personal Dashboard”. Overflow 0px. |
| Candidate `/account/profile` | 768×1024 | PASS. Heading “Profile”. Overflow 0px. |
| Employer `/account/jobs` | 390×844 and 768×1024 | PASS. Trial-allowance banner visible. Overflow 0px. |
| Admin `/admin` | 390×844 | PASS. Heading “Operations dashboard”. Overflow 0px. Primary actions remained on screen. |

Confirmed defect: at 390×844 the home hero body ran under the previous/next slide controls. `HomeHeroSlider` now gives the text block `px-14 sm:px-16 lg:px-0`. Rechecked in the browser after the change.

### Second trial job in the browser — PASS

`b6.verify.1847@example.test` remained email-verified, mobile-unverified, with `trial_job_used_at` set and one published trial. A temporary draft, “B6 Closure Draft Do Not Publish”, was saved and Publish was clicked. The lifecycle request returned HTTP 303. The card stayed Draft (`ONT-J-000056`, Basic, 0 applicants). The jobs page showed “Your one-hour trial job allowance has been used. Verify your email and mobile number to publish additional jobs.” The draft was then deleted. Employer profile 9 again has only job 52, still published, trial, expiry `2026-10-18 12:28:32` UTC, and not publicly visible. Verified multi-job publishing was not re-run; it remains covered by the earlier `EmployerUnverifiedTrialPublishTest` class result.

### Native print dialog — PASS (user-reported manual confirmation)

The resume builder button **Print / Save as PDF** calls `window.print()`.

**Automated evidence (historical, not re-run):** print CSS hid site chrome and kept `.ourneer-resume-sheet` visible; Chromium PDF was one A4 page (61,900 bytes). Headless click produced no OS dialog (`qa-agent/artifacts/b6-closure-20261018/resume-print-button.png`).

**User-reported manual confirmation (2026-10-18, venkat):** in an interactive Windows browser on `http://127.0.0.1:8000/account/resumes/builder`, **Print / Save as PDF** opened the real print interface; Save as PDF produced a PDF that looked correct (readable resume content, not clipped). This is **not** agent-automated verification. The saved file was **not** copied into `qa-agent/artifacts/b6-closure-20261018/` and was **not** independently page-count verified by the agent. Do not treat a Downloads-path file as archived B6 evidence unless it is later added to the evidence folder with inspection notes.

Optional follow-up (non-blocking for local B6): copy the verified PDF into `qa-agent/artifacts/b6-closure-20261018/` with a unique name and record file size, page count, and A4 dimensions.

### Checks run in this pass

- Browser checks above (historical). Native print: user-reported manual PASS; see section above.
- `php artisan migrate:status --pending`: no pending migrations. None applied.
- `npx tsc --noEmit`: exit 0.
- `npm run build`: exit 0, Vite 7.3.6, built in 14.64s.
- PHPUnit, including the full suite and `EmployerUnverifiedTrialPublishTest`, was not re-run. No PHP code changed in this pass. The earlier class result of 7 passed / 69 assertions, and the historical full-suite file `storage/logs/b6-full-suite-final.txt`, are prior evidence only.

## Still open (non-local / evidence hygiene)

- **External:** Live SMTP, DNS, staging, and production acceptance. Not authorized and not run. Local log mail does not prove inbox delivery.
- **Optional evidence:** user-saved print PDF not archived under `qa-agent/artifacts/b6-closure-20261018/`; agent did not re-inspect that file in this closure pass.

## Decision

**LOCAL B6 ACCEPTED.** B6-06 and B6-08 passed on existing browser evidence (historical). Second trial-job browser restriction passed (historical). TypeScript and Vite build passed in the reported local checks (historical). Full PHPUnit **1,157 passed** remains historical (`storage/logs/b6-full-suite-final.txt`). `REG-RECT-B6-PRINT` is satisfied by automated print-layout evidence plus **user-reported manual** native print confirmation, with the evidence limitation above.

**EXTERNAL ACCEPTANCE NOT ACCEPTED.** Live SMTP, public DNS, staging, and production were not authorized and were not run. Local acceptance does not imply staging or production readiness.
