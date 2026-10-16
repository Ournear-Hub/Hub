# Phase F3 — Admin navigation regrouping

**Date:** 2026-10-11  
**Agent:** 1 (development only)  
**Status:** **PHASE F3 IMPLEMENTED — AWAITING QA** (2026-10-12 server catalog + tests authored, not run)

**Agent 2 context:** Run `20261010T184500Z-agent2-qa` — sidebar single-expand **code review PASS**; browser/kbd/mobile **NOT TESTED** (`docs/OURNEAR_QA_AGENT2_INDEPENDENT_HANDOFF_REPORT_20261010.md`). **REG-ENV-001** remains open (MySQL `NO_MATCH`); no environment changes in this batch.  
**Commit / push / deploy:** **None**

---

## 1. Files changed

| File | Change |
| --- | --- |
| `app/Support/AdminNavigationCatalog.php` | **2026-10-12:** Canonical eight-group nav catalog; permission filtering; scorecard templates + design system (UI Lab) links |
| `app/Http/Middleware/HandleInertiaRequests.php` | Shared `adminNav` prop on admin surface |
| `resources/js/Components/Admin/Sidebar.tsx` | Renders `adminNav`; Tabler icons via `OurNearIcon`; chevron expand/collapse; `aria-current` on active links |
| `resources/js/Components/Admin/adminNavigation.ts` | Active-route helpers (+ design-system nested paths) |
| `resources/js/Components/Admin/adminNavigation.test.ts` | Vitest active-state cases — **NOT RUN** |
| `tests/Feature/AdminNavigationCatalogTest.php` | PHPUnit catalog + Inertia + recruiter visibility — **NOT RUN** |

**Not modified:** Public F1/F2 pages, QA journeys, backend routes, permissions definitions.

---

## 2. Eight-group mapping (all prior routes retained)

| Group | Items (href unchanged) |
| --- | --- |
| **1. Overview** | Dashboard (`/admin`); Hiring Command Center (`/admin/hiring`) if `can_view_hiring`; Analytics (`/admin/analytics`) if `can_view_analytics` |
| **2. Recruitment** | My Work, Jobs, Applications, Interviews, **Scorecard templates**, Tasks, Automation, Screening, Shortlisted, Recruitment Partnership, Placements, Employees, Replacements |
| **3. Employers & Companies** | Companies, Staffing Enquiries, Partners, CRM |
| **4. Candidates** | Talent Pool, Resume Import |
| **5. Communications** | Notifications, Messages, Campaigns, Message templates, Email delivery (`website.manage`) |
| **6. Website & Content** | Website Content, Media & Social, Design system (UI Lab, `website.manage`) |
| **7. Master Data** | Industries, Categories, Job Titles, Locations, Other Master Data |
| **8. Administration** | Users (+ segments), Admin Users, Roles & Permissions, Activity Logs, Login Logs, Freelancer intake, Investors, Invoices, Payments, Commission, Settings |

**Relocated (no route change):** Staffing **Employees/Replacements** → Recruitment; **CRM** → Employers & Companies; **Campaigns** → Communications; **Finance + Settings + People/Security** → Administration; **Master Data tabs** split out of old “System”.

**2026-10-12:** `/admin/design-system` and `/admin/scorecard-templates` added to catalog (existing routes; `website.manage` / `applications.view` respectively). Link visibility ≠ authorization.

---

## 3. Preserved behavior (code inspection)

| Behavior | Status |
| --- | --- |
| Single-expand accordion | Unchanged `toggle()` — opening one group closes others |
| Permission gates on items | Unchanged `permission` fields and `visibleItems()` |
| HCC / Analytics visibility | Still gated by `can_view_hiring` / `can_view_analytics` (Overview items) |
| Active link highlighting | Existing `isActive()` + `data-active`; extended for hiring/analytics nested paths |
| User segment children | Unchanged under Administration → Users |
| Mobile drawer / scroll restore | Unchanged |

---

## 4. Validation (Agent 1)

| Check | Result |
| --- | --- |
| `npm run typecheck` | **PASS** (re-run 2026-10-11 session, exit 0) |
| `npm run build` | **PASS** (re-run ~110s, exit 0) |
| `AdminNavigationCatalogTest` + `adminNavigation.test.ts` | **Authored — NOT RUN — deferred by product owner** |
| Browser / responsive QA | **Not run** |

---

## 5. Risks for Agent 2

- Long **Recruitment** and **Administration** lists on small viewports — verify scroll and focus order.
- **Overview** auto-expands when Dashboard/HCC/Analytics active — confirm only one group open when user toggles another.
- Staff users with partial permissions — confirm empty groups are hidden and no unauthorized links appear.

---

## 6. Agent 2 QA scope

- Desktop + mobile admin sidebar: expand/collapse, active states on sample routes per group.
- Permission smoke with non-super-admin role if available.
- Regression with prior single-expand test batch.

---

*End of Phase F3 report.*
