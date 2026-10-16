# OurNear Export & Download Architecture Review (PR-5AC)

## Objective and scope

PR-5AC documents how **data export and file download** work in OurNear Hire today, and decides whether the frozen Design System needs a reusable export/download component or shared client contract.

**In scope:** Read-only inspection of routes, controllers, file services, policies, and React/Inertia UI that trigger downloads or bulk file retrieval (excluding the completed master-data Excel scope below).

**Out of scope:** Re-opening PR-5AA/PR-5AB or other frozen DS decisions; V2 adoption or migration; page refactors; new npm/Composer export libraries; PR-6A.

**Scope boundary — master-data Excel import/export:** A dedicated **master-data Excel import/export** architecture review and implementation were completed in a prior gate. **This document does not re-review that work.** In the current application tree, Admin **Master Data** (`MasterDataController`, `Pages/Admin/MasterData/Index.tsx`) is exposed as **Inertia CRUD** (index/store/update/status) with **no additional export/download routes** in `routes/admin.php`. Any Excel tooling from that prior gate remains **domain-owned** and outside PR-5AC evidence.

**Decision summary:** **No new DS export/download component.** Downloads are **authorized server GET responses**; the UI correctly uses **Link / Button / anchor** with server-provided URLs. There is **no repeated client-side export pipeline** (CSV generation, blob assembly, progress polling) that would justify a Design System abstraction.

---

## Evidence inspected

| Area | Paths / method |
|------|----------------|
| Dependencies | `package.json`, `composer.json`, `package-lock.json` (chart/export library names) |
| Routes | `routes/account.php`, `routes/admin.php` (download/resume routes) |
| File delivery | `CandidatePrivateFileService`, `EmployerPrivateFileService`, `FreelancerFileService`, `InternalCandidateResumeService` |
| Controllers | `CandidateResumeController`, `CandidateDocumentController`, `EmployerDocumentController`, `ApplicationsController`, `CandidatesController`, `EmployersController`, `FreelancersController`, `CandidateApplicationsController`, `EmployerJobsController` |
| Authorization | `CandidateResumePolicy`, `CandidateDocumentPolicy`, `EmployerDocumentPolicy`, `ApplicationPolicy`, `FreelancerPolicy`, etc. |
| Frontend | Pages/components with `download_url`, `download_href`, or “Download” labels (Account resumes/documents, Admin candidates/employers/freelancers/users, `ApplicationDrawer`, submissions/employees indexes) |
| Related non-export | `ResumeImportController` / `ResumeImportService` (**import** POST + redirect flash); `Account/Resumes/Builder.tsx` (`window.print()` for print/PDF) |
| DS / UI Lab | `Button`, `Link`, `LinkButton`, `Toast`, `Alert`, `Spinner`; icon registry `download`; EmptyState lab demo label “Import CSV” (illustrative only) |
| Product docs | `docs/09-RESUME-DOCUMENTS-FILES.md`, `docs/25-HIRE-IDENTITY-AND-RESUME-IMPORT.md` |

### Search methodology

Ripgrep on `app/`, `routes/`, and `resources/js/` for: `download`, `StreamedResponse`, `Content-Disposition`, `Storage::download`, `csv`, `xlsx`, `excel`, `export`, `createObjectURL`, `blob`, and common PHP export packages. Frontend `export` keyword matches were filtered to **download/export product behavior**, not TypeScript `export` statements.

---

## Confirmed dependencies

| Layer | Export/download libraries |
|-------|---------------------------|
| **npm** (`package.json`) | **None** — no `file-saver`, `xlsx`, `papaparse`, `jsPDF`, or similar |
| **Composer** (`composer.json`) | **None** — no `maatwebsite/excel`, `phpoffice/phpspreadsheet`, DomPDF, Snappy, etc. |
| **Runtime** | Laravel `Storage` streaming/download, Symfony `StreamedResponse`, session/auth middleware on routes |

Generated reports (CSV/Excel/PDF) are **not** produced by a shared client or server export package in this repository snapshot.

---

## Existing export/download implementations

### 1. Server-generated file downloads (primary pattern)

**Mechanism:** Authenticated **GET** routes → policy check → `Storage::disk(...)->download()` or `->response()` with `Content-Disposition` (attachment or inline PDF where supported).

**Examples:**

| Workflow | Route surface (representative) | UI trigger |
|----------|--------------------------------|------------|
| Candidate resume library | `account.resumes.download`, `admin.candidates.resumes.show` | `Button href={download_url}`, text links |
| Candidate documents | `account.documents.download`, admin candidate document routes | Anchor with “Download” |
| Employer company documents | `account.employer.documents.download`, admin employer document routes | Anchor |
| Application resume snapshot | `account.applications.resume`, `admin.applications.resume` | “Download snapshot” / drawer links |
| Employer view of applicant resume | `account.employer.applications.resume` | Download link on show pages |
| Freelancer intake files | `admin.freelancers.resume`, certificate routes | Anchor download on Admin show |
| User 360 documents | `download_href` on admin user show | `<a href={...}>Download</a>` |

**Data ownership:** Paths, disks (`resumes`, `candidate_files`, `employer_files`, `freelancer_files`), filenames, and MIME types are determined **server-side** from stored records. Models expose `download_url` / route helpers in arrays sent to Inertia.

**Filters / parameters:** Downloads are **resource-scoped** (IDs in path), not “export current table filter” queries. List exports with query-string filters were **not found**.

### 2. Browser-generated output (secondary, not server export)

| Feature | Implementation |
|---------|----------------|
| OurNear Hire Resume “PDF” | `Account/Resumes/Builder.tsx` — **`window.print()`**; user saves via browser print dialog |
| Share links | `ProfileShareMenu` — external URLs (`window.open`), not file export |

No client-side assembly of CSV/Excel from table data was found in production pages.

### 3. Bulk **import** (related but not export)

Admin **resume import** (`ResumeImportController`, `StoreResumeImportRequest`) accepts **file uploads**, processes server-side, redirects with **flash message** — not a download/export workflow. Documented in `09-RESUME-DOCUMENTS-FILES.md` / `25-HIRE-IDENTITY-AND-RESUME-IMPORT.md`.

### 4. Reporting / analytics export

Admin **Analytics** and **Finance** pages (per PR-5AB inspection and route/UI search) expose **in-app aggregates and tables** without **“Export CSV/Excel”** actions or dedicated export routes in this codebase.

### 5. UI interaction pattern (consistent, minimal)

- **Navigation model:** Plain **`href`** on `<a>` or DS **`Button` / Public `Button` with `href`** — full document navigation; **not** `router.get` / Inertia visits (appropriate for binary responses).
- **Loading / success:** Browser-native download handling; no app-wide export progress component.
- **Errors:** HTTP **403/404** from authorization or missing files; import flows use **redirect + flash** + optional **Alert** on next page — not a download-specific Toast contract.

---

## Candidate component / contract decisions

| Candidate | Evidence | Decision |
|-----------|----------|----------|
| **ExportButton / DownloadLink molecule** | Many pages use text links or `Button href={download_url}` with visible labels; no extra props (format, job id, blob handler) shared across domains | **Reject** — thin wrapper over **Button** / **Link** with no stable API beyond `href` |
| **Generic export wrapper** (client CSV/Excel) | No library, no `createObjectURL` usage for reports | **Reject** — speculative |
| **Export progress / async job UI** | No queued export jobs or polling in FE | **Reject** |
| **Export-specific EmptyState / Toast** | EmptyState used for empty lists; Toast for general feedback (PR-5V) | **Reject** — use existing molecules; no export semantics |
| **Shared visualization/report download contract** | Out of scope for PR-5AC; PR-5AB found no chart export | **N/A** |
| **Icon + label convention** | Registry includes `download` icon; optional on buttons | **Keep as icon usage**, not a new component |

---

## Server/domain versus Design System ownership

| Concern | Owner |
|---------|--------|
| Authorization, policies, ownership checks | Laravel policies + controllers |
| File storage, disks, virus/size rules, MIME allowlists | Domain services + `*FileRules` support classes |
| Route naming, `download_url` in API payloads | Models / assemblers / controllers |
| Report generation, CSV/Excel column definitions, filter encoding | **Not present** — future feature gates stay **backend-first** |
| Trigger control (label, placement, optional icon) | Pages compose **Button**, **Link**, **LinkButton**, **Text** |
| User feedback for **imports** or form posts | **Alert**, flash, **Toast** (general) — not DS “export” |

Design System components must **not** embed export formats, query serialization, or entitlement logic.

---

## Accessibility and interaction findings

- **Prefer descriptive link text:** Existing copy varies (“Download”, “Download resume”, “Download snapshot”, filename hints) — good when filename or context is adjacent; avoid icon-only download without `aria-label`.
- **Native anchors for files:** Using `<a href>` (or Button rendering as link) preserves open-in-new-tab, save-as, and assistive technology expectations for file resources.
- **`download` attribute:** Not required as a DS standard; server `Content-Disposition` defines attachment behavior.
- **Print/PDF:** Print action should remain a **labeled button** (“Print / save PDF”); print styles are page-owned (`resume-print-hide` classes on builder).
- **No export-only loading state:** If long-running server exports are added later, progress UI belongs to **that domain feature** (or general **Spinner** on a form submit), not a frozen export primitive in PR-5AC.

---

## Files created and modified (PR-5AC)

| Action | Path |
|--------|------|
| **Created** | `docs/OURNEAR_EXPORT_DOWNLOAD_ARCHITECTURE_REVIEW_PR5AC.md` |
| **Modified** | *(none — application code unchanged)* |

**Application code changed:** **No**  
**Dependencies changed:** **No**  
**V2 consumers changed:** **No**

---

## Verification

| Check | Result |
|-------|--------|
| TypeScript (`npm run typecheck`) | **Not run** — documentation-only |
| Production build (`npm run build`) | **Not run** — documentation-only |

---

## V2 isolation and migration status

| Item | Status |
|------|--------|
| V2 inspection | Read-only (patterns mirror V1: href-based downloads; no V2-specific export SDK found) |
| V2 modification | **None** |
| V2 adoption / migration | **Not started** |

---

## Stop condition

PR-5AC complete. **Do not start PR-6A.** **Do not begin V2 adoption or migration.**
