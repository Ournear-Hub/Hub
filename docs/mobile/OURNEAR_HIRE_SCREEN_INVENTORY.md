# OurNear Hire — Screen Inventory

| Field | Value |
| --- | --- |
| Document ID | UI-CANDIDATE-INVENTORY |
| Authority | `OURNEAR_HIRE_CANDIDATE_APP_MASTER_SPEC.md` |
| Date | 2026-10-14 |
| Rule | Every approved candidate screen is listed. Existing non-candidate surfaces are in the appendix so they are not mistaken for product screens |

Status words:

| Status | Meaning |
| --- | --- |
| Partial | Some source exists and does not meet the master spec |
| Absent | No candidate screen source |

Verification for every row: not exercised on a device or browser in Stage 0. Where tests exist, they were not executed. See the baseline report.

Stage 7 did not change these screen statuses. No new API contract was available, so profile, documents, applications, messages, notifications, and settings stay partial and honestly unavailable.

Stage 8 did not change these screen statuses. It validated the existing shell, packaging, and the known-limitations list in `OURNEAR_HIRE_STAGE_8_RELEASE_CANDIDATE.md`.

Shared requirements, unless a row narrows them: loading, empty, error, offline, and populated states for repository-backed screens; a demo banner when the data source is mock; the bottom bar hidden on focused flows (master spec section 4.3).

## 1. Startup and authentication

### SCR-AUTH-01 Session gate

| Field | Value |
| --- | --- |
| Purpose | Hold the UI until session restoration finishes (F-AUTH-SESSION) |
| Entry | Candidate start: `HireAppScaffold` then `_HireBootstrapGate` |
| Interactions | None. It advances on its own |
| Required states | Spinner while `AuthStatus.unknown`, then the root shell |
| Dependencies | `AuthController.bootstrap`, secure storage, API-ACCOUNT when a token exists |
| Status | Partial (Stage 11). Branded candidate gate with checking and recoverable error states. Retry and browse-without-account actions. Employer variant keeps a simple spinner |
| Code | `lib/app/hire_app_scaffold.dart`, `lib/features/auth/candidate_session_gate.dart` |
| Tests present | `test/features/auth/candidate_session_gate_test.dart` |

### SCR-AUTH-02 Onboarding

| Field | Value |
| --- | --- |
| Purpose | Optional first-run orientation (F-AUTH-ONBOARDING). Skip is required. No language step |
| Entry | After the session gate, for unauthenticated candidate users when `OnboardingPreferences` is not complete. Signed-in users skip it |
| Interactions | Next, back, skip, get started, and a link to sign in |
| Required states | Three pages, skipped or completed locally |
| Dependencies | Local flag `ournear_hire_candidate_onboarding_complete` in secure storage. No backend |
| Status | Implemented (frontend only). Does not block public job discovery after skip or completion |
| Code | `lib/features/auth/onboarding/candidate_onboarding_screen.dart`, gate in `lib/app/hire_app_scaffold.dart` |
| Tests present | `test/features/auth/candidate_account_access_test.dart` |

### SCR-AUTH-03 Login

| Field | Value |
| --- | --- |
| Purpose | Sign in with an existing account (F-AUTH-LOGIN) |
| Entry | Profile, Sign in. Onboarding “Sign in”. The bottom bar is covered because the route is pushed |
| Interactions | Email or username, password, submit. Links to registration and password recovery |
| Required states | Idle, validating, busy, API error, success (the route pops) |
| Dependencies | API-AUTH-LOGIN, `AuthController` |
| Status | Partial. Login works. Registration and recovery routes are linked. The title is “Sign in” |
| Code | `lib/features/auth/login_screen.dart` |
| Tests present | `test/features/auth/login_flow_test.dart`, `test/features/auth/candidate_account_access_test.dart` |

### SCR-AUTH-04 Registration

| Field | Value |
| --- | --- |
| Purpose | Collect registration fields and validate locally (F-AUTH-REGISTER). Server create is not connected |
| Entry | From login (`login-to-register`) |
| Interactions | Name, email, password, confirm password, visibility toggles, submit, back to login, link to recovery |
| Required states | Editing, client validation, unavailable message on submit. No success into profile setup |
| Dependencies | GAP-AUTH-REGISTER, `LiveAccountAccessRepository` (throws before HTTP) |
| Status | Partial (UI only). Submit does not call the network and does not create a session |
| Code | `lib/features/auth/registration_screen.dart` |
| Tests present | `test/features/auth/candidate_account_access_test.dart` |

### SCR-AUTH-05 Password recovery

| Field | Value |
| --- | --- |
| Purpose | Collect email for recovery (F-AUTH-RECOVERY). Server reset is not connected |
| Entry | From login or registration |
| Interactions | Email, submit, back to login |
| Required states | Editing, client validation, unavailable message on submit. No fake inbox success |
| Dependencies | GAP-AUTH-RECOVERY, `LiveAccountAccessRepository` (throws before HTTP) |
| Status | Partial (UI only). Submit does not call the network |
| Code | `lib/features/auth/password_recovery_screen.dart` |
| Tests present | `test/features/auth/candidate_account_access_test.dart` |

### SCR-AUTH-06 Profile setup

| Field | Value |
| --- | --- |
| Purpose | Optional guided walkthrough over the eight existing profile edit sections (F-AUTH-SETUP) |
| Entry | Profile tab: Complete your professional profile or Set up your profile (signed out). Onboarding page 2: Set up your profile (one-shot session open on Profile tab) |
| Interactions | Section list with local status, open section form, Finish later, link to resume/documents |
| Required states | Not started, in progress (opened), reviewed locally (non-empty in repository snapshot). No server complete state |
| Dependencies | Reuses `EditProfileSectionScreen` and `ProfileRepository`. GAP-PROFILE-READ and GAP-PROFILE-WRITE stay open |
| Status | Partial (UI only). Live repository still throws before HTTP. Progress is labelled local setup progress |
| Code | `lib/features/hire/profile/profile_setup/guided_profile_setup_screen.dart` |
| Tests present | `test/features/hire/profile/guided_profile_setup_test.dart` |

## 2. Home and jobs

### SCR-HOME-01 Home

| Field | Value |
| --- | --- |
| Purpose | Search entry, industry and category shortcuts, recommended jobs, latest jobs, saved-job access (F-HOME) |
| Entry | First root tab, id `home` |
| Interactions | Open search, open a category, open a job, open saved jobs. Message and notification actions in the top bar |
| Required states | Loading, empty recommendations, API not configured, error, populated. No unlabelled fake jobs |
| Dependencies | `JobsRepository`, API-JOBS-LIST, GAP-MASTER-DATA, GAP-JOBS-SAVED. Message and notification actions remain later-phase snack bars |
| Status | Partial. Welcome banner, keyword and location (combined keyword), industry filter chips, latest jobs, View All → Jobs tab, notifications in the top bar (no menu icon on Home), loading, empty, error, retry, and not-configured states are in `CandidateHomeScreen`. Saved jobs opens from the Profile-tab end drawer menu |
| Code | `lib/features/hire/home/candidate_home_screen.dart` |
| Tests present | `test/features/hire/jobs/candidate_job_discovery_test.dart` |

### SCR-JOBS-01 Jobs search and results

| Field | Value |
| --- | --- |
| Purpose | Search and list jobs (F-JOBS-SEARCH) |
| Entry | Second root tab, id `jobs`. Also “Browse jobs” on the applications empty state |
| Interactions | Submit a keyword, clear it, refresh, load more, open a card. Filter and sort controls are not offered as working actions |
| Required states | Loading, not configured, unreachable, empty, error with retry, populated, loading more |
| Dependencies | API-JOBS-LIST through `JobsRepository` and `HireApiService` |
| Status | Partial. Keyword search, count, paging, refresh, and retry exist on the candidate Jobs tab. The keyword is one parameter, not separate title, skill, company, and location fields |
| Code | `lib/features/hire/hire_screen.dart`, `lib/features/hire/jobs/data/jobs_repository.dart` |
| Tests present | `test/features/hire/jobs/candidate_job_discovery_test.dart`, `test/features/hire/jobs/jobs_repository_test.dart` |

### SCR-JOBS-02 Filters and sort

| Field | Value |
| --- | --- |
| Purpose | Apply the filters and sort the platform supports (F-JOBS-FILTERS) |
| Entry | From Jobs. The candidate Jobs tab explains that options are unavailable |
| Interactions | None until the repository reports supported filter parameters |
| Required states | No options from the platform |
| Dependencies | GAP-MASTER-DATA. Filter values are not hard-coded. The list request does not send undocumented filter parameters |
| Status | Partial. The unavailable explanation is shown. No working filter or sort control is rendered |

### SCR-JOBS-03 Industry and category catalogue

| Field | Value |
| --- | --- |
| Purpose | Browse platform industries and categories (F-JOBS-CATALOGUE) |
| Entry | Home. Shortcuts appear only when `JobsRepository.fetchIndustryShortcuts` returns rows |
| Interactions | A shortcut opens Jobs and submits its keyword. It is not an industry filter |
| Required states | Unavailable when the live repository has no master-data contract. Populated only from repository data |
| Dependencies | GAP-MASTER-DATA. The live method throws and does not call HTTP |
| Status | Partial. The candidate Home shows the unavailable message. A test repository can supply a shortcut |

### SCR-JOBS-04 Job details

| Field | Value |
| --- | --- |
| Purpose | Read one public job (F-JOBS-DETAIL) |
| Entry | Job card to `HireJobDetailScreen(slug)` |
| Interactions | Retry on failure. Company details show published job fields only. Apply Now opens a later-stage notice when the job is not closed and `accepts_applications` is not false |
| Required states | Loading, not found, offline, error, populated. Bottom bar hidden because the screen is a pushed route |
| Dependencies | API-JOBS-DETAIL through `JobsRepository` |
| Status | Partial. Parsed detail fields are shown when present. Application submission is not implemented |
| Code | `lib/features/hire/hire_job_detail_screen.dart` |
| Tests present | `test/features/hire/jobs/candidate_job_discovery_test.dart` |

### SCR-JOBS-05 Company details

| Field | Value |
| --- | --- |
| Purpose | Read the hiring company (F-JOBS-COMPANY) |
| Entry | From job details, using the company name already parsed on the job |
| Interactions | Back. No extra company request |
| Required states | Unavailable when the job has no company name. Otherwise the published name, with an explicit statement that a company profile is not loaded |
| Dependencies | GAP-COMPANY |
| Status | Partial. `CompanyDetailsScreen` does not invent a company record |
| Code | `lib/features/hire/jobs/company_details_screen.dart` |

### SCR-JOBS-06 Saved jobs

| Field | Value |
| --- | --- |
| Purpose | List saved jobs and show availability (F-JOBS-SAVED) |
| Entry | End drawer menu: Saved Jobs |
| Interactions | Open the screen, read the unavailable explanation, back |
| Required states | Unavailable. The live repository does not call a saved-jobs endpoint |
| Dependencies | GAP-JOBS-SAVED |
| Status | Partial. `SavedJobsScreen` says jobs are not saved on the device and are not synced. It does not list fabricated bookmarks |
| Code | `lib/features/hire/jobs/saved_jobs_screen.dart` |
| Tests present | `test/features/hire/jobs/candidate_job_discovery_test.dart` |

## 3. Applications

### SCR-APP-01 Applications list

| Field | Value |
| --- | --- |
| Purpose | Application history and the status filters the platform supports (F-APP-HISTORY) |
| Entry | Third root tab, id `applications` |
| Interactions | Refresh, open a row, browse jobs when empty. Message and notification placeholders. Demo status chips filter the complete local demo list only |
| Required states | Loading, empty, offline, error, session expired, populated, unavailable when the live repository is used, demo banner when the source is mock |
| Dependencies | `ApplicationsRepository`. The candidate shell provides `LiveApplicationsRepository`. GAP-APP-LIST |
| Status | Partial. The live tab says history is not connected and does not show demo rows. Mock injection still shows the labelled demo list |
| Code | `lib/features/hire/applications/candidate_applications_screen.dart` |
| Tests present | `test/features/hire/applications/candidate_applications_screen_test.dart`, `test/features/hire/applications/application_flow_test.dart` |

### SCR-APP-02 Application details

| Field | Value |
| --- | --- |
| Purpose | One application, the hiring timeline, interview details, and candidate actions the backend allows (F-APP-HISTORY, F-APP-TIMELINE) |
| Entry | An application row |
| Interactions | Back. View job when the record has a slug. No withdrawal and no status edit |
| Required states | Populated from the supplied record, loading, not found, unavailable. Bottom bar hidden because the screen is a pushed route |
| Dependencies | GAP-APP-DETAIL. Timeline events are rendered only when the record includes them |
| Status | Partial. Demo detail shows submitted and updated timestamps. Interviews are not invented |
| Code | `lib/features/hire/applications/candidate_application_detail_screen.dart` |
| Tests present | `test/features/hire/applications/application_flow_test.dart` |

### SCR-APP-03 Application readiness

| Field | Value |
| --- | --- |
| Purpose | Check required profile fields before apply (F-APP-READINESS) |
| Entry | Apply Now on job details, when the job is not closed and `accepts_applications` is not false |
| Interactions | Read the job summary and the profile and resume gaps. Continue opens the wizard. A blocked job has no Continue |
| Required states | Blocked when the job is closed or not accepting applications. Otherwise a continue state that does not claim a submission |
| Dependencies | Published job fields. GAP-PROFILE-READ, GAP-RESUME-UPLOAD, GAP-APP-SUBMIT |
| Status | Partial. `ApplicationReadinessScreen` uses the job payload. It does not invent required profile fields |
| Code | `lib/features/hire/applications/application_readiness_screen.dart` |
| Tests present | `test/features/hire/applications/application_flow_test.dart` |

### SCR-APP-04 Application wizard

| Field | Value |
| --- | --- |
| Purpose | Multi-step apply (F-APP-WIZARD) |
| Entry | Continue on readiness |
| Interactions | Next, back, and system back move one step. Bottom bar stays covered. The step list is vertical, not a horizontal scroller |
| Required states | Account validation, resume unavailable, review |
| Dependencies | Signed-in `AuthUser` when present. GAP-APP-SUBMIT |
| Status | Partial. Three steps: account, resume gap, and review. No stand-in profile fields and no draft endpoint |
| Code | `lib/features/hire/applications/application_wizard_screen.dart` |
| Tests present | `test/features/hire/applications/application_flow_test.dart` |

### SCR-APP-05 Application review

| Field | Value |
| --- | --- |
| Purpose | Review answers and the chosen resume before submit (F-APP-WIZARD) |
| Entry | Last wizard step |
| Interactions | Back returns to the resume step. Submit calls `ApplicationsRepository.submitApplication` |
| Required states | Review, please-wait while the call is in progress, error with retry, labelled demo confirmation |
| Dependencies | GAP-APP-SUBMIT |
| Status | Partial. The review shows the job, account, missing resume, and the absence of job questions |
| Code | `lib/features/hire/applications/application_wizard_screen.dart` |

### SCR-APP-06 Resume selection

| Field | Value |
| --- | --- |
| Purpose | Choose the generated Hire Resume or My Resume when the backend offers that choice (F-APP-WIZARD, F-DOCS) |
| Entry | Wizard step between account and review |
| Interactions | Read the unavailable explanation and continue. There is no file to select |
| Required states | Unavailable |
| Dependencies | GAP-RESUME-GENERATED, GAP-RESUME-UPLOAD |
| Status | Partial. The step says no resume source is connected and nothing will be attached |

### SCR-APP-07 Job-specific questions

| Field | Value |
| --- | --- |
| Purpose | Answer questions only when the job payload includes them (F-APP-WIZARD) |
| Entry | Omitted. The job model has no question list |
| Interactions | None. Review states that no questions were provided and that no questions endpoint is called |
| Required states | Hidden |
| Dependencies | GAP-APP-SUBMIT |
| Status | Partial. The step is omitted rather than filled with invented questions |

### SCR-APP-08 Submission confirmation

| Field | Value |
| --- | --- |
| Purpose | Confirm a successful submit and block a second submit (F-APP-WIZARD) |
| Entry | After `submitApplication` returns |
| Interactions | A live failure stays on review and can be tried again. A mock success disables the button |
| Required states | Unavailable for the live repository. Labelled demo confirmation for the mock repository. Server confirmation only when `confirmedByServer` is true |
| Dependencies | GAP-APP-SUBMIT |
| Status | Partial. The live path says nothing was sent. The mock path says “Demo record only” under the demo banner |

## 4. Profile

The current Profile tab is the shared account screen in the appendix. It is not this set.

### SCR-PROF-01 Profile overview

| Field | Value |
| --- | --- |
| Purpose | Overview with photo, headline, completion, and entry to documents and settings (F-PROFILE) |
| Entry | Fourth root tab, id `profile`, when the candidate profile replaces the shared account card for this entry |
| Interactions | Edit, open the professional view, open documents, open settings, sign in, sign out |
| Required states | Signed out, loading, incomplete, populated, error |
| Dependencies | API-AUTH-LOGIN, API-ACCOUNT, GAP-PROFILE-READ |
| Status | Partial. Profile tab is `CandidateDirectProfileScreen` (professional sections + Edit Profile). The end drawer menu icon appears only on this tab (`CandidateShellScaffold` / `CandidateShellScope.menuEnabled`). Menu order: View Profile; Saved Jobs; Applied Jobs; Resumes; Settings; Dark Mode; OURNEAR APPS; information links; Delete Account; Logout. `CandidateProfileScreen` remains for legacy widget tests |
| Code | `lib/features/hire/profile/candidate_profile_screen.dart` |
| Tests present | `test/features/hire/profile/candidate_profile_flow_test.dart`, `test/features/profile/candidate_profile_screen_test.dart` |

### SCR-PROF-02 Professional profile

| Field | Value |
| --- | --- |
| Purpose | Read-only professional profile (F-PROFILE) |
| Entry | View professional profile |
| Interactions | Open a separate edit form. Sections are read-only on this screen |
| Required states | Unavailable when the live repository throws. Empty sections when a demo profile has no rows. Populated only from supplied fields |
| Dependencies | GAP-PROFILE-READ |
| Status | Partial. Private contact and resume files are not shown. Recruiter visibility is not claimed |
| Code | `lib/features/hire/profile/professional_profile_screen.dart` |

### SCR-PROF-03 Edit personal information

| Field | Value |
| --- | --- |
| Purpose | Separate form for personal information (F-PROFILE) |
| Entry | From the overview or the professional profile |
| Interactions | Save, cancel. Private contact stays off the default read-only view |
| Required states | Editing, validation, saving, server error |
| Dependencies | GAP-PROFILE-WRITE |
| Status | Partial. Public location can be edited. Account name is shown and is not written by this form. A live save says nothing was saved |
| Code | `lib/features/hire/profile/edit_profile_section_screen.dart` |

### SCR-PROF-04 Edit summary

| Field | Value |
| --- | --- |
| Purpose | Separate summary form |
| Entry | From profile |
| Interactions | Save, cancel |
| Required states | Editing, validation, saving, error |
| Dependencies | GAP-PROFILE-WRITE |
| Status | Partial. Save reports success only when the repository sets `persisted`. The live repository does not |
| Code | `lib/features/hire/profile/edit_profile_section_screen.dart` |

### SCR-PROF-05 Edit experience

| Field | Value |
| --- | --- |
| Purpose | Add, edit, and remove experience entries |
| Entry | From profile |
| Interactions | List, edit one, save |
| Required states | Empty, populated, editing, error |
| Dependencies | GAP-PROFILE-WRITE |
| Status | Partial. One role and organization form. A demo save stays in memory and is labelled |
| Code | `lib/features/hire/profile/edit_profile_section_screen.dart` |

### SCR-PROF-06 Edit education

| Field | Value |
| --- | --- |
| Purpose | Add, edit, and remove education entries |
| Entry | From profile |
| Interactions | List, edit one, save |
| Required states | Empty, populated, editing, error |
| Dependencies | GAP-PROFILE-WRITE |
| Status | Partial. Separate credential and institution form |
| Code | `lib/features/hire/profile/edit_profile_section_screen.dart` |

### SCR-PROF-07 Edit skills

| Field | Value |
| --- | --- |
| Purpose | Edit skills from platform data when the API provides a catalogue |
| Entry | From profile |
| Interactions | Add, remove, save |
| Required states | Empty, populated, saving, error |
| Dependencies | GAP-PROFILE-WRITE. No hard-coded skill catalogue |
| Status | Partial. The form accepts text the candidate enters. It does not offer a skill catalogue |
| Code | `lib/features/hire/profile/edit_profile_section_screen.dart` |

### SCR-PROF-08 Edit qualifications

| Field | Value |
| --- | --- |
| Purpose | Edit qualifications |
| Entry | From profile |
| Interactions | Add, edit, remove, save |
| Required states | Empty, populated, editing, error |
| Dependencies | GAP-PROFILE-WRITE |
| Status | Partial. Separate form. Live save is unavailable |
| Code | `lib/features/hire/profile/edit_profile_section_screen.dart` |

### SCR-PROF-09 Edit certifications

| Field | Value |
| --- | --- |
| Purpose | Edit certifications |
| Entry | From profile |
| Interactions | Add, edit, remove, save |
| Required states | Empty, populated, editing, error |
| Dependencies | GAP-PROFILE-WRITE |
| Status | Partial. Separate form. Live save is unavailable |
| Code | `lib/features/hire/profile/edit_profile_section_screen.dart` |

### SCR-PROF-10 Profile completion

| Field | Value |
| --- | --- |
| Purpose | Show completion and the missing required items (F-PROFILE, F-AUTH-SETUP) |
| Entry | Overview and application readiness |
| Interactions | Open the form for a missing item |
| Required states | Incomplete with a list, complete |
| Dependencies | GAP-PROFILE-READ |
| Status | Partial. The overview says completion cannot be calculated. No percentage is shown |
| Code | `lib/features/hire/profile/candidate_profile_screen.dart` |

## 5. Resumes and documents

### SCR-DOC-01 Hire Resume

| Field | Value |
| --- | --- |
| Purpose | Show the generated Hire Resume (F-DOCS) |
| Entry | Resume and documents, Hire Resume section |
| Interactions | Preview supplied text. The application wizard does not attach it |
| Required states | Unavailable on the live repository. Demo preview only when the mock supplies text |
| Dependencies | GAP-RESUME-GENERATED |
| Status | Partial. Generation is described as not connected |
| Code | `lib/features/hire/profile/documents_screen.dart` |

### SCR-DOC-02 My Resume

| Field | Value |
| --- | --- |
| Purpose | One replaceable uploaded file (F-DOCS) |
| Entry | Resume and documents, My Resume section |
| Interactions | Enter a file name and replace. The live repository refuses the call |
| Required states | Unavailable, demo file name, validation for an empty name, replace of the single demo name |
| Dependencies | GAP-RESUME-UPLOAD |
| Status | Partial. No file bytes are stored and no upload is reported as successful |
| Code | `lib/features/hire/profile/documents_screen.dart` |

### SCR-DOC-03 Document preview

| Field | Value |
| --- | --- |
| Purpose | Preview a resume the signed-in candidate is allowed to see (F-DOCS) |
| Entry | Hire Resume, My Resume, or an application snapshot |
| Interactions | Close. Bottom bar hidden. Panning a PDF or image must not change tabs |
| Required states | Loading, unauthorized, ready, error |
| Dependencies | GAP-RESUME-UPLOAD, GAP-RESUME-SNAPSHOT. Authorized access only |
| Status | Partial. Preview shows supplied text or says preview is not available. It does not open a file URL |
| Code | `lib/features/hire/profile/document_preview_screen.dart` |

## 6. Communication

### SCR-MSG-01 Messages inbox

| Field | Value |
| --- | --- |
| Purpose | Inbox and unread state (F-MSG) |
| Entry | Top app bar message action on Home and Applications. Not a root tab |
| Interactions | Open a thread, try again |
| Required states | Signed out, loading, empty only after a successful live list, unavailable, demo |
| Dependencies | GAP-MSG |
| Status | Partial. `LiveMessagesRepository.fetchInbox` throws unavailable and does not call HTTP. That state is not an empty inbox. Demo rows appear only when a mock repository is injected |
| Code | `lib/features/hire/communication/messages_inbox_screen.dart` |
| Tests present | `test/features/hire/communication/candidate_communication_test.dart` |

### SCR-MSG-02 Message thread

| Field | Value |
| --- | --- |
| Purpose | Read a conversation. Send only when a contract exists (F-MSG) |
| Entry | Inbox |
| Interactions | Back. Send stays disabled. A job or application reference is shown only when the record includes it. Bottom bar is covered. A horizontal drag does not change tabs |
| Required states | Signed out, loading, unavailable, demo thread |
| Dependencies | GAP-MSG |
| Status | Partial. No message is sent. The thread does not claim an employer or recruiter wrote it |
| Code | `lib/features/hire/communication/message_thread_screen.dart` |

### SCR-NOTIF-01 Notifications

| Field | Value |
| --- | --- |
| Purpose | Notification list and read state (F-NOTIF) |
| Entry | Top app bar bell on Home and Applications, and the Profile notifications row. Not a root tab |
| Interactions | Try again. Mark read and dismiss call the repository and show its failure |
| Required states | Signed out, loading, empty only after a successful live list, unavailable, demo |
| Dependencies | GAP-NOTIF |
| Status | Partial. `LiveNotificationsRepository` throws unavailable and does not call HTTP. Mark read does not clear unread. No push client |
| Code | `lib/features/hire/communication/notifications_screen.dart` |

## 7. Preferences and settings

These open from the candidate Profile settings action. None is a root tab. Logout remains an action. Sign out stays on the candidate Profile tab and on the privacy screen.

### SCR-SET-01 Job preferences and alerts

| Field | Value |
| --- | --- |
| Purpose | Job preferences and alerts the platform supports (F-PREFS) |
| Entry | Settings |
| Interactions | Open the existing job-preferences form. Job alerts open the unavailable preferences screen |
| Required states | Explanation, unavailable save, unavailable alerts |
| Dependencies | GAP-PREFS, GAP-PROFILE-WRITE, GAP-MASTER-DATA |
| Status | Partial. The form save says nothing was saved unless the profile repository confirms persistence |
| Code | `lib/features/hire/settings/candidate_settings_screen.dart`, `lib/features/hire/profile/preferences_unavailable_screen.dart` |

### SCR-SET-02 Notification preferences

| Field | Value |
| --- | --- |
| Purpose | Choose which notifications the account receives (F-PREFS) |
| Entry | Settings |
| Interactions | Read the unavailable explanation |
| Required states | Unavailable |
| Dependencies | GAP-PREFS |
| Status | Partial. No channel toggles and no save |
| Code | `lib/features/hire/settings/candidate_settings_screen.dart` |

### SCR-SET-03 Privacy and account security

| Field | Value |
| --- | --- |
| Purpose | Privacy and security actions the backend supports, including session clearing (F-PREFS) |
| Entry | Settings |
| Interactions | Sign out through `AuthController.logout` |
| Required states | Signed in, signed out |
| Dependencies | API-AUTH-LOGOUT exists. Password change and account deletion are GAP-PREFS |
| Status | Partial. Sign out clears the session. Password change and account deletion have no action |
| Code | `lib/features/hire/settings/candidate_settings_screen.dart` |

### SCR-SET-04 Help and support

| Field | Value |
| --- | --- |
| Purpose | Help content the product already has (F-PREFS) |
| Entry | Settings |
| Interactions | Read, close |
| Required states | Unavailable |
| Dependencies | GAP-CONTENT |
| Status | Partial. The screen says help is not connected and names no support service |
| Code | `lib/features/hire/settings/candidate_settings_screen.dart` |

### SCR-SET-05 Terms

| Field | Value |
| --- | --- |
| Purpose | Terms of use (F-PREFS) |
| Entry | Settings |
| Interactions | Read, close |
| Required states | Unavailable |
| Dependencies | GAP-CONTENT |
| Status | Partial. The screen says terms are not connected |
| Code | `lib/features/hire/settings/candidate_settings_screen.dart` |

### SCR-SET-06 Privacy policy

| Field | Value |
| --- | --- |
| Purpose | Privacy policy (F-PREFS) |
| Entry | Settings |
| Interactions | Read, close |
| Required states | Unavailable |
| Dependencies | GAP-CONTENT |
| Status | Partial. The screen says the privacy policy is not connected |
| Code | `lib/features/hire/settings/candidate_settings_screen.dart` |

## 8. Appendix — surfaces that are not approved candidate screens

Do not delete these while another shell still uses them. Do not add them to the candidate bottom bar.

| Surface | Code | Why it is not a candidate screen |
| --- | --- | --- |
| Unified Home | `lib/features/home/home_screen.dart` | Module launcher for Home, Vibes, Drops, Memory, Hire, and Notifications |
| Vibes | `lib/features/vibes/vibes_screen.dart` | Placeholder in the unified shell |
| Drops | `lib/features/drops/drops_screen.dart` | Placeholder in the unified shell |
| Unified Hire tab | `HireScreen` inside `AppShell` | Same jobs widget, title “Hire”, inside a five-tab shell |
| Shared Profile | `lib/features/profile/profile_screen.dart` | Account card. Memory row when `showMemoryShortcut` is true. The candidate tab uses `CandidateProfileScreen` and does not show Memory. Unified and employer shells keep the row |
| Employer Home, My Jobs, Applicants | `HireAppConfigs.employer` placeholders | Employer scope. Preserved |
| Employer preview entry | `lib/preview/hire_employer_preview_main.dart` | Out of scope |

## 9. Count

| Group | Approved screens | Partial source | Absent |
| --- | --- | --- | --- |
| Authentication | 6 | 2 (session gate, login) | 4 |
| Home and jobs | 7 | 7 (home, search, filters note, catalogue note, detail, company, saved) | 0 |
| Applications | 8 | 8 (list, detail, readiness, wizard, review, resume, questions note, confirmation) | 0 |
| Profile | 10 | 10 | 0 |
| Documents | 3 | 3 | 0 |
| Communication | 3 | 3 | 0 |
| Settings | 6 | 6 | 0 |
| Total | 43 | 39 | 4 |
