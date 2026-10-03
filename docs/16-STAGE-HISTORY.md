# Stage history

**Sources:** migrations filenames, test class names (`*Stage*Test`), `V1.1_DEVELOPMENT_STATE.md` (stale vs code), conversation-era Stage 23–24 work.

[CODE IS SOURCE OF TRUTH — EXISTING DOCUMENTATION CONFLICT] `V1.1_DEVELOPMENT_STATE.md` “latest A.9 / next V1.2 portal” does not match the tree.

Git dates/commits for every stage: [HISTORICAL CONTEXT NOT VERIFIED IN REPOSITORY] unless you run `git log` per file. This table is **code-evidence** based.

| Stage (code evidence) | Purpose | Evidence |
| --- | --- | --- |
| V1 / A.0–A.9 | Admin ATS core, master data, notifications | Early migrations Aug–Sep 2026, Hostinger dump |
| Stage 1–2 | Users, roles | `UserFoundationStage1Test`, `RolesPermissionsStage2Test` |
| Stage 3 | Talent pool | `CandidateTalentPoolStage3Test` |
| Stage 4 | Employer company | `EmployerCompanyFoundationStage4Test` |
| Stage 5 | Jobs ATS | `JobsAtsFoundationStage5Test` |
| Stage 6 | Interviews/tasks | `RecruitmentOperationsStage6Test` |
| Stage 7 | CRM | `CrmFoundationStage7Test` |
| Stage 8 | Channel partners | `ChannelPartnerStage8Test` |
| Stage 9 | Investors | `InvestorFoundationStage9Test` |
| Stage 10 | Campaigns/referrals | `CampaignReferralStage10Test` |
| Stage 11 | Production readiness | `ProductionReadinessStage11Test` |
| Stages 12–20 | UX / analytics / marketing | `ProductionUxStage*Test` |
| Stage 23B | Company email/WhatsApp settings | `CompanyCommunicationSettingsTest`, migration `120000` |
| Stage 23C | Mobile OTP | `MobileOtpVerificationTest` |
| Stage 23D | WhatsApp Cloud | `WhatsAppCloudFoundationTest` |
| Stage 23E | WhatsApp OTP | `WhatsAppOtpVerificationTest` |
| Stage 23F | Unified profile | `UnifiedProfileStage23FTest` |
| Stage 24 | Recruitment automation | `RecruitmentAutomationStage24Test`, migration `240000` |
| Stage 25 | Admin/RBAC finalization | `AdminRbacStage25Test`; bootstrapper floor + privilege-escalation guards |
| Stage 26 | Recruiter Hiring Command Center | `RecruiterHiringCommandCenterStage26Test`, `admin.hiring.index` |
| Hire identity + import | `ON-HIR` display, `person_number`, bulk resume import | `HireIdentityResumeImportTest`, migration `2026_09_29_150000` |
| Public platform UX (partial) | Companies / Hire / Talent Pool / Staffing pages; Admin sidebar groups | `PublicPlatformController`, `navigation.ts`, `Sidebar.tsx`. Full job-to-joining run: [NOT VERIFIED] |

Phase F2–F6 tests cover screening/operations/finance/readiness UX on the ATS.

**Status:** Stages through 26 exist in the working tree; they may be **uncommitted** relative to `origin/main`.
