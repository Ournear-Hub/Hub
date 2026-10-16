# OURNEAR Panel / surface review — PR-4X

**Status:** Review complete — **no Panel primitive** (architecture phase — **V2 not modified**)

## 1. Decision

**Panel was NOT created.**

**Card remains the canonical bounded surface; Panel not justified.**

## 2. Card contract (frozen PR-4R)

`resources/js/Components/atoms/Card.tsx`

```tsx
<Card
  padding="none" | "compact" | "standard" | "feature"   // default standard
  className?
>
  {children}
</Card>
```

| padding | Visual |
|---------|--------|
| **none** | `rounded-xl border border-line bg-white` — **no shadow**, caller-owned inner spacing |
| compact | `cardDensity.compact` — `rounded-lg`, `p-3`, `shadow-sm` |
| standard | `cardDensity.standard` — `p-5 sm:p-6`, `rounded-xl`, `shadow-sm` |
| feature | `cardDensity.feature` — larger padding, `shadow-md` |

Non-interactive `<div>`. No auto landmarks or roles.

UI Lab: `/admin/design-system/atoms/card` (includes `padding="none"` + caller padding demo).

## 3. V2 evidence (read-only)

### Surface patterns

- **~99 TSX files** use hand-rolled `rounded-xl border border-line bg-white` (admin settings, dashboards, account, lists).
- **Flat admin sections** typically **omit shadow** and set **caller padding** (`p-4`–`p-6`, responsive steps, or **zero** on outer table/list shells).
- **`Components/Admin/ui/Card.tsx`** — `<section>` + `rounded-xl border border-line bg-white p-5` (no shadow); **6** admin files.
- **`Components/UI/Card.tsx`** — `cardDensity` with **shadow**; **~13** public/account pages.
- **Domain cards** (`JobCard`, `ServiceCard`, `PublicCompanyCard`) — product-specific; not generic surfaces.
- **No `Panel.tsx`** or shared panel primitive in V2.
- **`LabPanel`** (ui-lab) — title chrome + ≈ `cardDensity.standard`; not a product surface atom.
- Explicit **`shadow-none`** on bordered shells: essentially **resume print** only.

### Distinct “panel” vs “card” in V2?

V2 does **not** name or implement a stable second surface family. Differences are **density and shadow**, already expressed by **`Card` padding** and **`className`**, not by a separate Panel semantics.

## 4. Whether Panel is justified

**No.**

| Question | Answer |
|----------|--------|
| Flat structural surfaces semantically different from cards? | **No** — same white + `border-line` bounded box; shadow/padding vary by context |
| Stable visual contract for a second primitive? | **No** — recipes collapse to `padding="none"` vs density presets |
| Would Panel reduce duplication? | **Minimal** — would duplicate `Card padding="none"` (~same classes as most inline admin shells) |
| Can Card express the pattern? | **Yes** — `padding="none"` + caller `p-*`; optional `className` for `divide-y`, semantic `<section>` wrapper outside Card if needed |
| Two competing generic surfaces? | **Yes, if Panel were added** — architects and migrators would choose Card vs Panel arbitrarily |

**Future reconsideration criteria:**

- Product language consistently distinguishes “panel” (e.g. full-bleed settings regions, sidebar shells) with a **documented visual contract that cannot be expressed** via Card `padding` + `className` without ambiguity
- Evidence that `padding="none"` is insufficient (e.g. required different default radius/border token for structural regions only)

Until then, use:

```tsx
<Card padding="none" className="p-5">…</Card>
```

for flat admin-style sections (equivalent to common V2 inline + Admin/ui Card).

## 5. Distinction from Card (if Panel were created)

**N/A** — Panel not created.

**Durable one-line distinction today:**

- **Card** = canonical bounded surface (flat shell via `padding="none"`, elevated/dense via compact/standard/feature).

## 6. API (if created)

**N/A**

## 7. Accessibility

Unchanged: **Card** does not inject roles/landmarks. Callers use `<section>` + headings when semantics matter (V2 pattern on Users/Show, Settings).

## 8. Interaction boundary

**Card** remains non-interactive; no Panel-specific behavior introduced.

## 9. Composition boundary

Caller-owned children on **Card**; no header/body/footer slots.

## 10. Container relationship

**Container** = width/gutters. **Card** = visual surface inside width. Unchanged.

## 11. UI Lab route

**No new route.** Bounded surfaces: `/admin/design-system/atoms/card`.

## 12. Explicit non-goals (PR-4X)

- No `Surface`, `ContentPanel`, `SectionPanel`, `DashboardPanel`
- No changes to frozen **Card**, V2 **Admin/ui/Card**, **UI/Card**, or JobCard
- No new token system
- No V2 migration

## 13. Migration status

**V2 was inspected READ-ONLY. No V2 application code was modified or rebuilt.**

## 14. Validation

Documentation-only PR — **typecheck/build not re-run**. Prior Card PR-4R validation remains authoritative.
