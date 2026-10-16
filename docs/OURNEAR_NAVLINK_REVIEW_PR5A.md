# OURNEAR NavLink review — PR-5A

**Status:** Review complete — **no NavLink primitive** (architecture phase — **V2 not modified**)

## 1. Decision

**NavLink was reviewed and not justified as a canonical design-system primitive.**

**NavLink was NOT created.**

## 2. Architectural classification

**N/A** — navigation items remain **caller-composed** using frozen **Link** (textual anchor), **LinkButton** (button-styled anchor), or **Inertia `Link`** in V2 shells until a later adoption phase.

## 3. Rationale

### V2 evidence (read-only)

| Context | Pattern | Active / routing |
|---------|---------|------------------|
| **Public Header** | `navLinkClass(active)` — border-b-2, brand/muted | `usePage().url` + `isNavActive()` (`content/navigation.ts`) |
| **Admin Sidebar** | Local function **`NavLink`** — rounded-md, white/15 on **dark brand** sidebar | `isActive(url, href)` + permissions + nested children + `data-active` |
| **Account SettingsNav** | Pill: `bg-brand text-white` vs muted hover | `usePage().url` path equality per item |
| **Breadcrumbs** | Inertia `Link` + `aria-current="page"` on last crumb | Component-owned “last = current” |
| **Mobile menu** | Separate `PublicMobileMenu` | Shell-specific |
| **Canonical atom Link** | `linkVariant*` + underline — **UI Lab only** in product | Native `<a>`, no Inertia |

**Findings:**

1. **Repeated navigation structure** — yes (arrays of `{ href, label }`, maps in shells).
2. **Stable shared visual contract** — **no**. Public top nav, admin sidebar, and settings pills use **different colors, density, and active treatments** (underline vs filled pill vs translucent on brand).
3. **Behavior beyond Link** — active truth and **route matching live in shells** (`usePage`, `isNavActive`, `isActive`, permissions, nested sections). PR-5A forbids baking that into an atom.
4. **Cross-context reuse** — a generic NavLink would either pick **one** look (wrong for admin sidebar) or expose variants that duplicate Tailwind per surface (forbidden API explosion).

**Local name collision:** `Sidebar.tsx` defines an internal **`NavLink`** helper — **product/shell code**, not a design-system export. It must not be mistaken for a canonical primitive.

### Architectural test (PR-5A)

Fails **#2, #4, #5** (stable visual + active contract across contexts). Passes rejection path: **caller-owned active** + **Link**/`className`/`aria-current` is sufficient at atom layer.

## 4. Recommended composition (architecture reference)

**Design-system lab / static pages (no Inertia):**

```tsx
<Link href="/jobs" variant="muted" underline="none" className={active ? 'text-brand' : ''} aria-current={active ? 'page' : undefined}>
  Jobs
</Link>
```

**Icon + label (caller-owned):**

```tsx
<Link href="..." className="inline-flex items-center gap-2">
  <OurNearIcon name="briefcase" size="sm" />
  <span>Jobs</span>
</Link>
```

**CTA-style nav:** **LinkButton**, not NavLink.

**Routing / active truth:** Page, layout, or future **navigation organism** — not an atom.

## 5. Relationships

| Primitive | Role |
|-----------|------|
| **Link** | Generic native anchor; variant + underline; accepts `aria-current`, `className` |
| **LinkButton** | Button-visual anchor; actions/CTAs |
| **Button / IconButton** | Actions, not generic nav items |
| **DropdownMenu** | `menuitem` semantics — not nav list |
| **SegmentedControl** | `radiogroup` selection — not navigation |

## 6. Visual / interaction / accessibility (if NavLink existed)

**N/A.** Guidance for callers:

- Native **`<a href>`** (or Inertia in V2 only).
- **`aria-current="page"`** when caller marks current item — **never** from URL parsing inside an atom.
- Focus via existing **linkBase** / shell classes.

## 7. UI Lab route

**None.**

## 8. Explicit non-goals (PR-5A)

- No `NavigationItem`, `SidebarLink`, `AdminNavLink`, `ActiveLink`
- No Inertia/router in design-system atoms
- No permission or nested-menu logic in atoms
- No V2 Sidebar/Header/SettingsNav migration

## 9. V2 inspection status

Read-only — `Header.tsx`, `Sidebar.tsx`, `SettingsNav.tsx`, `Breadcrumbs.tsx`, `content/navigation.ts`, atoms `Link.tsx`.

## 10. V2 modification status

**None.**

## 11. Dependencies

**None.**

## 12. Deviations

**None.**

## 13. Validation

Documentation-only — **typecheck/build not run**.

## 14. Future reconsideration

Justify a **NavLink** atom or a **navigation molecule/organism** only when:

- One **governed nav-item recipe** (or small surface map) is documented in `classNames.ts` with evidence from multiple contexts, **and**
- Active styling is **caller-supplied boolean** with shared inactive/active classes, **and**
- Shell migration is explicitly in scope.

Until then, **Link remains sufficient** for the atom layer.
