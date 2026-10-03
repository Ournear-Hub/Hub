# Hire identity and bulk resume import

**Status:** IMPLEMENTED. Display layer plus Admin import. Does not replace `users.business_id`.

## Hire display identity

One immutable integer: `users.person_number` (unique, assigned on user create when the column exists).

Display format (`App\Support\OurNearHireIdentity`):

```text
ON-HIR-{CAN|REC|USER}-{padded number}
```

| Context code | When |
| --- | --- |
| `CAN` | Candidate (`UserRole::Candidate`) |
| `REC` | Employer, or Admin who can view Hiring Command Center |
| `USER` | Other (including admin who cannot open Command Center) |

The **same** `person_number` is used for all three display codes. Renaming the user does not change the number. Code must not treat display strings as a second identity table.

Shared Inertia: `auth.user.ournear_id`. Resume builder also emits candidate display id.

Migration: `database/migrations/2026_09_29_150000_add_hire_identity_and_resume_imports.php` (also adds optional `website_settings.brand_mark` / `wordmark_path` if missing).

## Account verification entry

No new identity verification product. Staff/candidates use existing email, mobile OTP, and WhatsApp OTP under **`/account/settings/security`**.

## Bulk resume import — IMPLEMENTED (limited)

| Piece | Location |
| --- | --- |
| Routes | `GET/POST admin/candidates/imports` (`admin.candidates.imports.index` / `.store`) |
| Controller | `app/Http/Controllers/Admin/ResumeImportController.php` |
| Service | `app/Services/ResumeImportService.php` |
| Request | `StoreResumeImportRequest` — `candidates.manage` to POST; index requires `candidates.view` |
| UI | `resources/js/Pages/Admin/Candidates/ResumeImport.tsx` |
| Tables | `resume_import_batches`, `resume_import_items` |
| Models | `ResumeImportBatch`, `ResumeImportItem` |

Behavior (from service/tests):

- Upload many files (max 250, 8 MB each in the Form Request).
- Stores through existing `CandidateResumeLibraryService` / resume disk — **not** a public disk.
- Matches existing users by extracted email; **does not overwrite** existing profile fields on match.
- Missing/unparseable email → needs review.
- Duplicate content hash is skipped/failed according to the service.
- Queues `ImportedCandidateWelcomeMail` with a password-setup link (`emails_queued` on the batch).
- Parser is **not** a full CV intelligence product: PDF text / DOCX XML scanned for an email only.

Permissions: no new `PermissionName` case. Reuses `candidates.view` / `candidates.manage`.

## Tests

`tests/Feature/HireIdentityResumeImportTest.php`

## Do not

- Invent a second User id system or change `business_id` prefixes to `ON-HIR`.
- Treat import as unlimited public resume search.
- Reuse WhatsApp OTP for import notifications.
