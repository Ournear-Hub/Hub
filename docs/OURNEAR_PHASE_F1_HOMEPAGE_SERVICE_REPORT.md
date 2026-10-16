# Phase F1/F2 — Compact recruiter-first homepage & service pages

**Date:** 2026-10-10  
**Agent:** 1 (implementation)  
**Independent QA:** **Not performed by Agent 1** — Agent 2 required  
**Commit / push / deploy:** **None**

---

## 1. Pages and components changed

| Area | Files |
| --- | --- |
| Homepage | `resources/js/Pages/Home.tsx` |
| Public header / footer | `resources/js/Components/Public/Header.tsx`, `Footer.tsx` |
| Service heroes | `resources/js/Components/Public/PublicPageHero.tsx` (`compact` prop) |
| Hiring / Talent / Staffing | `resources/js/Pages/Platform/Hire.tsx`, `TalentPool.tsx`, `Staffing.tsx` |
| Jobs landing | `resources/js/Pages/Jobs/Index.tsx` (compact hero copy) |
| New shared UI | `PublicModuleCard.tsx`, `PublicPipelineStrip.tsx`, `PublicProductProofGrid.tsx`, `PublicFeatureTiles.tsx` |
| Content map | `resources/js/content/publicModules.ts` |

**Not changed (Agent 2 QA scope):** `resources/js/Components/Admin/Sidebar.tsx`, C1/C2 journey files, screening workspace, publication policy services.

---

## 2. Assets reused

| Asset | Use |
| --- | --- |
| Tabler via **OurNearIcon** registry | `briefcase`, `search`, `user`, `building`, `clipboard`, `message`, `check`, `chevron-right` |
| **BrandMark** + public branding props | Header (unchanged component) |
| **JobCard**, **PublicCompanyCard**, **EmptyState** | Home job/company strips |
| CMS sections (`hero`, `home_cta`, etc.) | Headlines where present; recruiter-first defaults when absent |
| Design tokens | `bg-brand` (#214449), `bg-accent` (orange CTA), existing Button `surface="public"` |

**Screenshot gap:** No new files under `public/` — product-proof section uses icon tiles + honest copy. Agent 2 may capture anonymized screenshots later.

---

## 3. Header compaction

- Height **h-12 / sm:h-14** (was h-14 / sm:h-16).
- Sticky bar with light backdrop blur.
- Guest desktop: **Login**, **Start Hiring** (`/hire`, accent), **Register**.
- Guest mobile drawer: **Start Hiring** first, then Login / Register.

Dashboard layouts **unchanged** (AccountLayout / AdminLayout).

---

## 4. Homepage (recruiter-first)

1. Compact brand hero — headline + one sentence; **Start Hiring** primary, **Find Jobs** secondary.
2. Pipeline strip (real ATS stage labels).
3. Job search form (unchanged behavior → `GET /jobs`).
4. **Four module cards** (Hiring, Talent Pool, Jobs, Staffing).
5. Product-proof tiles → `/jobs`, `/hire`, `/services`.
6. Condensed open roles / featured / companies (when data present).
7. Final brand CTA band.

Removed long “Why OurNear” bullet block and duplicate employer prose sections.

---

## 5. Service pages

| Route | Primary CTA | Secondary |
| --- | --- | --- |
| `/hire` | **Start Hiring** → `/register` (guest) or `/account/hiring` (signed in) | Find Jobs → `/jobs` |
| `/talent-pool` | Open Hiring / Start Hiring → `/account/hiring` or `/hire` | Submit requirement → `/contact` |
| `/staffing` | Request Staffing → `/for-employers` | Contact → `/contact` |
| `/jobs` | (hero copy only; search below) | — |

Each page: **compact PublicPageHero**, icon **feature tiles**, reduced prose.

---

## 6. CTA / route verification (static code review)

| CTA | Destination | Notes |
| --- | --- | --- |
| Start Hiring | `/hire`, `/register`, `/account/hiring` | Context-dependent; no new routes |
| Find Jobs / Browse | `/jobs` | Existing index + filters |
| Request Staffing | `/for-employers` | Existing POST enquiry form |
| Explore Talent | `/talent-pool` | Live search + profile links |
| Product proof → Services | `/services` | Existing `Services.tsx` |

No authentication or ATS backend logic modified.

---

## 7. Admin navigation (Phase F3)

**Deferred** — Agent 2 actively testing admin sidebar accordion; no regroup into eight handoff labels in this batch.

---

## 8. Build validation (Agent 1)

| Command | Result |
| --- | --- |
| `npm run typecheck` | **PASS** (exit 0) |
| `npm run build` | **PASS** (~47s) |

---

## 9. Browser / DB checks

| Check | Status |
| --- | --- |
| Browser visual desktop/mobile | **Not run** (MySQL/app server not required for build; no Playwright in this batch) |
| `PublicHomePageTest` | **Not re-run** (requires DB) |
| `test_public_home.py` | **Not re-run** |

---

## 10. Agent 2 QA scope

- Homepage hierarchy and CTAs at **1440×900** and **390×844**.
- Header size, sticky behavior, mobile drawer.
- `/hire`, `/talent-pool`, `/staffing`, `/jobs` — links, overflow, touch targets.
- Guest vs signed-in CTA destinations (employer/candidate).
- Regression: Stage 1 public checklist samples.

---

## 11. Known limitations

- Hire guest **Start Hiring** points to **`/register`** (marketing choice); employer activation still occurs post-registration in account — not altered in this batch.
- No marketing screenshots in repo yet.
- **FE-201/202** still blocked; unrelated to Phase F1.

---

*End of Phase F1 implementation report.*
