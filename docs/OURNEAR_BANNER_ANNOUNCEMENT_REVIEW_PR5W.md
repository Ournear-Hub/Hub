# OURNEAR Banner / announcement / callout architecture review — PR-5W

**Status:** Review complete — **no Banner / Announcement / Callout components** (architecture phase — **V2 not modified**)

## 1. Decision

**ALERT REMAINS SUFFICIENT — NO BANNER COMPONENT**

Frozen **Alert** (PR-4D) covers V2 **inline and page-level informational / status surfaces**. **Toast** (PR-4E) covers **transient** feedback (PR-5V). V2 does **not** prove a **materially distinct**, repeated **Banner**, **Announcement**, or **Callout** contract that warrants a second Alert-like primitive.

Domain **onboarding strips**, **dashboard “next action”** blocks, **marketing CTAs**, and **profile header “banner” layout** are **product/layout patterns** — documented for boundary clarity, **not** canonicalized.

## 2. Architectural classification

| Surface type | V2 evidence | DS mapping |
|--------------|-------------|------------|
| Success/error/warning after actions | Flash + ~30 inline tone boxes | **Alert** (adoption from ad-hoc markup) |
| Admin layout flash | **FlashMessage** | **Alert** semantics |
| Alert + inline CTAs | **UI/Alert** + buttons (apply confirm, profile continue, resume ready) | **Alert** children + **Button** |
| Dismissible notice | **molecules/Alert** + **Toast** (UI Lab / frozen only in V2 prod) | **Alert** / **Toast** |
| Accent “setup / next action” sections | **HiringActivationStrip**, **VerificationCenter**, **Account/Home** | **Domain** card/section — not site Banner |
| Full-width marketing CTA | **CtaSection** (`bg-brand py-14`) | **Marketing** — excluded |
| Profile cover layout | **ProfileIdentityHeader** `variant="banner"` | **Layout** (large header), not informational Banner |
| Persistent notification inbox | Admin/account notifications (PR-5V) | **Domain** |
| Sticky site-wide announcement bar | **None** | N/A |

## 3. Evidence inspected (read-only)

**Frontend:** `D:\Ournear Hub\Ournear Hire\resources\js` — `rg` for component names and patterns; read key files.

**Inspected:** `Components/molecules/Alert.tsx`, `Components/UI/Alert.tsx`, `Components/Admin/FlashMessage.tsx`, `Components/Profile/ProfileIdentityHeader.tsx`, `ProfileShell.tsx`, `HiringActivationStrip.tsx`, `VerificationCenter.tsx`, `ProfileModuleContinueBar.tsx`, `ResumeReadinessPanel.tsx`, `Public/CtaSection.tsx`, `Pages/Account/Home.tsx`, `Pages/Admin/Profile/Password.tsx`, `ui-lab/sections/AlertLabSection.tsx`, `ui-lab/sections/AspectRatioLabSection.tsx`, `package.json`.

**Backend:** Flash shared props (reference only, per PR-5V) — no separate “announcement” API surveyed as DS input.

| Search term (component names) | DS / shared component hits |
|------------------------------|----------------------------|
| `Banner`, `Announcement`, `Callout`, `NoticeBar`, `SiteBanner`, `PromoBanner`, etc. | **0** reusable components |
| `headerVariant="banner"` / `variant="banner"` | **Profile** layout only |
| UI Lab “Banners” | **Aspect ratio** doc (hero **images**), not notice UI |

| Pattern | Approx. files |
|---------|----------------|
| Inline success/error/warning boxes (border + tone bg) | **~30** TSX |
| `border-2 border-accent/30 bg-accent/5` callout-like sections | **~12** (mixed domain) |
| `sticky` / `fixed` top surfaces | **3** files — **nav/editor chrome**, not announcement bars |
| `localStorage` / `sessionStorage` for dismissal | **0** banner dismissal; **1** sidebar scroll position only |

## 4. Existing Alert comparison

**Canonical Alert (`molecules/Alert`):**

- Flex row: optional **icon**, **title**, **message**, optional **dismiss** (`IconButton` “Dismiss”).
- Variants: info | success | warning | error; **error → role="alert"**, others → **role="status"**.
- **Dismissible** + caller **`onDismiss`** (DEV warning if missing).
- **Production V2 consumers:** **0** direct imports — **UI Lab only**; V2 uses legacy **UI/Alert** (6 files) and ad-hoc `<p>` / `<section>` markup.

**Legacy UI/Alert:**

- Simpler: `tone`, `title`, children; always **`role="status"`** (even error tone).
- Used with **embedded CTAs** (`ProfileModuleContinueBar`, `ApplicationConfirmation`) — demonstrates that **actions inside Alert** are already a V2 pattern; no separate **Callout** needed.

**FlashMessage vs Alert:**

- Same information architecture: variant-colored bordered block, full width of content column, **not dismissible**, clears on navigation.
- Difference is **styling adapter** and **missing icon/title API** — **not** a distinct Banner lifecycle (PR-5V: adopt **Alert**, do not add FlashBanner).

**Material differences searched (Banner justification):**

| Dimension | Banner hypothesis | V2 finding |
|-----------|-------------------|------------|
| Full viewport width strip | Site/system bar | **No** layout-level strip; width follows **PageContainer** / admin content |
| Sticky/fixed lifecycle | Persistent until dismissed | **No** announcement stickies; sticky used for **Header** / job pane chrome |
| Site-wide broadcast | All pages | **No** shared announcement prop or component |
| Distinct semantics | `region` / landmark banner | **No**; notices use **alert/status** or plain sections |
| Browser-persisted dismiss | “Don’t show again” | **None** |

**Conclusion:** Repeated “notice” UI is either **Alert-equivalent** (tone + message [+ title] [+ dismiss] [+ actions]) or **domain section** (card + heading + CTA list) — not a third DS primitive.

## 5. Banner findings

**No** `Banner` component or stable **Banner** import path.

**False positives:**

- **ProfileIdentityHeader / ProfileShell `variant="banner"`** — larger avatar/header **layout**, unrelated to system messages.
- **AspectRatioLabSection** — documents marketing **hero image** heights as “Banners”.
- **CtaSection** — full-width **brand marketing** band with title, description, single CTA — **promotional section**, explicitly out of DS Banner scope.

**No** MaintenanceBanner, SiteBanner, CampaignBanner, or AlertBanner components.

## 6. Announcement findings

**No** `Announcement` UI component or repeated **broadcast announcement** surface.

- Copy like “system notices” on **Admin/Users/Show** refers to **notification records** (PR-5V domain), not a page announcement strip.
- **No** maintenance mode, policy rollout, or global “important notice” layout shared across apps.
- **Account “Notification center”** is **inbox list** (PR-5V), not an Announcement molecule.

## 7. Callout findings

**No** `Callout` component name in codebase.

**Callout-like visuals** (accent border, tinted bg, heading + body + actions):

| Location | Structure | Classification |
|----------|-----------|----------------|
| **HiringActivationStrip** | `<section>` + label + text + **Button** / **Link** | Employer hiring **gate** — single domain widget |
| **VerificationCenter** hiring block | `<section>` + checklist + **Button** | Same domain |
| **Account/Home** “Next action” | `<section>` + eyebrow + **h2** + CTA | **Dashboard** guidance — candidate-specific |
| **Admin/Profile/Password** `must_change` | Warning-styled `<p>` | Maps to **Alert** `warning` |

These share **visual language** (rounded-xl, accent tint) but **different content models** (checklists, dynamic next-action copy, gates). **Not** one reusable Callout API evidenced ≥2 identical contracts.

## 8. Dismissal findings

| Mechanism | Where |
|-----------|--------|
| **Alert `dismissible` + `onDismiss`** | Frozen Alert, UI Lab |
| **Toast dismiss / auto-dismiss** | Frozen Toast, UI Lab |
| **localStorage / cookie “don’t show again”** | **None** for notices |
| **sessionStorage** | **Admin Sidebar** nav **scroll position** only |

V2 flash and inline banners are **not dismissible** — session flash clears on next request/navigation. **No** global dismissal service to document or build.

## 9. Action / CTA findings

Notices with **primary/secondary actions**:

- **Inside UI/Alert:** profile module continue, application confirmation — **Alert + Button** composition.
- **Domain sections:** hiring activation, next action, resume readiness (incomplete) — **section + Button/Link** or **Card**-like layout.
- **JobForm / CMS** helper text in bordered boxes — **inline help**, not Callout DS.

Actions are **domain-specific** (verify, finish setup, complete profile, apply). No generic **“notice with action slot”** molecule beyond **Alert children** + layout utilities.

## 10. Accessibility findings

- **Canonical Alert / Toast:** variant-based **alert** vs **status** roles (UI Lab documented).
- **UI/Alert:** always **status** (legacy gap for error assertiveness).
- **FlashMessage / flash `<p>`:** often **unlabeled** blocks; some account flash uses **`role="status"`**.
- **Domain sections:** **`aria-labelledby`** on next-action and hiring strips; **VerificationCenter** hiring block uses **`aria-live="polite"`** for dynamic gate copy.
- **No** separate live-region model required for Banner — extend **Alert** placement/className if full-width polite status needed.

## 11. Keyboard findings

- **Alert dismiss:** **IconButton** focusable.
- **Domain sections:** native **button/link** tab order; no roving tabindex on “callout” lists.
- **No** Escape-to-dismiss page banners (none exist).

## 12. Responsive findings

- Inline alerts/flash: **full width of parent** column; text wraps naturally.
- **CtaSection:** stacks title and CTA on small screens (`flex-col` → `sm:flex-row`).
- **Hiring / next-action sections:** `flex-wrap` on CTAs, `min-h-11` touch targets.
- **No** fixed top announcement consuming viewport on mobile.

## 13. State ownership findings

| Content | Owner |
|---------|--------|
| Flash / inline success | **Server session** → Inertia **flash** |
| Alert/Toast visibility | **Caller** (when used) |
| Hiring gate / next action copy | **Server props** on page |
| Profile header variant | **Page** props |
| Dismissed Alert (lab) | **Caller** local state |

**No** BannerProvider, announcement queue, or browser-persisted visibility store.

## 14. Domain-vs-design-system classification

| Item | DS? |
|------|-----|
| Form/page feedback, warnings, success | **Alert** (Toast if transient client-only) |
| FlashMessage | Adopt **Alert** — not FlashBanner |
| Marketing **CtaSection**, CMS heroes | **Product/marketing** |
| Profile **banner** header layout | **Profile shell** |
| Hiring activation / verification gates | **Account/hiring domain** |
| Dashboard next action | **Account home domain** |
| Resume readiness (incomplete) | **Resume domain** (Card/section; success branch uses UI/Alert) |
| Admin must-change password notice | **Alert**-class warning inline |

## 15. Relationship to frozen Alert

- **Do not** add Banner as Alert with different border radius — use **`className`** / layout wrapper when full-width or top-of-page placement is needed.
- **Title + body + icon + dismiss + actions** = **Alert** contract; UI Lab shows form-context and long message cases.
- V2 adoption path: **FlashMessage**, tone `<p>` blocks, and **UI/Alert** → **molecules/Alert** (align error **role="alert"**).
- **ResumeReadinessPanel** incomplete state is intentionally a **section** with list links — product UX; optional future refactor to **Alert** + list only if semantics fit, not a new Callout type.

## 16. Relationship to frozen Toast

- **Toast** remains for **portaled transient** feedback (PR-5V).
- **No** sticky bottom **Snackbar** strip evidenced; would duplicate Toast if needed later.
- Site-wide **ephemeral** broadcast without navigation → **Toast** at layout caller, still **caller-owned** — not Banner.

## 17. Candidate patterns

| Pattern | Recommendation |
|---------|----------------|
| Flash / colored `<p>` at top of page | **Alert** |
| Warning with required action (password change) | **Alert** variant warning |
| Success + next step buttons | **Alert** + **Button** / **LinkButton** |
| Client-only “Saved” overlay | **Toast** |
| Employer hiring gate | Keep **HiringActivationStrip** (domain) |
| Candidate next action | Keep **Home** section (domain) |
| Marketing full-width CTA | Keep **CtaSection** (marketing) |
| Profile hero layout | Keep **ProfileIdentityHeader** variants |

## 18. Why created / rejected

**Rejected — Banner, Announcement, Callout, NoticeBar, global dismissal:**

- **Zero** named components; **no** site-wide sticky strip; **no** persisted dismiss.
- Callout-like styling appears in **few domain-specific sections** with different structures.
- **CTA + message** already composed with **Alert** in V2.
- Creating Banner would **duplicate Alert** visually/semantically per PR-5W rules.

**Rejected — modifying Alert/Toast:** frozen per stop conditions.

**Accepted — documentation only:** clarifies Alert sufficiency vs domain/marketing surfaces.

## 19. Final API contract (if created)

**N/A** — no new components.

## 20. Semantic contract (if created)

**N/A** for Banner/Callout.

**Informational/status (DS):** use **Alert** in document flow; optional **`className="w-full"`** or layout placement at top of main — still **alert/status** roles, not a new landmark type unless product requires **`role="region"`** with **`aria-label`** (not evidenced as repeated pattern).

## 21. Accessibility contract (if created)

**N/A** for new components. Prefer **molecules/Alert** role rules over legacy UI/Alert for errors.

## 22. Keyboard contract (if created)

**N/A** for new components.

## 23. Interaction contract (if created)

**N/A** for new components.

## 24. State ownership contract

Feedback: **server flash** or **caller** (Alert/Toast). Domain gates: **server-driven** page props. **No** DS banner visibility store.

## 25. Responsive contract

Use existing **Alert** layout (flex, wrap) and page containers; domain sections already use responsive padding and CTA wrap — no new DS recipes.

## 26. UI Lab route (if created)

**N/A** — no changes. **`molecules-alert`** unchanged.

## 27. Documentation path

`docs/OURNEAR_BANNER_ANNOUNCEMENT_REVIEW_PR5W.md`

## 28. Files created

- `D:\Ournear Hub\Ournear Hire\docs\OURNEAR_BANNER_ANNOUNCEMENT_REVIEW_PR5W.md`

## 29. Files modified

**None**

## 30. V2 inspection status

**Complete (read-only)**

## 31. V2 modification status

**None** — frozen **Alert** and **Toast** untouched.

## 32. Dependency changes

**None**

## 33. TypeScript result

**Not run** (documentation-only — no implementation changed)

## 34. Build result

**Not run** (documentation-only — no implementation changed)

## 35. Deviations

None.

## 36. V2 migration / adoption status

**Not started.** Recommended (documented only):

- Map **FlashMessage** and inline flash/tone paragraphs → **molecules/Alert**.
- Map **UI/Alert** → **molecules/Alert** where inline status is intended.
- Keep **HiringActivationStrip**, **next action**, **CtaSection**, profile **banner** header as product/layout code unless a **new** repeated generic contract appears.

**Do not** introduce **Banner** merely for top-of-page placement.

## 37. Stop condition

**PR-5W complete. Stop. Do not start PR-5X. Do not begin V2 adoption/migration.**
