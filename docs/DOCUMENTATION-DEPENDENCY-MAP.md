# Documentation dependency map

```text
Authentication (02)
   ↓
Authorization (03)
   ↓
Candidate (05) ──→ Applications (08) ──→ Notifications (10)
       ↓                    ↓
     Files (09)      Recruitment ops (19)
                           ↓
                    Automation (20)

Jobs (07) ──→ Applications (08) ──→ Submissions/Placements (19)
Employer account (06) ──→ Jobs (07) ──→ Files (09)

Public/CMS (22) ──→ Enquiries ──→ CRM (21)

Campaigns (21) ──→ Users/Applications attribution

Integrations (14) ──→ WhatsApp OTP (10)  [not recruitment chat]

Database (04) underpins all modules
Frontend (12) consumes all HTTP/Inertia surfaces
Security (23) cuts across files, auth, webhooks
```

Staff Recruiter is authorization (03) + ops (19), not a separate UserRole. Stage 25 hardened bootstrapper/role grants; dual-guard auth (02) is unchanged.

Hiring Command Center (24) depends on applications/submissions/placements/employees (08, 19) and RBAC (03). Hire identity (25) depends on `users` and resume disks (09). Public platform pages (22) depend on employer_profiles + candidate share flags (05, 06). Stage 26 Command Center sits on 19 + 08 + 06 and must not add a second match engine.

Channel partners (21) depend on employers/payments; they do **not** replace `EmployerSubmission`.
