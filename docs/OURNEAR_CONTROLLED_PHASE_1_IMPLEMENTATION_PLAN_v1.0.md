# OURNEAR CONTROLLED PHASE 1 IMPLEMENTATION PLAN v1.0

**Type:** Planning only (no code changes authorized by this document)  
**Project:** `D:\Ournear Hub\Ournear Hire`  
**Architecture:** [OURNEAR_UNIFIED_PLATFORM_ARCHITECTURE_v1.1.md](./OURNEAR_UNIFIED_PLATFORM_ARCHITECTURE_v1.1.md)  
**Library:** [OURNEAR_CORPORATE_LIBRARY_SPECIFICATION_v1.0.md](./OURNEAR_CORPORATE_LIBRARY_SPECIFICATION_v1.0.md)

---

## 1. Objective

Deliver a **small, reversible, compatibility-first** Phase 1 that establishes:

1. Canonical **design foundation / token ownership** (without breaking existing Tailwind usage)  
2. **OurNear Icon Registry** schema + **`OurNearIcon`** wrapper contract  
3. **First unified primitives** sufficient to prove the stack—without ATS, auth, shell, or page-wide migrations  

V2 must keep working throughout; changes are **frontend-only** and rollback-friendly.

---

## 2. Source of Truth

| Document | Use in Phase 1 |
|----------|------------------|
| Architecture v1.1 | Layers, shells (not migrated), dependency rules |
| Library Spec v1.0 | Atoms, tokens, icons, surface variants, PR boundaries |
| `AGENTS.md` / `DEVELOPMENT-RULES.md` | No auth/ATS/match-engine changes |
| `CURRENT-STATE.md` | What is implemented |
| V2 files listed in §6–§7 | Exact touch points |

---

## 3. Phase 1 Scope

### A. Design foundation

- Document and centralize ownership of **existing** tokens in `app.css` + `classNames.ts`  
- Add **non-breaking** canonical exports (TypeScript/CSS comments / re-exports)—no new arbitrary colors  
- Document spacing (Tailwind default), radius, shadows, focus (`focusRing`), motion (minimal: spin on Spinner only today), breakpoints (`sm|md|lg|xl`)  
- **No dark mode implementation** in Phase 1 (readiness notes only)

### B. OurNear Icon foundation

- Registry **schema** (JSON or TS manifest)  
- **`OurNearIcon`** wrapper contract  
- Map **existing** `icons.tsx` icons into registry (no mass Tabler migration in Phase 1)  
- **Optional Phase 1b:** add `@tabler/icons-react` dependency only when implementing wrapper—not in planning task  

### C. First unified primitives (minimum set)

| Primitive | Phase 1 | Rationale |
|-----------|---------|-----------|
| **OurNearIcon** (+ registry) | **Required** | Foundation for all surfaces |
| **Button** | **Required** | Validates surface variants + highest duplication (Public vs Admin) |
| **IconButton** | **Required** | Already solid; align with OurNearIcon + focus tokens |
| **Spinner** | **Required** | Via registry / OurNearIcon alias of current `SpinnerIcon` |
| **Label** | **Optional thin wrapper** | Single implementation exists (`Admin/ui/Label`); re-export or alias only |
| **Input** | **Defer to Phase 2** | `controlBase` vs `adminControlClass` merge is medium risk; not needed to prove icons+button |
| **Text / Heading** | **Defer to Phase 2** | No dedicated components; typography lives in `classNames.typography` |
| **Badge** | **Defer to Phase 2** | Dual `StatusBadge` + domain string map—feature coupling |
| **Avatar** | **Defer to Phase 2** | Inline in layouts/pages, no shared atom file |
| **Divider** | **Defer to Phase 2** | No shared component in V2 codebase |

**Phase 1 primitive count (implementation):** 4 core (OurNearIcon, Button, IconButton, Spinner) + token foundation; Label optional 5th.

---

## 4. Non-Scope (explicit)

- DataGrid, Search System, Workflow, Notification redesign  
- Form System wrapper / `useForm` changes  
- EmptyState / Card consolidation (Phase 2)  
- Product Shell moves, sidebar refactors  
- Admin or ATS page migrations  
- Laravel, routes, migrations, policies, services  
- `@tabler/icons-react` required only at implementation time—not prerequisite for schema  
- Vitest/Storybook setup (Phase 9 registry host)  
- Mass replacement of imports across 203 TSX files  

---

## 5. Current Code References

### Styling / tokens

| File | Role |
|------|------|
| `resources/css/app.css` | Tailwind v4 `@theme`: brand, accent, canvas, surface, line, ink, muted, disabled, success, warning, danger, radii, shadows, Instrument Sans |
| `resources/js/design-system/classNames.ts` | `focusRing`, `controlBase`, `controlError`, label/helper/error text, `cardDensity`, `typography`, `statusTone` |
| `vite.config.js` | `@tailwindcss/vite`; alias `@` → `resources/js` |
| `tsconfig.json` | Strict TS; paths `@/*` |

No separate `tailwind.config.js` (Tailwind 4 CSS-first).

### UI primitives (actual paths)

| Primitive | Path | Notes |
|-----------|------|-------|
| Button (public) | `resources/js/Components/Public/Button.tsx` | Link + button; variants incl. primary/accent/secondary/brand; default variant `accent`; loading + SpinnerIcon |
| Button (admin) | `resources/js/Components/Admin/ui/Button.tsx` | Button only; variants primary/secondary/ghost/danger; primary = `bg-brand` |
| IconButton | `resources/js/Components/UI/IconButton.tsx` | Requires `label`; Link/button; `focusRing` |
| Input (admin) | `resources/js/Components/Admin/ui/Input.tsx` | Exports `adminControlClass`; date picker helper |
| Label (admin) | `resources/js/Components/Admin/ui/Label.tsx` | Matches `labelBase` intent |
| Badge | `resources/js/Components/UI/StatusBadge.tsx`, `resources/js/Components/Admin/ui/StatusBadge.tsx` | Tone vs status string map |
| Spinner | `resources/js/Components/UI/icons.tsx` → `SpinnerIcon` | Used by Public Button |
| Text / Heading | — | Ad hoc Tailwind + `typography.*` in `classNames.ts` |
| Avatar | — | Inline in `Layouts/AccountLayout.tsx`, `Components/Public/Header.tsx`, etc. |
| Divider | — | No shared component |

### Icons

| File | Role |
|------|------|
| `resources/js/Components/UI/icons.tsx` | Inline SVG exports: BellIcon, CloseIcon, MenuIcon, HomeIcon, BriefcaseIcon, ClipboardIcon, UserCircleIcon, MoreHorizontalIcon, SpinnerIcon |
| `resources/js/Components/Public/SocialIcons.tsx` | Social SVGs (out of Phase 1 registry except document) |
| `resources/js/Components/BrandMark.tsx` | Logo wordmark—not Tabler |

No npm icon package in `package.json` today.

### Relevant duplicates (Phase 1 touches Button only)

- Public vs Admin **Button** (different defaults and APIs: href vs button-only)  
- Card, EmptyState, StatusBadge, form controls — **out of Phase 1 scope**

### Cross-import note (validation only)

Some admin pages import `Components/Public/Button` (e.g. `Pages/Admin/Dashboard.tsx`, `Pages/Admin/Hiring/CommandCenter.tsx`)—compatibility layer must preserve this import path and behavior.

---

## 6. Exact File Mapping

| Area | Existing file | Current role | Phase 1 action | Risk |
|------|---------------|--------------|----------------|------|
| Global tokens | `resources/css/app.css` | @theme primitives | **Preserve**; add comment block / optional `--our-*` aliases mirroring existing values | Low |
| Component tokens | `resources/js/design-system/classNames.ts` | Control/typography/status | **Extend** with exported token map doc; no visual change | Low |
| Token entry | (none) | — | **Add** `resources/js/foundation/tokens.ts` (or `design-system/tokens.ts`) re-exporting names only | Low |
| Icon registry | (none) | — | **Add** `resources/js/icons/registry.ts` (+ optional `registry.json`) | Medium |
| OurNearIcon | (none) | — | **Add** `resources/js/icons/OurNearIcon.tsx` | Medium |
| Legacy icons | `resources/js/Components/UI/icons.tsx` | SVG components | **Keep**; re-export via OurNearIcon or thin wrappers calling registry | Medium |
| Button canonical | (new internal) | — | **Add** e.g. `resources/js/components/atoms/Button.tsx` (path per future convention) OR implement inside `design-system` first | Medium |
| Public Button | `Components/Public/Button.tsx` | Public API | **Compatibility re-export** → canonical Button `surface="public"` | Medium |
| Admin Button | `Components/Admin/ui/Button.tsx` | Admin API | **Compatibility re-export** → canonical Button `surface="admin"` | Medium |
| IconButton | `Components/UI/IconButton.tsx` | Icon actions | **Refactor internals** to use OurNearIcon when child is registry icon; keep props API | Low–Med |
| Label | `Components/Admin/ui/Label.tsx` | Form labels | **Optional:** re-export from foundation; no delete | Low |
| Input | `Components/Admin/ui/Input.tsx` | Admin input | **No change** Phase 1 | — |
| Package | `package.json` | deps | **Phase 1 PR2 optional:** add `@tabler/icons-react` if wrapper uses Tabler for new icons only | Low |
| Docs | `docs/` | — | **Add** Phase 1 completion notes to library spec appendix or `docs/12-FRONTEND-ARCHITECTURE.md` snippet | Low |

*Exact new folder names (`foundation/` vs `design-system/`) to match Library Spec target—**introduce minimally** in Phase 1 (one new subtree under `resources/js/icons/` required; `foundation/` optional if `design-system/` extended instead).*

---

## 7. Protected Files / Directories

Phase 1 implementation **must not modify**:

```text
app/                          # All domain logic, HTTP, middleware, services, models, policies
routes/                       # web.php, account.php, admin.php
database/                     # migrations, seeders
config/                       # except no config changes at all in Phase 1
bootstrap/
tests/                        # unless adding dedicated frontend tests later
resources/views/              # Blade root
.env / .env.example
phpunit.xml
composer.json / composer.lock
```

**Within `resources/js`, avoid in Phase 1:**

- `Pages/Account/**`, `Pages/Admin/**` (except optional **one** low-risk public page—see §14)  
- `Components/Admin/Sidebar.tsx`, `JobForm.tsx`, recruitment components  
- `Layouts/*` (shell migration is later phase)  
- `types/*` except optional icon name union type  

**Allowed touch zones:**

- `resources/css/app.css` (comments + alias vars only if zero visual change)  
- `resources/js/design-system/**`  
- `resources/js/icons/**` (new)  
- `resources/js/Components/Public/Button.tsx` (thin alias)  
- `resources/js/Components/Admin/ui/Button.tsx` (thin alias)  
- `resources/js/Components/UI/IconButton.tsx`, `icons.tsx` (compatibility)  
- Optional: `resources/js/Pages/About.tsx` (validation only—see §14)  

---

## 8. Token Migration Plan

### Options evaluated

| Option | Description | Verdict |
|--------|-------------|---------|
| **A** | Replace all Tailwind theme names immediately | **Reject** — high regression risk across 203 TSX files |
| **B** | Canonical tokens + keep existing `@theme` names as aliases | **Accept** — best balance |
| **C** | Compatibility layer + incremental migration | **Accept** — same as B for Phase 1 |

### Recommended: **Option B + C (incremental compatibility-first)**

```text
Global Tokens     →  app.css @theme (unchanged keys: brand, accent, canvas, …)
       ↓
Semantic Tokens   →  classNames.ts + documented mapping to Tailwind utilities
       ↓
Component Tokens  →  buttonVariants[surface][variant], controlBase, focusRing (Phase 1: button + focus only)
```

**Mapping (existing → canonical name):**

| Existing | Tier | Canonical documentation name |
|----------|------|------------------------------|
| `--color-brand` | Global | `color.brand` #214449 |
| `--color-accent` | Global | `color.accent` #F15A24 |
| `--color-canvas` … `danger` | Global | semantic palette |
| `bg-canvas`, `text-ink`, etc. | Semantic | use as-is |
| `focusRing` | Component | `focus.default` |
| `controlBase` / `adminControlClass` | Component | `control.input` (public vs admin density)—merge Phase 2 |
| `typography.*` | Semantic | `type.*` |
| `statusTone.*` | Component | `badge.tone.*` |

**Rule:** No new hex values in Phase 1 unless identical alias for documentation.

---

## 9. Button Decision

**PRODUCT DECISION REQUIRED — do not implement without sign-off.**

Brand values (approved):

- Primary brand: `#214449`  
- Accent: `#F15A24`  
- White: `#FFFFFF`  

### Option A — Single global primary = Brand `#214449`

| Aspect | Assessment |
|--------|------------|
| Visual role | Consistent “OurNear green” on all CTAs |
| Brand | Strong corporate green |
| Accessibility | Green/white generally OK; verify accent CTAs converted |
| Links/secondary | Accent becomes secondary highlight only |
| Migration impact | **High** — changes all public marketing CTAs currently accent-orange |

### Option B — Single global primary = Accent `#F15A24`

| Aspect | Assessment |
|--------|------------|
| Visual role | High-energy CTAs everywhere |
| Brand | Matches current **Public** default (`variant='accent'`) |
| Accessibility | Orange/white needs contrast check on small text |
| Admin | **Breaks** current admin primary (`bg-brand`) on many staff actions |
| Migration impact | **High** on admin surfaces |

### Option C — Surface-based primary (Library Spec §28)

| Surface | `primary` maps to | Matches V2 today |
|---------|-------------------|------------------|
| `public` / `account` | **Accent** #F15A24 | Public Button default |
| `admin` / `corporate` | **Brand** #214449 | Admin Button primary |

| Aspect | Assessment |
|--------|------------|
| Visual role | Marketing vs operations distinction preserved |
| Brand | Both colors remain first-class **product tokens** |
| Accessibility | No global swap; audit per surface |
| Migration impact | **Low** — compatibility aliases preserve defaults |

### **RECOMMENDATION — PRODUCT SIGN-OFF REQUIRED**

**Option C (surface-based primary)** — grounded in existing code: Public `Button` defaults to accent; Admin `Button` defaults to brand. Phase 1 canonical Button accepts `surface: 'public' | 'account' | 'admin' | 'corporate'` with defaults above.

**Blocking for Button merge:** Explicit product approval of Option C (or A/B if product chooses single global primary).

---

## 10. Icon Registry Plan

### Phase 1 registry entry (minimal schema)

```typescript
type IconRegistryEntry = {
  name: string;              // canonical kebab-case, e.g. "bell"
  source: 'inline' | 'tabler'; // Phase 1: mostly 'inline'
  tablerName?: string;       // e.g. "IconBell" when source tabler
  category: IconCategory;
  aliases?: string[];        // e.g. ["notifications"]
  defaultSize: 'sm' | 'md' | 'lg';
  stroke?: number;           // default 2 for inline
  decorativeByDefault?: boolean;
  status: 'active' | 'deprecated';
};
```

### Phase 1 population

Seed registry from **existing** `icons.tsx` exports only (~9 icons). Social icons remain in `SocialIcons.tsx` until Phase 2.

### Tabler

- Phase 1: **schema supports** `source: 'tabler'`; implementation may map one pilot icon via `@tabler/icons-react` to prove pipeline—or defer all Tabler to Phase 1b **same PR** if dependency approved.  
- **Rule:** Pages import `OurNearIcon` only; wrapper internally resolves registry → inline component or Tabler.

### Missing / invalid icon

- **Development:** console.warn + render empty `<span aria-hidden />` or fallback `IconHelp` (once in registry)  
- **Production:** same fallback; never throw during render  

### Sprawl prevention

- ESLint rule (Phase 1 or 2): ban `@tabler/icons-react` imports outside `resources/js/icons/`  
- Ban new raw `<svg` in `Pages/` (warn initially)

---

## 11. OurNearIcon Contract

```typescript
type OurNearIconProps = {
  name: string;                    // registry key or alias
  size?: 'xs' | 'sm' | 'md' | 'lg'; // 16|20|24|32px
  stroke?: number;
  className?: string;
  /** Required unless decorative */
  'aria-label'?: string;
  decorative?: boolean;            // sets aria-hidden, no label required
};
```

**Behavior:**

| Case | Behavior |
|------|----------|
| Missing icon | Fallback glyph + dev warn |
| Invalid name | Same as missing |
| Decorative | `aria-hidden={true}`, ignore label |
| In IconButton | Icon decorative; **button** has `aria-label` (unchanged) |
| In Button with leading icon | Icon `decorative={true}` if text present; else label on button |

**API size:** No children; no arbitrary SVG pass-through in Phase 1.

---

## 12. First Primitive Contracts (summary)

### Button (canonical)

- **Variants:** primary, secondary, outline, ghost, destructive, link (+ legacy aliases accent/brand/light mapped)  
- **States:** idle, hover, focus, active, disabled, loading (match Public Button)  
- **Sizes:** default only Phase 1 (`min-h-10`, `text-sm`); sm/lg Phase 2 if needed  
- **Behavior:** `href` (Inertia Link), `type`, `onClick`, loading spinner via OurNearIcon/Spinner  
- **Deps:** `focusRing`, token variants, OurNearIcon for loading  
- **Compat:** Public and Admin files become thin wrappers setting `surface`  
- **Tests:** render variants per surface; loading; disabled; link vs button  
- **Risk:** Medium — cross-surface visual regression  

### IconButton

- Keep **`label` required**  
- Optional `tooltip` Phase 2  
- **Compat:** same export path  
- **Risk:** Low  

### Spinner

- Registry name `spinner`; alias `SpinnerIcon` export preserved  
- **Risk:** Low  

### Label (optional)

- Align `Admin/ui/Label` with `labelBase` string (should match visually already)  
- **Risk:** Low  

---

## 13. Button Contract (detailed)

**Variants (required for V2 parity):**

| Variant | Public usage | Admin usage |
|---------|--------------|-------------|
| primary | accent fill | brand fill |
| secondary | brand fill | white/border secondary |
| outline | yes | via secondary pattern |
| ghost | yes | yes |
| destructive | yes (`destructive`) | yes (`danger` alias) |
| link | yes | rare |

**States:** loading uses `aria-busy` on `<button>` (Public pattern); Link uses `aria-disabled`.

**Not in Phase 1:** icon-leading/trailing props (unless zero-cost pass-through of children only).

---

## 14. Form Primitive Contract (Phase 1 = document only)

Input/Label **unchanged** in code. Document alignment:

- Label → `htmlFor`, `labelBase`  
- Input → server errors via Inertia `errors` on parent (no change)  
- Admin Input keeps `showPicker` behavior  

Phase 2 merges `controlBase` and `adminControlClass`.

---

## 15. IconButton Contract

- Icon-only; **`label` mandatory** (already enforced)  
- Size: 40×40 (`h-10 w-10`) — keep  
- Variants: single style Phase 1 (bordered white)  
- Loading state: optional Phase 2  
- Focus: `focusRing`  

---

## 16. Compatibility Strategy

```text
Existing import paths (Public/Button, Admin/ui/Button, UI/icons, UI/IconButton)
        ↓
Thin re-export OR delegate to canonical implementation
        ↓
Canonical: foundation + icons/OurNearIcon + atoms/Button
```

- **No** mass codemod in Phase 1  
- **Default props** on wrappers preserve today’s visual defaults  
- Admin pages that import Public Button continue to work  

---

## 17. Migration Strategy

```text
1. Freeze scope + Button decision (Option C sign-off)
2. Token ownership doc + optional tokens.ts (no visual change)
3. Icon registry + OurNearIcon (inline icons only)
4. Canonical Button (internal)
5. Wire Public/Button + Admin/ui/Button as aliases
6. IconButton → OurNearIcon for spinner/bell/menu as pilot
7. Controlled validation surface (optional import path test on About.tsx only if needed)
8. tsc + vite build + manual smoke (About, Admin login page button if admin wrapper touched)
9. Review → Phase 2 approval
```

### Safest adoption surface for **optional** visual proof

**`resources/js/Pages/About.tsx`** — uses `Public/Button`; marketing-only; no ATS/auth.  

**Do not use:** Admin Jobs, Applications, Account Home, Apply flow.

Alternative: **no page changes**—validation purely via existing pages rendering unchanged (preferred if wrappers are 100% behavior-compatible).

---

## 18. Test Strategy

| Layer | Command / action | Acceptance |
|-------|------------------|------------|
| TypeScript | `npm run typecheck` | Zero errors |
| Build | `npm run build` | Success |
| PHPUnit | **Not required** for frontend-only Phase 1 unless `app.css` changed structurally | N/A |
| Manual UI | Public About (if touched); one admin page with Admin Button; header IconButton (notifications) | No visual diff vs baseline screenshots (informal) |
| Icon | Render OurNearIcon for each registry key | Icons visible |
| Button | primary/secondary/disabled/loading on public + admin surfaces | Matches pre-Phase 1 |
| a11y spot-check | IconButton notification bell; modal not in scope | Label present |

**Future (Phase 2):** Vitest smoke for Button + OurNearIcon.

---

## 19. Rollback Strategy

- Single revert of frontend commits (no DB/migrations/routes)  
- Compatibility files restore old implementations if canonical layer removed  
- Keep Phase 1 commits **isolated** to `resources/js/icons`, `design-system`, Button aliases, optional `app.css` comments  
- Tag release before merge recommended  

---

## 20. PR Boundary

### In Phase 1 (max 2 PRs — see §21)

- Token documentation / `tokens.ts` re-exports  
- `icons/registry` + `OurNearIcon`  
- Canonical Button + Public/Admin aliases  
- IconButton minor internal update  
- `icons.tsx` compatibility shims  
- Optional `@tabler/icons-react` + one pilot Tabler mapping  
- Docs update for Phase 1 completion  
- Optional About.tsx only if validation requires  

### NOT in Phase 1

- EmptyState, Card, Input merge, Badge, Avatar, Divider  
- DataTable, filters, shells, sidebar  
- Any `app/**`, `routes/**`, `database/**`  
- Employer/candidate/admin feature pages (except optional About)  
- API, jobs, events, queues  

---

## 21. Registry Host Decision

| Host | Phase | Notes |
|------|-------|-------|
| Markdown in `docs/` | Phase 1 | Registry schema documented in library spec + generated table in docs |
| `registry.ts` in repo | Phase 1 | **Source of truth for code** |
| Storybook / DocumentationShell | Phase 9 | Per library spec—**does not block Phase 1** |

**Recommendation:** Decide Storybook vs internal docs site in **Phase 8–9**; Phase 1 uses **repo registry file + short docs appendix**.

---

## 22. Phase 1 Acceptance Gate

Phase 1 is **complete** when all are true:

- [ ] Token ownership documented; V2 `@theme` keys unchanged visually  
- [ ] Icon registry schema merged; all legacy `icons.tsx` icons registered  
- [ ] `OurNearIcon` contract stable and used by IconButton and Button loading state  
- [ ] Canonical Button exists; Public/Admin paths are compatibility aliases  
- [ ] Product sign-off recorded for Button **Option C** (or chosen alternative)  
- [ ] No protected paths modified  
- [ ] `npm run typecheck` and `npm run build` pass  
- [ ] Manual smoke: public header + About (if used) + one admin button  
- [ ] No business/domain behavior change  
- [ ] Rollback documented; revert tested on branch  
- [ ] `docs/` updated with Phase 1 “implemented” pointer (post-implementation)  

---

## 23. Risk Register

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| V2 visual regression on CTAs | Medium | High | Surface-based primary; aliases; About-only optional proof |
| Token/CSS conflict | Low | Medium | No rename of @theme keys Phase 1 |
| Duplicate Button implementations remain | Medium | Low | Aliases must delegate to single canonical file |
| Icon migration sprawl | Medium | Medium | ESLint ban later; registry-only Tabler |
| Button branding inconsistency | Medium | Medium | Product sign-off Option C |
| Scope creep (EmptyState, Input) | Medium | High | Strict PR boundary §20 |
| CSS regressions print/resume | Low | High | Do not touch print rules in app.css |
| a11y regression IconButton | Low | Medium | Keep required label |
| Accidental Laravel change | Low | Critical | Protected list + review |
| `@tabler/icons-react` bundle size | Low | Low | Tree-shake; few icons Phase 1 |

---

## 24. Implementation Order (do not execute in planning task)

1. Freeze Phase 1 scope (this document approved)  
2. **Resolve Button decision** (Option C recommended) — **blocking**  
3. Define token ownership (`tokens.ts` + doc comments in `app.css`)  
4. Add `resources/js/icons/registry.ts` + types  
5. Implement `OurNearIcon` (inline sources only first)  
6. Implement canonical `Button` with `surface` prop  
7. Convert `Public/Button.tsx` and `Admin/ui/Button.tsx` to re-exports  
8. Update `IconButton` / loading spinner to use registry  
9. Run typecheck + build + manual smoke  
10. PR review (max 2 PRs)  
11. Update docs  
12. Approve Phase 2 (EmptyState, Card, Input, Label/FormField)  

---

## 25. Final Recommendation

- Proceed with **minimal Phase 1**: tokens (documentary + re-exports), **registry + OurNearIcon**, **unified Button + IconButton compatibility**, **Spinner via registry**.  
- **Defer** Input, Text, Heading, Badge, Avatar, Divider to Phase 2.  
- Use **Option C** surface primary mapping pending product sign-off.  
- Prefer **zero page changes** if aliases prove behavior-identical; optional About validation only.  
- Split implementation into **2 PRs** (see below).

---

## 26. Implementation PR Split (recommended)

| PR | Contents | Est. risk |
|----|----------|-----------|
| **PR-1** | Token docs/`tokens.ts`; `icons/registry.ts`; `OurNearIcon`; `icons.tsx` shims; optional Tabler dep + 1 icon | Low–Medium |
| **PR-2** | Canonical Button; Public/Admin aliases; IconButton spinner/icon wiring; typecheck/build verification | Medium |

**Total recommended implementation PRs: 2**

(PRs are planning targets—not opened by this document.)

---

## 27. Phase 1 Readiness for Implementation

| Item | Status |
|------|--------|
| Plan complete | Yes |
| Scope bounded | Yes |
| **Button decision** | **BLOCKING until product sign-off** (Option C recommended) |
| Tabler dependency | Non-blocking; decide in PR-1 implementation |
| Registry host | Non-blocking (Phase 9) |

**Ready for implementation:** **Yes, after Button surface decision is signed off.**

---

*End of OURNEAR CONTROLLED PHASE 1 IMPLEMENTATION PLAN v1.0*
