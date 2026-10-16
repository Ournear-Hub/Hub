# OURNEAR Drawer review — PR-5I

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Decision

**Drawer was created** as a canonical **molecule** (`Components/molecules/Drawer.tsx`).

**Not** implemented as a Dialog presentation variant. V2 **`Components/Admin/ui/Drawer.tsx`** remains unchanged.

## 2. Architectural classification

| Option | Outcome |
|--------|---------|
| **A. Canonical Drawer molecule** | **Selected** — full-height right workspace shell with distinct layout contract |
| **B. Dialog variant** | **Rejected** — centered `max-w-md` dialog cannot express full-viewport scroll workspace without overloading Dialog |
| **C. Page-only pattern** | **Rejected** — single V2 primitive with ~26 admin consumers |

**ApplicationDrawer** (`Components/Admin/ApplicationDrawer.tsx`) is **domain composition** (tabs, forms, API) rendered **inside** the Drawer shell — not a DS primitive.

## 3. Evidence inspected (read-only)

| Source | Finding |
|--------|---------|
| `Components/Admin/ui/Drawer.tsx` | Right-aligned `fixed inset-0 z-[60] flex justify-end`; backdrop `bg-ink/40`; `aside` `role="dialog"` `aria-modal="true"`; header + scroll body + optional footer; `size` `md` \| `xl`; optional `loading` (inline “Loading…”); `closeLabel` (defaults only). **No** CSS slide animation, **no** focus trap, **no** portal. Static `aria-labelledby="admin-drawer-title"`. |
| `<Drawer` JSX | **26** instances across **24** files (Finance ×2, Investors ×2). |
| `size="xl"` | **11+** entity workspaces (Candidates, Employers, Placements, Applications workspace, Messages, …). Omitted → default **md** (`sm:max-w-lg`). |
| `footer=` | **7** index flows (e.g. MasterData create/edit form actions). |
| `loading=` on Drawer | **0** consumer usages (prop exists in V2 only). |
| `closeLabel=` | **0** consumer usages (V2 default only). |
| `side` / left drawer | **None** — all V2 drawers use `justify-end` (right). |
| `translate-x` / offcanvas | **Not used** on admin Drawer. |
| `ApplicationWorkspacePage.tsx` | Drawer shell + `ApplicationDrawer` children + separate `ConfirmDialog`. |
| `PublicMobileMenu.tsx`, `CandidateMobileNav.tsx` | Navigation sheets (bottom/right); body scroll lock on public menu only — **not** admin Drawer contract. |
| Frozen **Dialog** (PR-5H) | Centered `z-[70]`, `max-w-md`, task/form modal — complementary stack layer. |

Search: PowerShell `Get-ChildItem -Recurse` + `Select-String` on `D:\Ournear Hub\Ournear Hire\resources\js`; ripgrep from workspace `References` unreliable.

## 4. V2 Drawer contract (stable)

Repeated caller pattern:

- `open` (boolean, usually `selected !== null`)
- `onClose` (clears selection / query param)
- `title` (entity id or label)
- `children` (tabs, tables, forms, domain components)
- optional `footer` (form submit rows)
- optional `size="xl"` for wide entity 360 views

Ephemeral / unused in consumers: `loading`, `closeLabel`.

## 5. Consumer analysis

- **Dominant:** Admin **index** pages — row click opens entity **workspace** in Drawer (Candidates, Employers, Jobs, CRM, Finance, …).
- **Heavy composition:** Tabs, Timeline, DataTable links, inline forms — all **caller-owned**.
- **Modals alongside:** Same pages use **Modal** / **ConfirmDialog** for short tasks while Drawer stays open pattern is avoided (separate state).
- **MasterData:** Drawer + `footer` + form `id` submit pattern.

## 6. Dialog comparison

| Aspect | Dialog (DS) | Drawer (DS / V2) |
|--------|-------------|-------------------|
| Purpose | Short blocking task / confirm / small form | Entity workspace / detail |
| Layout | Centered panel | Full-height right column |
| Width | `max-w-md` | `sm:max-w-lg` or `sm:max-w-3xl` |
| z-index | `70` | `60` |
| Body | Static panel padding | `flex-1` scroll region |
| Header/footer | Simple stack | Border-separated regions |

Shared: overlay, Escape, backdrop dismiss, `open` / `onClose`, dialog semantics, no focus trap in V2.

**Conclusion:** Shared mechanics do not justify merging into one component; spatial and scroll contracts differ materially.

## 7. Mobile sheet analysis

Account/public **navigation** sheets use local markup (`absolute` panel, optional `rounded-t-2xl` bottom sheet). They serve **nav**, not admin entity CRUD. **Do not** generalize as Drawer.

## 8. Semantic contract

Modal **workspace** surface: blocks background via overlay, presents extended content in viewport-height panel anchored to the **right** edge.

## 9. Accessibility contract

- `role="dialog"`, `aria-modal="true"` on panel (`aside`)
- Name via `aria-labelledby` (per-instance `useId` in DS)
- Optional `description` → `aria-describedby`
- Backdrop button + header Close (`aria-label="Close drawer"`)
- **No focus trap** (V2 parity; same as Dialog DS)

## 10. Keyboard contract

- **Escape** → `onClose`
- Tab / Shift+Tab follow document order; no roving tabindex

## 11. Interaction contract

**Drawer owns:** shell, scroll body region, Escape, backdrop close.

**Caller owns:** open state, title, content, footer actions, fetching, loading UI inside children, routing/query sync.

## 12. Visual contract

Recipes in `classNames.ts`: `drawerOverlay`, `drawerPanel`, `drawerPanelSizeXl`, `drawerHeader`, `drawerTitle`, `drawerBody`, `drawerFooter`. Tokens: `border-line`, `bg-white`, `bg-ink/40`, existing admin typography scale.

## 13. Responsive contract

- Mobile: panel is **full width** (`w-full`), still **right-aligned** stack (same as V2).
- `sm+`: max-width **lg** (md) or **3xl** (xl).
- **No** bottom-sheet transform for admin Drawer in V2 — not invented in DS.

## 14. State ownership

Caller-controlled **`open`** + **`onClose`**. **`loading`** excluded from DS API (zero V2 consumer usage; use Skeleton/spinner in `children`).

## 15. Final API

```tsx
type DrawerSize = 'md' | 'xl';

type DrawerProps = {
  open: boolean;
  onClose: () => void;
  title: ReactNode;
  description?: ReactNode;
  children: ReactNode;
  footer?: ReactNode;
  size?: DrawerSize; // default 'md'
  'aria-label'?: string;
  className?: string;
};
```

No `side` (V2 always right). No `Sheet.tsx` alias.

## 16. UI Lab route

`/admin/design-system/molecules/drawer`

## 17. Files created

- `resources/js/Components/molecules/Drawer.tsx`
- `resources/js/ui-lab/sections/DrawerLabSection.tsx`
- `docs/OURNEAR_DRAWER_REVIEW_PR5I.md`

## 18. Files modified

- `resources/js/design-system/classNames.ts` — `drawer*` recipes
- `resources/js/Components/molecules/index.ts`
- `resources/js/ui-lab/catalog.ts`, `metadata.ts`
- `resources/js/Pages/Admin/DesignSystem/Show.tsx`
- `app/Http/Controllers/Admin/DesignSystemController.php`
- `routes/admin.php`

## 19. Dependencies

**None**

## 20. TypeScript result

**PASS** (`npm run typecheck` / `tsc --noEmit`).

## 21. Build result

**PASS** (`npm run build` / Vite production build).

## 22. Deviations from V2 (molecule only)

- Per-instance **`useId`** for title/description ids
- Optional **`description`** prop + `aria-describedby`
- DS **Button** atom
- **`loading`** and **`closeLabel`** omitted (no consumer evidence)
- Description band uses border-b styling when present (minor layout polish)

V2 Drawer and all consumers **unchanged**.

## 23. V2 untouched confirmation

**Confirmed** — no V2 components, pages, routes, or callers modified.

## 24. Migration / adoption confirmation

**Not started.** Future adoption may map `Admin/ui/Drawer` → `@/Components/molecules/Drawer` where the workspace shell is intended.

## Explicit non-goals (PR-5I)

- `Sheet`, `Overlay`, `ModalPrimitive`
- Dialog `variant="drawer"`
- Domain drawers (ApplicationDrawer migration)
- Mobile nav sheet canonicalization
- Slide animations, focus trap, body scroll lock without V2 evidence
