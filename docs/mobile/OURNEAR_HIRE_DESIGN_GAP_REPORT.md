# OurNear Hire Candidate — Design Gap Report (Phase 1)

| Field | Value |
| --- | --- |
| Document ID | DESIGN-GAP-PHASE1 |
| Date | 2026-10-15 |
| Mobile app | `C:\Ournear Hub\ournear_mobile` |
| Reference assets (documented) | `D:\Ournear Hub\References\` — HireMe Job Portal, Social Network UI, React Job Portal (per project README) |

## Reference discovery note

Template folders are documented outside the mobile repo at `D:\Ournear Hub\References\`. They were **not copied into** `ournear_mobile`. During this task the agent could not enumerate those directories from the sandbox; comparison below uses **README-declared intent**, **master-spec UX goals**, and **source-level alignment** with typical HireMe (job card + list hierarchy) and Social Network UI (profile header + section cards). React Job Portal is treated as **visual-only** guidance (typography, card spacing, badges)—no React code imported.

When reference sources become readable on the build machine, re-verify each “Closed” row with side-by-side screenshots.

---

## Screen comparison matrix

| Screen | Current implementation | Reference pattern | Match | Gap (before Phase 1) | Change (Phase 1) | API? | Evidence |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Home | `candidate_home_screen.dart` | HireMe: search + latest list | Partial | Plain job rows; hardcoded teal button | Theme primary button; existing welcome banner | Jobs list API | `candidate_job_discovery_test.dart` |
| Job card | `job_card.dart` | HireMe: logo, title, meta, badges | Partial | Text-only stack, weak hierarchy | Company avatar, badges, location icon, salary emphasis | Optional logo URL from API | `job_card_presentation_test.dart` |
| Jobs tab | `hire_screen.dart` | HireMe list + search | Partial | Same card component | Inherits `JobCard` | GET `/api/v1/jobs` | Existing job tests |
| Job detail | `hire_job_detail_screen.dart` | HireMe detail header + sections | Partial | Flat title list | `JobDetailHeader` + `ProfileSectionCard` sections | GET `/api/v1/jobs/{slug}` | Existing discovery tests |
| Applications | `candidate_applications_screen.dart` | HireMe “my applications” | Good | Signed-out gating | Preserved auth-gated copy | APP list when configured | application flow tests |
| Profile tab | `candidate_direct_profile_screen.dart` + `professional_profile_body.dart` | Social UI: header + sections | Partial | Plain text sections | `ProfileSectionCard` dividers; theme edit button | Profile read blocked | `candidate_profile_ux_test.dart` |
| Login | `login_screen.dart` | HireMe auth forms | Good | Generic app bar only | `HireBrandMark`, `OurNearPasswordField`, `OurNearPrimaryButton`, hire canvas | AUTH login | `login_flow_test.dart`, `hire_design_system_test.dart` |
| Settings | `candidate_settings_screen.dart` | HireMe account/settings | Good | Outlined button rows | `HireSettingsNavTile` cards; `PlatformEmptyState` for unavailable content | Flags only | `account_verification_settings_test.dart` |
| Menu | `candidate_app_menu.dart` | HireMe drawer | Good | — | Preserved order/Profile-only | N/A | menu tests |
| Saved jobs | `saved_jobs_screen.dart` | HireMe saved list | Partial | Plain empty only | `HireNoticeBanner`, `PlatformLoadingState`, retry key `saved-jobs-retry` | **GET list missing** | discovery test |
| Resumes | `documents_screen.dart` | HireMe CV upload | Partial | Plain headings | `ProfileSectionCard`, loading/notice banners | GAP-RESUME | profile tests |
| Applications list | `application_list_tile.dart` | HireMe application rows | Partial | Text-only company | Company avatar row; theme secondary icons | Live list when configured | application tests |

---

## HireMe-aligned job card (closed in Phase 1)

**Reference intent:** Title prominence, company row, optional logo, employment meta, salary, status chips (featured/urgent/closed), bookmark affordance.

**Implementation:** `JobCard`, `JobCompanyAvatar`, `StatusBadge` tones (brand/warning/neutral), theme-aware surfaces via `HireThemeContext`.

**Acceptance:** Widget test asserts badges, company initial, salary; dark theme brightness on card tree.

---

## Social-style profile sections (closed in Phase 1)

**Reference intent:** Section titles with visual separation; header row with avatar and headline.

**Implementation:** `ProfileSectionCard` (title, divider, body on card surface); existing header row retained with theme secondary text for photo disclaimer.

**Acceptance:** Existing profile section keys preserved (`profile-section-*`).

---

## Job detail hierarchy (closed in Phase 1)

**Reference intent:** Summary card at top; description blocks below.

**Implementation:** `JobDetailHeader`; long text in `ProfileSectionCard`; facts as labeled lines; Apply button 48dp min height.

---

## Remaining gaps (not closed — blocked or out of scope)

| ID | Gap | Dependency |
| --- | --- | --- |
| DG-REF-01 | Side-by-side screenshot proof vs downloaded templates | Access to `D:\Ournear Hub\References\` on QA machine |
| DG-SAVED-01 | Saved jobs list UI with real rows | Authorized GET saved-jobs collection in mobile register |
| DG-PROF-02 | Rich timeline for experience/education | Optional; current text blocks match data model |
| DG-FEED-01 | Social feed / messaging | Explicitly out of scope |
| DG-LEGAL-01 | Terms/privacy as real URLs | GAP-CONTENT official destinations |

---

## Manual QA checklist (reproducible)

1. Preview: `flutter run -t lib/preview/hire_candidate_preview_main.dart` or serve `build/web_candidate`.
2. Home: welcome banner, keyword+location helper text, latest jobs cards show company initial and badges when API returns flags.
3. Jobs: search, open detail — header card and section cards readable in light/dark (Profile → Dark Mode).
4. Profile: section cards with dividers; Edit Profile when signed in.
5. Settings → Verification: flags only, no resend.

---

## Authentication visual parity (2026-10-15)

| Item | Implementation | Website screenshot parity |
| --- | --- | --- |
| Unified auth shell | `LoginScreen` with `AuthTabSelector` (Login / Sign up), `OurNearAuthHeader`, max-width form column | **Not verified** — no authorized website screenshot in mobile workspace |
| Login tab | `LoginForm` — username/email, `OurNearPasswordField`, `POST /api/v1/auth/login` via `AuthController` | Client keys preserved (`login-field`, `login-password`, `login-submit`, `login-error`) |
| Signup tab | `SignupForm` — username, display name, mobile, email, passwords, Terms & Privacy checkboxes (dialogs only; no URLs), neutral GAP notice | Full form; submit still calls blocked `LiveAccountAccessRepository.register` |
| Shared components | `HireBrandMark`, `OurNearPasswordField`, `OurNearPrimaryButton`, `HireNoticeBanner` (footer on signup only) | Teal/orange via `OurNearTheme.hireLight()` / `hireDark()` |
| Registration route | `RegistrationScreen` → `LoginScreen(initialTabIndex: 1)` | Same shell as in-tab signup |

**Tests:** `candidate_auth_visual_test.dart`, updated `candidate_account_access_test.dart`, `login_flow_test.dart`.

**Remaining for pixel parity:** Authorized website auth screenshots or spec assets copied into `docs/mobile/` or `assets/`.

## Shared design system (Phase 2 — 2026-10-15)

| Component | Path | Role |
| --- | --- | --- |
| `HireNoticeBanner` | `lib/features/hire/widgets/hire_notice_banner.dart` | Demo/info/neutral inline notices (replaces duplicated banner styling) |
| `HireBrandMark` | `lib/features/hire/widgets/hire_brand_mark.dart` | Auth wordmark |
| `HireWelcomeBanner` | `lib/features/hire/widgets/hire_welcome_banner.dart` | Home hero |
| `HireProfileAvatar` | `lib/features/hire/widgets/hire_profile_avatar.dart` | Profile header avatar |
| `HireSettingsNavTile` | `lib/features/hire/widgets/hire_settings_nav_tile.dart` | Settings navigation rows |
| `OurNearPasswordField` | `lib/core/widgets/ournear_password_field.dart` | Password visibility + theme inputs |

**Evidence:** `flutter test` **158 passed** (2026-10-16 baseline); `flutter analyze` **0 issues**; `hire_design_system_test.dart`.

## Cross-screen visual acceptance (2026-10-16)

| Area | Automated regression | Manual web screenshot | Known defect / gap |
| --- | --- | --- | --- |
| Auth (Login/Signup tabs) | `candidate_auth_visual_test.dart`, `login_flow_test.dart` | Onboarding only (see `qa-evidence/2026-10-16-web-onboarding-load.png`); auth shell not reached via browser taps | Website pixel parity **unverified** (no reference asset in repo) |
| Home + menu + dark mode | `candidate_home_menu_test.dart`, `candidate_dark_theme_test.dart` | Not captured (canvas navigation blocked) | — |
| Job cards / detail hierarchy | `job_card_presentation_test.dart`, discovery tests | Not captured | Saved jobs list still empty-state when GET list missing (`DG-SAVED-01`) |
| Applications | Application flow tests | Not captured | Live list requires API + sign-in |
| Profile / Settings / Documents | Profile UX/flow tests, verification settings test | Not captured | Profile/documents API blocked |
| Saved Jobs | Discovery test (unavailable copy) | Not captured | No real rows until list API |

**Completion execution (2026-10-16):** application history pagination + expanded live repository tests; **`flutter test` 162/162**, analyze clean, web preview rebuilt. Live Laravel still unverified without staging defines.

## Phase 1–2 verdict

**Frontend design consistency: PARTIAL PASS** — Shared tokens/components applied across Home, Jobs, Login, Settings, Saved Jobs, Documents, Applications cards, and profile header; API blockers unchanged.

**Release verdict (overall product): NOT READY** — see `OURNEAR_HIRE_RELEASE_REPORT.md`.
