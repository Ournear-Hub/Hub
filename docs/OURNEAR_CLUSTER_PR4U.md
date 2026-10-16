# OURNEAR Cluster — PR-4U

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Naming decision

**Cluster** (not Inline) — V2 repeats both tight horizontal rows (`flex items-center gap-*`) and **wrapped** compact collections (`flex flex-wrap gap-2` for chips/tags, MultiSelect control). “Cluster” matches that dual use without implying CSS `inline` display or a generic Row/Flex API. **Inline** was rejected as the primary name because wrapping chip/metadata groups is a first-class horizontal rhythm in evidence, not an edge case.

## 2. Architectural classification

**Atom** — horizontal sibling spacing via `flex-row` + CSS `gap`, complementary to **Stack**.

## 3. Purpose

Consistent **horizontal rhythm** for icon+text, avatar+text, action groups, and wrapped chip rows. Not InputGroup, not ButtonGroup, not page layout.

## 4. API

```tsx
<Cluster
  gap="xs" | "sm" | "md" | "lg" | "xl"   // default sm
  align="stretch" | "start" | "center" | "end" | "baseline"   // default center
  wrap={false | true}                     // default false
  className?
>
  {children}
</Cluster>
```

## 5. Gap contract

Same scale as Stack (`stackGap*` / `spacingScalePx`): xs→gap-1, sm→gap-2, md→gap-4, lg→gap-6, xl→gap-8. `clusterGap*` aliases those values — no second spacing system.

## 6. Alignment contract

Cross-axis `align-items`: stretch, start, center (default), end, **baseline** (icon+text). No `justify`.

## 7. Wrap contract

`wrap={false}` → `flex-nowrap` (default). `wrap={true}` → `flex-wrap` for chip/tag collections. No wrap-reverse or responsive wrap props.

## 8. Layout contract

`clusterRoot` → `flex flex-row` + gap + optional wrap. Native CSS gap only.

## 9. Spacing ownership

Cluster owns **horizontal sibling gap** only. Margin, padding, width, and vertical page rhythm remain caller/Stack/Container responsibilities.

## 10. Responsive boundary

No breakpoint props or JS viewport logic.

## 11. Relationship to Stack

Stack = vertical; Cluster = horizontal. Composable (e.g. Stack of Cluster metadata rows). Neither wraps the other.

## 12. Relationship to Container

Container = width/gutters; Cluster = horizontal composition inside.

## 13. Relationship to Chip

Chip = single metadata unit; Cluster lays out many chips (often `wrap`).

## 14. Relationship to Button / LinkButton

Action groups compose Cluster + buttons; button semantics stay on Button/LinkButton. No ButtonGroup atom in PR-4U.

## 15. UI Lab route

`/admin/design-system/atoms/cluster`

## 16. V2 evidence (read-only)

- Chip lab: `flex flex-wrap gap-2`, `flex flex-wrap items-center gap-2`
- MultiSelect: `flex flex-wrap items-center gap-1` on control
- Button: internal `inline-flex items-center gap-2` (control-level, not page layout)
- InputGroup: horizontal **join** recipe — not duplicated
- No V2 Row/Inline/Cluster primitive

## 17. Explicit non-goals

Generic Flex, HStack, Row, ButtonGroup, justify/direction/grow/shrink, responsive arrays, V2 migration, semantic toolbar roles.

## 18. Migration status

**V2 was inspected READ-ONLY. No V2 application code was modified or rebuilt.**
