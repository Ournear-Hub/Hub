# OURNEAR Section review — PR-4Y

**Status:** Review complete — **no Section primitive** (architecture phase — **V2 not modified**)

## 1. Decision

**Section was reviewed and not justified as a canonical design-system primitive.**

**Section was NOT created.**

## 2. Architectural classification

**N/A** — no new component. Page/region structure remains **composition** of frozen primitives + native HTML.

## 3. Rationale (why not justified)

### V2 evidence (read-only)

| Signal | Finding |
|--------|---------|
| `<section>` usage | **~221** tags in **~74** TSX files — widespread but **heterogeneous** classes |
| `Section.tsx` / `PageSection` / `ContentSection` | **None** in OurNear app code |
| Public page shell | `<section>` + **`PageContainer`** + content; padding varies (`py-10 md:py-12`, `py-14`, `border-t border-line bg-white` on About only) |
| Header patterns | **`SectionHeader`** (UI, left-aligned, optional action) vs **`SectionHeading`** (Public, centered) vs **`PublicPageHero`** vs inline `h2`/`p` — **not one contract** |
| Full-band components | **`CtaSection`**, **`PublicPageHero`** — **domain/marketing-specific**, not generic section |
| Admin/account | **`Admin/ui/Card`** uses `<section>` for card surface; drawers/forms use local `<section>` chunks — not a shared section primitive |
| Atom adoption | **`Container`**, **`Stack`** exist in design-system; **Stack ~14** production uses vs **221** sections — section rhythm not converged on Stack |
| Section-related recipes | **No** governed Tailwind “section” recipe in `classNames.ts` beyond page-specific ad hoc utilities |

### Architectural test (PR-4Y)

| # | Criterion | Met? |
|---|-----------|------|
| 1 | Repeated section-level structure | **Partially** — many `<section>` tags, but **not one structure** |
| 2 | Stable visual/layout contract | **No** — padding, border, background differ by page/CMS |
| 3 | Not merely Container + Stack / Card + Stack / Heading + Text | **Fails** — observed patterns decompose to those pieces |
| 4 | Durable value beyond semantic `<section>` | **No** — native landmark + Tailwind/`Stack` suffices |
| 5 | Not a page-layout mega-component | **Risk high** if Section adds title/actions/surface |
| 6 | Minimal API | **No evidence** for which single spacing/variant scale would be canonical |
| 7–10 | No duplicate Container/Card/Stack/surface system | **Would blur** Container (width) vs section band padding |
| 11–14 | No business behavior, responsive JS, cloning, state | **Satisfied only by not creating Section** |

**Conclusion:** V2 shows **many landmarks**, not **one Section contract**. Existing V2 helpers (`SectionHeader`, etc.) are **partial, page-family-specific** and must not be duplicated as a generic atom without migration evidence (out of scope).

## 4. Recommended composition (architecture reference)

Use **native semantics** and frozen atoms; adjust per page until a later adoption phase.

**Public-style band (width + vertical padding):**

```tsx
<section className="py-10 md:py-12">
  <Container size="md" gutter="md">
    <Stack gap="md">
      <Heading level={2}>Section title</Heading>
      <Text variant="secondary">Optional description</Text>
      {/* content */}
    </Stack>
  </Container>
</section>
```

**Bounded block (surface):**

```tsx
<Card padding="standard">
  <Stack gap="sm">
    <Heading level={3}>Settings group</Heading>
    {/* fields */}
  </Stack>
</Card>
```

**Separator between bands:** `Divider` or `border-t border-line` on the `<section>` (V2 ad hoc — caller-owned).

**Titled marketing header:** keep V2 **`SectionHeader`** / **`PublicPageHero`** unchanged until explicit migration; do not replace with a new Section atom in this PR.

## 5. Relationships to frozen primitives

| Primitive | Role |
|-----------|------|
| **Container** | Max width + gutters — not section padding or landmark |
| **Card** | Bounded **visual surface** — not page band / landmark wrapper |
| **Stack** | Vertical **sibling** gap inside a region |
| **Cluster** | Horizontal composition (e.g. header + action) |
| **Divider** | Separator between regions |
| **Heading / Text** | Typography — not section chrome |
| **`<section>`** | Caller-owned landmark when needed; optional `aria-labelledby` via caller heading `id` |

**Card vs Section:** PR-4X — **Card** is the only canonical surface; flat shells use **`Card padding="none"`**. Section must not reintroduce Panel/surface behavior.

## 6. API / visual / interaction (if created)

**N/A**

## 7. Accessibility

- Prefer real **`<section>`** when the landmark adds meaning; not every visual block needs it.
- **Heading** level and copy come from **Heading** / page structure; link sections with `aria-labelledby` only when callers wire `id` — **no auto IDs** from a Section atom.
- Do not wrap non-form copy in `<label>`.

## 8. UI Lab route

**None** (Section rejected).

## 9. Explicit non-goals (PR-4Y)

- No `ContentSection`, `PageSection`, `SectionHeader`/`SectionFooter` atoms
- No Panel/Surface
- No changes to frozen atoms/molecules, V2 `PageContainer`, `SectionHeader`, `SectionHeading`, `CtaSection`, admin/public pages
- No V2 migration or adapters

## 10. V2 inspection status

Read-only — patterns above; no file modifications.

## 11. V2 modification status

**None.**

## 12. Dependencies

**None.**

## 13. Deviations

**None.**

## 14. Validation

Documentation-only — **typecheck/build not run** (not required).
