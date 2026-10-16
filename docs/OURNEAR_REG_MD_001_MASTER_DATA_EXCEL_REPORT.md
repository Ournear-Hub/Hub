# REG-MD-001 — Master data Excel import/export

**Date:** 2026-10-11 (updated Phase 5C closure)  
**Workspace:** `D:\Ournear Hub\Ournear Hire`  
**Issue:** `REG-MD-001`

---

## 1. Discovery vs Stage 5F

Stage 5F QA (`20261011T131500Z-stage5f-admin-ui-closure`) ran against a tree where Excel I/E was **not visible** in the inspected scope. The **current working tree** includes full implementation (routes, `MasterDataExcelService`, admin UI panel, migration, PhpSpreadsheet).

---

## 2. Supported entities

| Entity | Route slug | Create via import | Notes |
| --- | --- | --- | --- |
| Industries | `industries` | Yes | |
| Job categories | `categories` | Yes | FK: `industry_name` → active industry |
| Job titles | `job-titles` | Yes | FK: `job_category_name` → active category |
| Locations | `locations` | Yes | |
| Employment types | `employment-types` | Yes | |
| Experience ranges | `experience-ranges` | Yes | |
| Qualifications | `qualifications` | Yes | |
| Job statuses | `job-statuses` | **No** | Update-only (`id` required) |
| Application statuses | `application-statuses` | **No** | Update-only |
| Rejection reasons | `rejection-reasons` | Yes | |

**Not in scope:** **Skills / skill categories** — not part of `MasterEntityType` admin master-data tabs (skills attach to jobs via separate models).

---

## 3. Architecture

| Component | Role |
| --- | --- |
| `MasterDataExcelSchema` | Column contract + template instructions row |
| `MasterDataExcelService` | Template, export, parse, preview, confirm (transaction) |
| `MasterDataExcelController` | Streamed template/export; Inertia redirect preview/confirm |
| `MasterDataImportPreview` | Tokenized preview payload (user-scoped, expires) |
| `MasterDataExcelPanel` | Admin UI on `Admin/MasterData/Index` |
| Routes | `admin/master-data/{type}/excel/{template,export,preview,confirm}` |

**Dependency:** `phpoffice/phpspreadsheet` **^5.10** (`composer.json` / `composer.lock`).

**Permissions:** `User::canManageMasterData()` / `MasterDataPolicy` on template & export; Form requests on preview & confirm.

---

## 4. Matching and validation

- **Match order:** numeric `id` if present; else case-insensitive `name` (with industry/category scope for hierarchical types).
- **Reject:** missing headers, empty workbook, invalid file type, unknown `id`, invalid FK names, in-file duplicate names, disallowed creates on update-only types.
- **Confirm:** one transaction; invalid preview rows stay `reject` and are not written; valid rows create/update via `MasterDataService` + audit.

---

## 5. Automated verification (2026-10-11)

| Command | Result |
| --- | --- |
| `php artisan test --filter=MasterDataExcelImportExportTest` | **PASS** — **20** tests, **82** assertions |
| `php artisan test --filter=MasterDataTest` | **PASS** — 10 tests, 78 assertions |
| `npm run typecheck` | **PASS** |
| `npm run build` | **PASS** |

---

## 6. Browser verification (Agent 2)

**Acceptance closure (canonical):** `20261011T094500Z-reg-md-001-acceptance-closure` — `REG_MD_001_BROWSER_QA_REPORT.md`  
**Prior runs:** `20261011T154500Z-reg-md-001-browser` (5/5), `20261011T091800Z-upload-c3-reg-md-ext` (3/3 extension)

| Check | Result |
| --- | --- |
| Admin panel + **10/10** template/export | **PASS** |
| E2E import (industries, categories, job-titles) + industry update-by-id | **PASS** |
| Preview validation (all entity types — E2E or reject preview) | **PASS** |
| Token lifecycle + guest/employer authorization | **PASS** |
| Boundaries **5 MB** / **2000** rows + invalid file/header/FK | **PASS** |
| Corrupt workbook / in-file duplicate names (browser) | **NOT TESTED** (PHPUnit) |
| Skills library | **Out of scope** |

**REG-MD-001 QA:** **PASS** (2026-10-11 Agent 2 closure).

---

## 7. Limitations

- Workbooks capped at **2000** rows; uploads **5 MB**.
- No dedicated import-history admin UI (preview rows in DB + per-record audit).
- Browser **upload** acceptance requires a PHP environment with working `upload_tmp_dir` / system temp for multipart POSTs.

---

## 8. Git / deploy

**No commit, push, or deployment** performed in this batch.

---

*End of REG-MD-001 report.*
