# REG-PHP-003 — Unverified employer publication policy (local browser closure)

**Run ID:** `20261011T120000Z-reg-php-003-browser`  
**Timestamp:** 2026-10-11 (local)  
**Environment:** `http://127.0.0.1:8000` via `scripts/start-local-web-server.ps1`  
**Database:** `ournear_hire_local` (non-destructive; trial expiry uses restore script)

## Scope

Local browser acceptance for **REG-PHP-003** complementing existing PHPUnit (`EmployerAccountWorkspaceTest::test_employer_jobs_are_owned_validated_trial_then_basic_and_never_unpaid_feature`).

## Product change (listing label)

- **`resources/js/Components/Public/JobCard.tsx`:** Show **Employer Not Verified** on list/split-view cards (E1-OBS-001); featured/urgent badges remain non-list only.

## Browser cases (Playwright)

| Case | Result |
| --- | --- |
| Public job detail shows unverified label | **PASS** |
| Jobs listing (split view) shows unverified label on fixture card | **PASS** |
| Unverified employer second publish blocked (draft save + publish attempt) | **PASS** |
| Trial expiry hides job from public (scripted clock + HTTP 404 + restore) | **PASS** |

**Pytest:** `tests/policy/test_reg_php_003_unverified_publish_browser.py` — **4/4 PASS** (~102s)  
**Log:** `qa-agent/artifacts/agent2/20261011T120000Z-reg-php-003-browser/pytest-final.log`

## PHPUnit (re-run this batch)

```text
php artisan test --filter=test_employer_jobs_are_owned_validated
```

**Result:** **PASS** (21 assertions)

## Harness / scripts

- `scripts/qa-reg-php-003-inspect.php`, `qa-reg-php-003-expire-trial.php`, `qa-reg-php-003-restore-published-a.php`
- `qa-agent/pyproject.toml` marker `reg_php_003`

## Notes / limitations

- **Admin verify lifts limit:** covered by PHPUnit only (no mutating admin approval in browser).
- **Legacy fixture state:** Employer A has many pre-policy published jobs; browser second-publish test validates **new** draft publish rejection, not retroactive unpublish.
- **Playwright on Windows:** set `PLAYWRIGHT_BROWSERS_PATH=%LOCALAPPDATA%\ms-playwright` when Cursor sandbox cache lacks browsers.
- **Vite:** `public/build/manifest.json` required before Inertia pages render (built this run).

## Disposition

**REG-PHP-003 QA:** **PASS** (local PHPUnit + mandatory browser matrix above).
