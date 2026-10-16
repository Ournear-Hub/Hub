# OURNEAR Dialog review — PR-5H

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Decision

**Dialog was created** as a canonical **molecule** (`Components/molecules/Dialog.tsx`).

Semantic name **`Dialog`** (not `Modal`). V2 **`Components/Admin/ui/Modal.tsx`**, **`ConfirmDialog.tsx`**, and **`Drawer.tsx`** remain unchanged.

**Drawer** and **ConfirmDialog** were **not** canonicalized as separate molecules.

## 2. Architectural classification

| Pattern | Classification |
|---------|----------------|
| **Admin Modal** | **TRUE centered modal shell** — stable `open` / `title` / `onClose` / `children` / `footer` |
| **ConfirmDialog** | **Use case** — wraps Modal + async confirm busy state; caller-owned workflow |
| **Drawer** | **Related but distinct** — slide-over entity workspace (loading, sizes, scroll body); dominant on admin index pages |
| **Popover** (DS) | **Non-modal** contextual floating panel — already frozen |
| **PublicMobileMenu / CandidateMobileNav “More”** | **Page-local** navigation sheets — dialog roles for a11y, not shared admin modal contract |
| **ProfileShareMenu** | Inline overlay pattern — not admin Modal |
| **Toast** (DS) | Temporary portal feedback — not dialog |
| **Alert** (DS) | Inline feedback — not dialog |
| **Card** (DS) | Visual surface — not dialog behavior |
| **Full-page routes** | Inertia pages — out of scope |

## 3. Evidence inspected (read-only)

| Source | Finding |
|--------|---------|
| `Components/Admin/ui/Modal.tsx` | Single shared centered modal: `fixed inset-0 z-[70]`, backdrop `bg-ink/40`, `role="dialog"` `aria-modal="true"`, Escape, backdrop click, header title + Close, optional footer. **Static** `aria-labelledby="admin-modal-title"` (duplicate-id risk when stacked). **No** focus trap, **no** portal, **no** body scroll lock. |
| `Components/Admin/ui/ConfirmDialog.tsx` | Wraps Modal; default labels; `danger` variant; internal `busy` around `onConfirm`. |
| `Components/Admin/ui/Drawer.tsx` | Slide-over `aside` dialog; `z-[60]`; `loading` prop; `size` md/xl; same Escape/backdrop pattern. |
| `Components/Admin/ui/index.ts` | Exports Modal, Drawer, ConfirmDialog. |
| `<Modal` JSX | ~18 instances across ApplicationDrawer, Employees, Finance, Messages, Placements, Replacements, Staff, Submissions. |
| `<Drawer` JSX | ~24 admin index/workspace files (CRM, Jobs, Candidates, Finance, …). |
| `<ConfirmDialog` | ~9 files (Jobs, MasterData, Placements, Finance, ApplicationDrawer, ApplicationWorkspacePage, Staff, Submissions, Users/Show). |
| `PublicMobileMenu.tsx`, `CandidateMobileNav.tsx` | Local `role="dialog"` sheets; body overflow hidden on public menu only. |
| Headless UI / `@headlessui` | **Not found** in `resources/js` (PowerShell recursive search). |
| Ripgrep from workspace `References` | Often misses `Ournear Hire` paths or times out; **PowerShell `Get-ChildItem -Recurse` + `Select-String`** used for counts. |

## 4. Candidate modal/dialog patterns

See classification table (§2).

## 5. Repetition analysis

Admin **Modal** is one implementation reused for **form tasks** (replacement request, invoice create, messaging) and as the shell for **ConfirmDialog**. Contract is stable enough to canonicalize as **Dialog** without absorbing confirmation or form logic.

**Drawer** repetition is higher for **entity detail**, but layout/loading/size differ materially — deferred to a future review (PR-5H documents relationship only).

## 6. Semantic analysis

- **Dialog** = blocking modal task in centered panel (max-width md).
- **Not** a menu (DropdownMenu), **not** lightweight context (Popover), **not** transient status (Toast).

## 7. Accessibility analysis

Evidence-backed behavior mirrored from V2 Modal:

- `role="dialog"`, `aria-modal="true"`
- Named via **`aria-labelledby`** on title (`useId` per instance — DS improvement)
- Optional **`description`** → `aria-describedby`
- Optional **`aria-label`** when title is not usable as name
- Backdrop control + header Close (labeled)
- **No focus trap** (V2 does not trap; not invented in DS)

Not added (no V2 evidence): initial focus move, focus restoration, `inert` on background.

## 8. Keyboard analysis

- **Escape** → `onClose` (window listener while open)
- Tab order follows DOM; **no** roving tabindex / arrow-key menu behavior

## 9. Interaction analysis

**Dialog owns:** open gating, overlay shell, Escape, backdrop dismiss, dialog semantics.

**Caller owns:** `open` state, `title` / `description` / `children` / `footer`, forms, validation, API calls, routing, confirm busy state, danger styling of actions.

## 10. State ownership

Controlled **`open`** + **`onClose`** (matches V2 `Modal` naming). No uncontrolled convenience API in V2.

## 11. Visual analysis

Recipes align with V2 Modal: `bg-ink/40` backdrop, `rounded-xl border border-line bg-white p-5`, `max-w-md`, bottom-aligned on small viewports (`items-end` → `sm:items-center`). Uses **`dialog*`** entries in `classNames.ts` and DS **Button** (`surface="admin"`) for Close/actions in lab.

## 12. Relationship to Popover

Popover is **non-modal**, anchor-positioned, portal/fixed contextual content, outside-pointer and return-focus semantics, **no** `aria-modal`. Dialog is **centered blocking shell** for tasks/confirmations/forms. **Do not duplicate Popover as Dialog.**

## 13. Relationship to Drawer

Drawer shares overlay + Escape + dialog roles but is a **spatial variant** for large entity workspaces (full-height aside, scroll body, loading). Documented as **future candidate**; **not** created in PR-5H to avoid two overlapping modal primitives.

## 14. Relationship to Alert

Alert is **inline** persistent feedback. Dialog is **overlay** task surface.

## 15. Relationship to Toast

Toast is **temporary**, portal-fixed, caller-owned lifecycle. Dialog is **blocking** until dismissed.

## 16. Relationship to Card

Card is a **bounded surface** only. Dialog composes overlay + panel; may contain Card-like content at caller discretion.

## 17. Final API

```tsx
type DialogProps = {
  open: boolean;
  onClose: () => void;
  title: ReactNode;
  description?: ReactNode;
  children: ReactNode;
  footer?: ReactNode;
  'aria-label'?: string;
  className?: string;
};
```

(V2 `Modal` used `onClose` and `footer`; not renamed to `onOpenChange`/`actions` to ease future adoption mapping.)

## 18. UI Lab route

`/admin/design-system/molecules/dialog`

## 19. Files created

- `resources/js/Components/molecules/Dialog.tsx`
- `resources/js/ui-lab/sections/DialogLabSection.tsx`
- `docs/OURNEAR_DIALOG_REVIEW_PR5H.md`

## 20. Files modified

- `resources/js/design-system/classNames.ts` — `dialog*` recipes
- `resources/js/Components/molecules/index.ts`
- `resources/js/ui-lab/catalog.ts`
- `resources/js/ui-lab/metadata.ts`
- `resources/js/Pages/Admin/DesignSystem/Show.tsx`
- `app/Http/Controllers/Admin/DesignSystemController.php`
- `routes/admin.php`

## 21. Dependencies

**None** (no new npm packages)

## 22. TypeScript result

Run after PR-5H (`npm run typecheck`).

## 23. Build result

Run after PR-5H (`npm run build`).

## 24. Deviations from V2 (molecule only)

- Per-instance **`useId`** for title/description ids (fixes static id in V2 Modal/Drawer)
- Optional **`description`** prop with `aria-describedby`
- DS **Button** atom instead of `Admin/ui/Button`
- Optional **`aria-label`** override

V2 Modal, ConfirmDialog, Drawer, and all consumers **unchanged**.

## 25. V2 untouched confirmation

**Confirmed** — no V2 components, pages, routes, or callers modified.

## 26. Migration / adoption confirmation

**Not started.** Future adoption may map `Modal` → `@/Components/molecules/Dialog` where centered shell is intended. Drawer and ConfirmDialog remain V2 until explicit reviews/migrations.

## Explicit non-goals (PR-5H)

- Canonical Drawer or ConfirmDialog molecules
- Focus trap, portal, body scroll lock (without evidence)
- Migrating admin Modal/Drawer/ConfirmDialog callers
- Replacing mobile navigation sheets
