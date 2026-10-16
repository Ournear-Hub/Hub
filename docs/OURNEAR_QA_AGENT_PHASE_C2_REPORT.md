# OurNear Human QA Agent — Phase C2 report

**Date:** 2026-10-09  
**Scope:** Form and navigation helpers; focused tests; smoke login assertion.  
**Application code:** unchanged.

---

## Files created or changed

| Path | Change |
| --- | --- |
| `qa-agent/ournear_qa/browser/locators.py` | **Created** — unique label/button resolution, `LocatorError` |
| `qa-agent/ournear_qa/browser/forms.py` | **Created** — `FormHelper` |
| `qa-agent/ournear_qa/browser/navigation.py` | **Created** — `NavigationHelper`, local path only |
| `qa-agent/ournear_qa/browser/files.py` | **Created** — synthetic text/PDF under `fixtures/generated/` |
| `qa-agent/ournear_qa/browser/__init__.py` | Export new helpers |
| `qa-agent/tests/fixtures/form_page.html` | **Created** — deterministic form fixture |
| `qa-agent/tests/fixtures/ambiguous_page.html` | **Created** |
| `qa-agent/tests/test_forms_navigation.py` | **Created** — 11 tests |
| `qa-agent/tests/smoke/test_account_login.py` | Uses helpers; asserts **Dashboard** heading |
| `qa-agent/.gitignore` | Ignore `fixtures/generated/` |

---

## Supported helpers

### `FormHelper`

| Method | Purpose |
| --- | --- |
| `fill_field` / `fill_textarea` | Labeled text controls |
| `select_option` | Native `<select>` by label |
| `set_checkbox` / `choose_radio` | Toggle / radio by label |
| `upload_file` | `set_input_files` with synthetic path |
| `click_submit` | Named submit button only (no auto-submit) |
| `expect_field_value` | Value assertion |
| `expect_field_invalid` | `aria-invalid="true"` |
| `expect_validation_message` / `expect_success_message` | Visible feedback |

### `NavigationHelper`

| Method | Purpose |
| --- | --- |
| `goto_path` | GET `{base_url}{path}`; rejects `://` and non-local hosts |
| `wait_for_path` | Playwright URL wait with actionable timeout error |
| `assert_url_contains` | URL fragment check |
| `assert_heading` / `assert_visible_text` | Visible state |

### `locators.by_label` / `by_role_button`

Fail on 0 or >1 matches (`LocatorError`).

### Synthetic files

` synthetic_text_file()`, `synthetic_pdf_file()` → `qa-agent/fixtures/generated/` (gitignored).

---

## Safety and redaction

- Form helpers never log field values; errors reference labels only.
- Navigation refuses external URLs and non-local `base_url` hosts (same allowlist as settings).
- No automatic form resubmission or retries.
- `BrowserSession` diagnostics unchanged; attached via existing `browser_session` fixture on failure.

---

## Tests executed (actual results)

**Focused (first):**

```powershell
.venv\Scripts\python.exe -m pytest tests/test_forms_navigation.py -v
```

**11 passed** (~53s on first run; 10 passed + 1 fix, then full suite).

**Full QA agent regression:**

```powershell
.venv\Scripts\python.exe -m pytest tests/test_forms_navigation.py tests/test_browser_session.py tests/smoke tests/test_settings_gate.py tests/test_registry.py -v
```

**28 passed**, 0 failed, 0 skipped (~58s).

| Suite | Tests |
| --- | ---: |
| `test_forms_navigation.py` | 11 |
| `test_browser_session.py` | 10 |
| smoke | 2 |
| settings + registry | 5 |

---

## Known limitations

- No Inertia-specific “network idle” wait yet (domcontentloaded + role/URL assertions only).
- `expect_validation_message` not used in fixtures (HTML5 + `aria-invalid` path used instead).
- Staging host allowlist for `NavigationHelper` not expanded (local hosts only).
- No full candidate/employer/admin journeys (Phase D).

---

## Next recommended step

**Phase C3** (per plan): tighten `files.py` / PDF contract tests if needed, then **Phase C4** registry schema (`journeys`, `phpunit`, `depends_on`) before **Phase D** P0 hiring journeys using `FormHelper` + `NavigationHelper`.
