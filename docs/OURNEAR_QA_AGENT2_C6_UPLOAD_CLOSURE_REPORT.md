# Agent 2 — REG-PROD-006 / Stage 4 upload persistence closure

**Run ID:** `20261011T142000Z-c6-upload-closure`  
**Date:** 2026-10-11  
**Reconciles:** `20261011T132500Z-stage4-field-closure` (salary/vacancy/location/benefits **reused PASS** — not rerun)  
**Register:** REG-PROD-006  

---

## 1. Root cause (confirmed)

| Layer | Finding |
| --- | --- |
| **Environment (primary)** | HTTP requests handled by Laravel’s child **`php -S` (cli-server)** process used **`upload_tmp_dir=C:\Windows`** (not writable) → **`UPLOAD_ERR_NO_TMP_DIR` (6)**, empty `$_FILES`, validation failures / 302 without persistence. |
| **CLI vs web** | **CLI** (`php scripts/verify-local-env.php`) reported writable `sys_get_temp_dir()` — **does not predict web SAPI**. Probe: `scripts/probe-web-php-upload.php` + route `POST /__local/upload-env-probe` (local only). |
| **`php artisan serve` + `-d`** | `-d upload_tmp_dir=…` on the **parent** `artisan serve` process **does not** apply to the spawned **`php -S`** worker (see `ServeCommand::serverCommand()`). |
| **Application** | No code defect found for storage/DB once multipart reaches Laravel. `EmployerProfilePhotoTest` and employer-document isolation PHPUnit **PASS**. |

**Local correction (reversible):** `scripts/start-local-web-server.ps1` — runs `php -d upload_tmp_dir=%LOCALAPPDATA%/Temp/ournear-hire-uploads -S 127.0.0.1:8000` with Laravel’s `server.php` router (cwd `public/`). Does **not** edit global `php.ini`.

**Evidence:** `qa-agent/artifacts/agent2/20261011T142000Z-c6-upload-closure/web_php_upload_probe.json` — web `file_error: 0`, `has_file: true` after fix.

---

## 2. Logo (company profile) — browser

| Check | Result |
| --- | --- |
| Upload PNG via `#employer-logo` + Save | **PASS** |
| Reload — same `/users/{id}/photo` displayed | **PASS** |
| Edit + Save again — logo intact | **PASS** |
| Employer B GET photo URL | **403 PASS** |
| Invalid PDF as logo — inline error + not stuck (browser) | **FAIL** in run `142000Z` — **superseded PASS** in `20261011T152000Z-c3-invalid-logo` (app `onSuccess` + test timing); see `docs/OURNEAR_QA_AGENT2_C3_INVALID_LOGO_CLOSURE_REPORT.md` |

Artifacts: `c6_upload_results.json`, C3 screenshots refreshed under `20261011T120000Z-c3-verify/` (partial scenario pass on re-run).

---

## 3. Employer company documents — browser

| Check | Result |
| --- | --- |
| Upload PDF via `/account/company-documents` form | **PASS** (list after submit + reload) |
| DB + storage | **PASS** (row visible in UI; download **200**) |
| Authorized download | **PASS** |
| PHPUnit isolation (Employer B forbidden) | **PASS** (`ProductionUxStage18Test`) |

Prior **302 without list** was **environment** (no file in `$_FILES`), not authorization bypass.

---

## 4. Tests executed

| Command | Outcome |
| --- | --- |
| `php scripts/probe-web-php-upload.php` | **PASS** (post-fix) |
| `php artisan test --filter=EmployerProfilePhotoTest` | **2/2 PASS** |
| `php artisan test --filter=test_stage_17_and_recruitment_surfaces_remain_protected` | **PASS** |
| `python …/20261011T142000Z-c6-upload-closure/c6_upload_closure.py` | **6/7 PASS** (invalid PDF browser UX) |
| `python …/20261011T120000Z-c3-verify/c3_browser_qa.py` (rerun) | Persistence/isolation **PASS**; flash/remove/invalid scenarios **partial** |

---

## 5. Staging / PHP-FPM

**NOT TESTED** — no staging or PHP-FPM stack in this workspace. Required hosting-style acceptance remains **open** until verified on a server where multipart temp upload is configured (not `C:\Windows`).

---

## 6. Code / config changes

| Change | Purpose |
| --- | --- |
| `scripts/start-local-web-server.ps1` | Writable `upload_tmp_dir` on the **same** `php -S` process |
| `scripts/probe-web-php-upload.php` | CLI vs HTTP upload diagnostic |
| `routes/web.php` | Local-only `POST /__local/upload-env-probe` (no CSRF; **local** env only) |
| `storage/app/tmp/php-upload/.gitkeep` | Optional project temp path (primary fix uses `%LOCALAPPDATA%/Temp/ournear-hire-uploads`) |

**No** production deploy, DB reset, or auth weakening.

---

## 7. Register / Stage 4 impact

| Item | Status |
| --- | --- |
| **REG-PROD-006** | **PARTIAL** — local browser logo + document **persistence PASS** with corrected dev server; invalid logo **browser** feedback still **FAIL** in automation (PHPUnit validation **PASS**); **PHP-FPM/staging NOT TESTED** |
| Stage 4 §F logo + documents (`132500Z`) | **PASS** (this run) for persistence; other §F fields **reused** from prior report |
| **Release verdict** | **`insufficient_evidence`** (unchanged globally) |

---

## 8. Next action

1. Use **`scripts/start-local-web-server.ps1`** (not plain `artisan serve`) for Windows local browser QA involving uploads.  
2. On staging/PHP-FPM: confirm `upload_tmp_dir` / system temp writable; rerun `c6_upload_closure.py` or C3 suite.  
3. Optional product follow-up: investigate employer profile **invalid logo** browser UX (Saving state) — separate from env blocker.
