# OURNEAR Divider composition — PR-4W

**Status:** Review complete — **no new molecule** (architecture phase — **V2 not modified**)

## 1. Decision

**LabeledDivider was NOT created.**

**Divider remains sufficient; no new molecule justified.**

## 2. Existing Divider contract (frozen PR-4J)

`resources/js/Components/atoms/Divider.tsx`

```tsx
<Divider
  orientation="horizontal" | "vertical"   // default horizontal
  decorative?                             // default true
  className?
/>
```

- Horizontal: decorative `<hr aria-hidden>` or semantic `<hr>`.
- Vertical: decorative `role="presentation"` or `role="separator"` + `aria-orientation="vertical"`.
- Recipes: `dividerHorizontal`, `dividerVertical` in `design-system/classNames.ts`.
- UI Lab: `/admin/design-system/atoms/divider`.

Divider is a **generic visual/semantic separator** with **no label slot** by design (PR-4J non-features).

## 3. V2 evidence (read-only)

| Pattern | Finding |
|---------|---------|
| `border-t border-line` / `border-b border-line` | Widespread ad hoc section rules (~29–31 files) |
| `divide-y divide-line` | List/stack separators (~9 files) |
| `<hr>` | Only canonical `Divider.tsx` — not used in V2 pages |
| `<Divider />` in app | **None** — UI Lab only |
| “OR” / auth-style line + text | **None** |
| Centered label between two lines | **None** as a repeated component or recipe |
| `SectionDivider`, `LabeledDivider`, `OrDivider` | **None** |
| `DropdownMenuSeparator` | Menu-specific molecule (`h-px bg-line`, inset) — distinct from content Divider |
| Heading + line | Section headings use typography/borders, not a shared “labeled divider” primitive |

One-off resume section styling (`OurNearResume.tsx`) is document layout, not a reusable product separator pattern.

## 4. Whether a molecule is justified

**No.**

A **LabeledDivider** would combine Divider + visible text (e.g. “OR”, section label). V2 does **not** demonstrate a repeated, governed need for that composition. Introducing it now would:

- Duplicate a pattern that does not exist in V2 evidence
- Blur the frozen Divider boundary (label slot was explicitly out of scope for PR-4J)
- Compete with **Stack** / **Cluster** + **Text** for one-off section labels without proven duplication savings

**Future reconsideration criteria:**

- Repeated V2 or product designs using the same flex row: line + label + line (especially auth “OR”, settings section breaks with inline labels)
- More than one migration target needing identical semantics and spacing
- Accessibility review requiring a documented pattern for decorative rules around visible divider text

Until then, callers can compose (architecture reference only — not a shipped molecule in PR-4W):

```tsx
<Cluster gap="sm" align="center">
  <Divider className="min-w-0 flex-1" />
  <Text variant="caption">OR</Text>
  <Divider className="min-w-0 flex-1" />
</Cluster>
```

## 5. API / composition (if created)

**N/A** — molecule not created.

## 6. Accessibility decision

Frozen **Divider** semantics unchanged. A future LabeledDivider would need:

- Decorative horizontal rules around visible text (avoid redundant `role="separator"` on the whole row)
- Non-form content as **Text** / `span`, not `<label>`
- No automatic `aria-label` on the composition

Not designed in PR-4W because the molecule was rejected.

## 7. Relationship to Divider

Divider stays the **only** canonical separator atom. Menu separation remains **DropdownMenuSeparator**.

## 8. Relationship to Stack / Cluster

Vertical section breaks: **Stack** + **Divider**.  
Hypothetical labeled row: **Cluster** + **Divider** + **Text** — sufficient for architecture phase without a new molecule.

## 9. UI Lab route

**No new route.** Existing: `/admin/design-system/atoms/divider`.

## 10. Explicit non-goals (PR-4W)

- No `Separator.tsx`, `SectionDivider`, or duplicate Divider atom
- No changes to frozen Divider, DropdownMenuSeparator, or V2 `border-t` usage
- No V2 migration

## 11. Migration status

**V2 was inspected READ-ONLY. No V2 application code was modified or rebuilt.**

## 12. Validation

No implementation in PR-4W — **typecheck/build not re-run** (documentation-only). Prior Divider PR-4J validation remains authoritative.
