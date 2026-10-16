# OurNear Hire — Stage 2: Candidate Registration, Profile & Resume QA Report

**Date:** 2026-10-10  
**Environment:** Local `http://127.0.0.1:8000` (`OURNEAR_ALLOW_RUN=local`)  
**Execution mode:** Playwright pytest harness (`qa-agent/tests/stage2/`) — browser automation, **not** a substitute for full human visual QA or every field-level manual pass.

**Stage 1 regression:** Not re-run; Stage 1 canonical run remains `20261010T064328Z-9a191885`.

---

## A. Executive summary

| Metric | Value |
| --- | --- |
| **Inventory — profile modules** | **11** (basic, about, professional, experience, education, skills, projects, certifications, languages, preferences, resume) |
| **Inventory — registration controls** | **9** (+ account-type radio) |
| **Inventory — personal / professional field groups** | **~35+** distinct controls (see registry inventory) |
| **Checklist cases executed (browser)** | **20** |
| **PASS** | **20** |
| **FAIL** | **0** |
| **BLOCKED** | **0** |
| **NOT_TESTED (checklist)** | **0** |
| **NOT_TESTED (extended field matrix)** | **See §F** |
| **MISSING product capabilities** | **0** confirmed in Stage 2 scope |
| **Product defects fixed** | **0** |
| **Canonical Stage 2 run ID** | **`20261010T071623Z-2ba908a5`** |
| **Stage 2 exit** | **`PASS_WITH_KNOWN_ISSUES`** — core checklist green; many individual fields covered by PHPUnit but not every browser field matrix item |

**Release gate:** unchanged — **`insufficient_evidence`**.

**Historical evidence:** Prior **`MAN-CAN-02`** PASS (`20261010T041431Z-9b88be75`) is **not** counted as fresh Stage 2 verification; Stage 2 re-proved bio/designation/skill persistence with new run evidence.

---

## B. Registration and authentication

| Case ID | Route | Expected | Actual | Status | Evidence |
| --- | --- | --- | --- | --- | --- |
| STG2-AUTH-001 | `/login` | Valid login → dashboard | Dashboard visible | PASS | `.../STG2-AUTH-001/dashboard.png` |
| STG2-AUTH-002 | `/login` | Invalid credentials rejected | Stayed on `/login` | PASS | `.../STG2-AUTH-002/invalid-login.png` |
| STG2-AUTH-003 | `/account` | Logout clears session | Guest redirected from `/account` | PASS | `.../STG2-AUTH-003/after-logout.png` |
| STG2-AUTH-004 | `/register` | Empty submit validation | Stayed on `/register` (requestSubmit) | PASS | `.../STG2-AUTH-004/validation.png` |
| STG2-AUTH-005 | `/register` | Duplicate email blocked | Rejected / remained on register | PASS | `.../STG2-AUTH-005/duplicate-email.png` |
| STG2-AUTH-006 | `/register` → `/login` | Unique registration succeeds | Success flash on login; **username login** → dashboard | PASS | `.../STG2-AUTH-006/registered-dashboard.png` |
| STG2-AUTH-007 | `/account` | Guest boundary | Redirect to login | PASS | `.../STG2-AUTH-007/guest-account.png` |

**Verified product behavior (not a defect):** Registration **`store`** redirects to **`/login`** with success message (does not auto-session). New accounts with unverified email must sign in with **username**, not email (`AccountCredentialService`).

---

## C. Personal and professional profile (browser)

| Case ID | Focus | Persistence | Status | Evidence |
| --- | --- | --- | --- | --- |
| STG2-PRO-001 | Bio (`#about`) | Hard reload | PASS | `.../STG2-PRO-001/bio-after-reload.png` |
| STG2-PRO-002 | Bio | Logout → login | PASS | `.../STG2-PRO-002/bio-after-relogin.png` |
| STG2-PRO-003 | `current_designation` | Hard reload | PASS | `.../STG2-PRO-003/designation-reload.png` |
| STG2-PRO-004 | Skill add (`#skills`) | Reload + reopen module | PASS | `.../STG2-PRO-004/skill-reload.png` |
| STG2-PRO-005 | Completion module hub | — | PASS | `.../STG2-PRO-005/modules-hub.png` |
| STG2-PRO-006 | Privacy `#privacy` | — | PASS | `.../STG2-PRO-006/privacy.png` |
| STG2-MOB-001 | Bio mobile 390×844 | Save | PASS | `.../STG2-MOB-001/mobile-bio.png` |

---

## D. Dashboard and completion alignment

| Case ID | Expected | Actual | Status | Evidence |
| --- | --- | --- | --- | --- |
| STG2-DASH-001 | Completion % non-decreasing after bio save | Before ≤ after | PASS | `.../STG2-DASH-001/completion.png` |
| STG2-DASH-002 | Next-action links into profile workflow | href under `/account` | PASS | `.../STG2-DASH-002/next-action.png` |

---

## E. Resume library and builder

| Case ID | Route | Expected | Actual | Status | Evidence |
| --- | --- | --- | --- | --- | --- |
| STG2-RES-001 | `/account/resumes` | Fixture library resume visible | Hub shows My Resume / fixture content | PASS | `.../STG2-RES-001/resumes-hub.png` |
| STG2-RES-002 | `/account/resumes/builder` | Builder loads | Heading visible | PASS | `.../STG2-RES-002/builder.png` |
| STG2-RES-003 | `/account/resumes` | Survives reload | My Resume still visible | PASS | `.../STG2-RES-003/resumes-reload.png` |

**PHPUnit (broader resume rules):** upload validation, replace, primary flag, cross-user denial — see §H.

---

## F. Extended inventory — NOT_TESTED in Stage 2 browser suite

These controls exist in the product but were **not** individually exercised in Playwright Stage 2 (many are covered by **`AccountCandidateProfessionalTest`** / **`AccountCandidateResumeDocumentTest`**):

| Area | Items NOT_TESTED (browser) |
| --- | --- |
| Personal basic | Photo upload/remove, username change, mobile, location, preferred language |
| About | `professional_headline` isolated save |
| Professional core | Industry/category selects, salary, notice period, employment preference form |
| Experience | Add/edit/delete employment history rows |
| Education | Add/edit education entries |
| Projects / certifications / languages | Full CRUD flows |
| Preferences | Preferred locations/types, relocate, availability |
| Resume | Upload/replace invalid file, download, delete, builder field edits, print |
| Auth | Password reset, forgot password, authenticated user visiting `/register` redirect |
| Security | UI attempt to open another candidate’s `/account/*` (PHPUnit covers API/policy) |
| Human-only | Visual layout, print preview quality, password strength UX |

---

## G. Defect register

| ID | Severity | Summary | Resolution |
| --- | --- | --- | --- |
| — | — | No confirmed Stage 2 **product** defects | — |

### Harness / environment notes (not product defects)

| ID | Detail |
| --- | --- |
| STG2-HARNESS-001 | Rapid repeated `/login` POSTs hit **`throttle:account-login` (429)** during suite development; Stage 2 login helper now reuses cookies, retries 429, and dismisses Inertia error overlay. |
| STG2-HARNESS-002 | Registration test **STG2-AUTH-006** uses **username** login post-register (unverified email rule). |

**Files changed (QA only):**

- `qa-agent/registry/stage_2_candidate_profile.yaml`
- `qa-agent/tests/stage2/conftest.py`
- `qa-agent/tests/stage2/test_stage2_candidate_profile.py`
- `qa-agent/pyproject.toml` (marker `stage2`)

---

## H. Automated regression and build

| Command | Exit | Result |
| --- | --- | --- |
| `pytest tests/stage2 -v --ournear-suite stage2` | **0** | **20 passed**, ~416s |
| `php artisan test --filter=AccountCandidate` | **0** | **60 passed**, **845 assertions**, ~310s |
| `ournear-qa report --run-id 20261010T071623Z-2ba908a5 --html --json --ci-exit` | — | `ci_exit_code: 4` (targeted suite semantics) |
| `npx tsc --noEmit` / `npm run build` | **Not run** | No `resources/js` product changes |
| Full `php artisan test` | **Not re-run** | Prior full suite **712 passed** (2026-10-10, `storage/logs/phpunit-full-20261010-session2.txt`) still applicable — no product code changed in Stage 2 |

---

## I. Execution evidence

| Artifact | Path |
| --- | --- |
| Checklist registry + field inventory | `qa-agent/registry/stage_2_candidate_profile.yaml` |
| Structured results | `qa-agent/artifacts/stage2/20261010T071623Z-2ba908a5/stage2_results.json` |
| Screenshots | `qa-agent/artifacts/stage2/evidence/20261010T071623Z-2ba908a5/STG2-*/` |
| Run record | `qa-agent/artifacts/20261010T071623Z-2ba908a5/run-record.json` |
| HTML report | `qa-agent/artifacts/reports/20261010T071623Z-2ba908a5/dashboard.html` |

**Viewports:** desktop **1400×900** (default); mobile **390×844** (STG2-MOB-*).

**Fixture account:** `qa-candidate@example.test` / local `Password1!` via `php artisan qa:hiring-fixtures`.

---

## J. Stage exit decision

| Decision | **`PASS_WITH_KNOWN_ISSUES`** |
| --- | --- |
| **Rationale** | All **20** Stage 2 checklist cases **PASS** with screenshots. Extended per-field browser matrix (§F) and human visual sign-off remain open. PHPUnit provides strong coverage for professional/resume authorization and CRUD not repeated in browser. |
| **Not claimed** | Every dropdown option, employment/education CRUD in browser, resume upload/replace UI, builder edits, employer-view resume parity, staging. |

---

## K. Stage 3 preview — candidate job application & application history

**Next stage:** End-to-end **apply** and **application lifecycle** (candidate side).

| Area | Scope |
| --- | --- |
| Public apply | `/jobs/{slug}/apply`, guest redirect, authenticated apply with profile resume |
| Applications list | `/account/applications`, status labels, persistence, filters if present |
| Application detail | Timeline, documents, status messaging |
| Duplicate apply | Same job guard |
| Resume snapshot | Application uses snapshot at apply time (not live library replace) — align with `CAN-07`, `X-03`, journey `JRN-CANDIDATE-APPLY-01` |
| Regression assets | `tests/journey/test_candidate_apply_journey.py`, `ApplicationExperienceTest`, `MAN-CAN-*` apply-related steps |
| Out of scope for Stage 3 start | Employer review workspace, admin ATS (Stage 4+) |

Stage 3 must not be marked complete until evidence-backed browser + targeted PHPUnit results exist.
