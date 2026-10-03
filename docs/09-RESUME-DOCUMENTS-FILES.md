# Resume, documents, files

## Disks (`config/filesystems.php`)

| Disk | Root | Visibility |
| --- | --- | --- |
| `resumes` | `storage/app/resumes` | private |
| `candidate_files` | `storage/app/candidate-files` | private |
| `employer_files` | `storage/app/employer-files` | private |
| `freelancer_files` | `storage/app/freelancer-files` | private |
| `public` | `storage/app/public` | public URL `/storage` |
| `local` | `storage/app/private` | private |

## Candidate resume library — IMPLEMENTED

`CandidateResume`, `CandidateResumeController`, `CandidateResumeLibraryService`. Upload, primary, archive, replace, download/view (auth). Throttle `account-files`.

## Application snapshot — IMPLEMENTED

`applications.resume_path` copied at apply time. Changing the library does **not** rewrite old applications (tested). Admin/employer download via application resume routes.

## Candidate documents — IMPLEMENTED

`CandidateDocument` + document controller/library service. Typed documents (payslips, certificates, etc.).

## Employer documents — IMPLEMENTED

`EmployerDocument`, verification review on admin (`reviewDocument`). Account employer library.

## Freelancer intake files — IMPLEMENTED

Public freelancer form stores files on `freelancer_files`; admin download.

## Access

Policies: `CandidateResumePolicy`, `CandidateDocumentPolicy`, `EmployerDocumentPolicy`. Never link private disks as public assets.

## KYC

No separate KYC product module. Employer document **verification status** is operational review, not a full KYC vendor.

## Tests

`AccountCandidateResumeDocumentTest`, employer document tests in Stage 4/company suites, freelancer resume routes in admin tests.

Admin bulk resume import (`admin.candidates.imports.*`, `candidates.manage` to upload) stores files through `CandidateResumeLibraryService`. It matches existing emails without overwriting profile fields, marks missing emails for review, and queues `ImportedCandidateWelcomeMail` with a password-setup link. There is no separate parser product; PDF text and DOCX XML are scanned for an email only. See [25-HIRE-IDENTITY-AND-RESUME-IMPORT.md](25-HIRE-IDENTITY-AND-RESUME-IMPORT.md).
