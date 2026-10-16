# OurNear Hire — Stage 10 guided profile setup

| Field | Value |
| --- | --- |
| Document ID | STAGE-10-CANDIDATE-GUIDED-PROFILE |
| Date | 2026-10-15 |
| Verdict | PASS (UI only) |

## Screens

| Item | Location |
| --- | --- |
| Guided setup overview | `lib/features/hire/profile/profile_setup/guided_profile_setup_screen.dart` |
| Local progress rules | `lib/features/hire/profile/profile_setup/profile_setup_progress.dart` |
| Onboarding one-shot entry | `lib/features/hire/profile/profile_setup/profile_setup_entry_request.dart` |

Section edits reuse `EditProfileSectionScreen` with `initialSection`. No duplicate forms.

## Entry points

- Signed in: Profile tab → **Complete your professional profile**
- Signed out: Profile tab → **Set up your profile** (sign-in guidance inside setup)
- Onboarding page 2 → **Set up your profile** → opens setup when the candidate next opens the Profile tab (session flag, not a forced redirect)

Setup is optional. No post-login redirect. Public jobs unchanged.

## Progress rules

| Status | Meaning |
| --- | --- |
| Not started | Section fields empty in the current repository snapshot |
| In progress | Section opened this session and still empty |
| Reviewed locally | Section has non-empty supported fields in the snapshot (mock in-memory or future API data) |

Overview text: **Local setup progress: X of 8 sections reviewed locally.** No server percentage. No “complete on OurNear.”

Headline: the summary section form edits summary text only; a separate headline field is not in the current edit forms.

## Persistence

- `LiveProfileRepository`: unchanged; throws before HTTP on read and write.
- `MockProfileRepository`: in-memory demo only; UI shows demo banner and never “Saved on OurNear.”
- No secure-storage profile cache was added.

## Documents

Setup links to the existing `DocumentsScreen`. Hire Resume and My Resume remain distinct; live generation and upload stay unavailable.

## Verified HTTP

Unchanged five-call set. Stage 10 added no endpoints.

## Tests and builds

See baseline report section 24 for command exit codes.

## Isolation

- `lib/main.dart` unchanged.
- Website/backend workspace not touched.
