# Agent 2 — Phase C3 employer photo browser QA

**Run ID:** `20261011T014500Z-c3-photo-qa`  
**Date:** 2026-10-11  
**Role:** Agent 2 (QA only)  
**Reference:** `docs/OURNEAR_PHASE_C3_EMPLOYER_PROFILE_PHOTO_REPORT.md`  
**Artifacts:** `qa-agent/artifacts/agent2/20261011T014500Z-c3-photo-qa/` (`c3_results.json`, screenshots `c3-*.png`, helper scripts)

---

## 1. Environment

| Check | Result |
| --- | --- |
| `php scripts/probe_local_mysql.php` | **PASS** — `CONNECTED user=root password_set=yes`, exit **0** |
| **REG-ENV-001** | **PASS** (this run) |
| `http://127.0.0.1:8000` | **200** (existing local serve) |
| `php artisan qa:hiring-fixtures` | **NOT RUN** (fixtures already ready per prior capacity script) |

**Not re-run:** `EmployerProfilePhotoTest`, typecheck, build (Agent 1 evidenced).

---

## 2. Commands

```powershell
cd "D:\Ournear Hub\Ournear Hire"
php scripts/probe_local_mysql.php

cd qa-agent
$env:OURNEAR_ALLOW_RUN="local"
python artifacts/agent2/20261011T014500Z-c3-photo-qa/c3_browser_qa.py
python artifacts/agent2/20261011T014500Z-c3-photo-qa/debug_upload.py
```

| Command | Exit / outcome |
| --- | --- |
| `c3_browser_qa.py` | **1** (multiple scenario failures) |
| `debug_upload.py` | **0** — PUT `/account/profile` → **303**, no `img[src*="/photo"]` in DOM |

---

## 3. Phase C3 browser scenarios (Employer A / B fixtures)

Accounts: `qa-employer-a@example.test`, `qa-employer-b@example.test` (`qa-agent/config/local.env`).

| ID | Scenario | Result | Evidence |
| --- | --- | --- | --- |
| **A** | Upload PNG via **Edit company profile → Company logo → Save → reload** | **FAIL** | No logo image URL in DOM after reload; header remains initials **PC** — `c3-a-reload.png` |
| **A′** | Re-login persistence | **FAIL** | Depends on A — `c3-a-relogin.png` |
| **B** | Replace with second PNG | **FAIL** | No persisted photo to replace — `c3-b-replace.png` |
| **C** | Remove logo | **FAIL** | Already no logo; remove N/A — `c3-c-remove.png` |
| **D** | Invalid PDF via save workflow | **FAIL** | No visible validation message; UI stuck **Saving…** — `c3-d-invalid.png` |
| **E** | Employer B accesses A photo URL | **NOT TESTED** | No `photo_url` produced in A |
| **F** | Responsive profile shell desktop/mobile | **PASS** | `c3-responsive-desktop.png`, `c3-responsive-mobile.png` |

**Optional public surfaces:** **NOT TESTED**.

---

## 4. Confirmed defects (Agent 1)

### C3-DEF-001 — Browser save does not persist company logo

| Field | Detail |
| --- | --- |
| **Area** | REG-PROD-006 / employer `Account/Profile` edit form |
| **Steps** | Login as Employer A → `/account/profile` → **Edit company profile** → choose valid PNG under **Company logo** → **Save** |
| **Expected** | Success message; after reload, logo visible (authorized `/users/{id}/photo` or preview img); persists after re-login |
| **Actual** | PUT returns **303**; after reload view mode still shows initials **PC**, no `/photo` URL in page HTML; `img_srcs` empty in debug |
| **Note** | `EmployerProfilePhotoTest` **PASS** via HTTP POST/PUT in PHPUnit — **browser Inertia form path appears broken or not attaching file** |
| **Priority** | P2 (C3 release gate) |

### C3-DEF-002 — Invalid PDF leaves form saving

| Field | Detail |
| --- | --- |
| **Steps** | Edit mode → select `fixture-invalid.pdf` as logo → **Save** |
| **Expected** | Validation error; form returns to editable state; prior logo unchanged |
| **Actual** | **Saving…** disabled state persists (`c3-d-invalid.png`) |
| **Priority** | P2 |

---

## 5. Priority B — other awaiting-QA scopes

Not re-executed this run (evidence already on file unless noted):

| Scope | Status this run | Prior evidence |
| --- | --- | --- |
| C1 cross-role | **NOT RE-RUN** | **PASS** — `20261011T011700Z-targeted-exec` |
| C2 screening | **NOT RE-RUN** | **FAIL** — compare panel name — same run |
| F3 admin sidebar | **NOT RE-RUN** | **PARTIAL** — `20261011T011700Z-targeted-exec` |
| F1/F2 public pages | **NOT RE-RUN** | **PARTIAL** — same run |
| Phase G1 pipeline | **NOT TESTED** | No new evidence |

---

## 6. Register impact

| ID | QA status |
| --- | --- |
| **REG-ENV-001** | **PASS** |
| **REG-PROD-006** | **FAIL** (browser persistence / validation UX) — **not QA-confirmed** |
| **REG-PROD-003 / 004** | Unchanged (not fully confirmed) |

**Release verdict:** **`insufficient_evidence`**.

---

---

## 7. Supplement — `20261011T091800Z-upload-c3-reg-md-ext`

After **`scripts/start-local-web-server.ps1`** and multipart probe **PASS**, focused pytest **`test_c3_employer_upload_browser.py`**: **2/2 PASS** (logo persist/reload + invalid PDF feedback; company document + cross-employer deny). Supersedes prior **C3-DEF-001/002** browser failures tied to **`UPLOAD_ERR_NO_TMP_DIR`**.

| ID | QA status (local) |
| --- | --- |
| **REG-PROD-006** | **PARTIAL** — local browser **PASS**; **PHP-FPM/staging NOT TESTED** |

Report: `qa-agent/artifacts/agent2/20261011T091800Z-upload-c3-reg-md-ext/UPLOAD_C3_REG_MD_EXT_REPORT.md`

*Prior section 6 register row superseded for local C3 evidence only.*
