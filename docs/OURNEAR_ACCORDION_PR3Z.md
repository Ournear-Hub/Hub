# OURNEAR Accordion — PR-3Z

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Purpose

Expandable/collapsible **content disclosure** for FAQs, profile sections, job/employer detail blocks, filter panels, and long-form subsections. Not a modal, drawer, tabs, navigation, or card primitive.

## 2. Chosen API model

**Compound component** (single canonical API):

```tsx
<Accordion type="single" | "multiple" collapsible? defaultValue? value? onValueChange? className?>
  <AccordionItem value="id" disabled? className?>
    <AccordionTrigger className?>…title…</AccordionTrigger>
    <AccordionContent className?>…</AccordionContent>
  </AccordionItem>
</Accordion>
```

No parallel data-driven `items={[]}` API in this PR.

## 3. Native vs custom

**Native-first:** each item is `<details>` with `<summary>` trigger and a sibling content region. React coordinates **single-open** vs **multiple-open** and optional **controlled** `value` / `onValueChange` via `open` + `onToggle` / summary click (controlled). No custom focus manager, no height animation, no new dependencies.

## 4. State ownership

Accordion owns **transient open/closed state** only (or reflects caller `value`). It does not own forms, fetching, persistence, routing, or URL state.

## 5. Controlled / uncontrolled

- **Uncontrolled:** `defaultValue` (`string` for single, `string[]` for multiple) + internal state; `onValueChange` optional.
- **Controlled:** `value` + `onValueChange`; native toggle prevented; summary click updates via `requestToggle`.
- **Single + `collapsible` (default `true`):** closing the open item yields empty `value` (`''` externally).

## 6. Multiple-open behavior

- `type="multiple"`: each item toggles independently; `value` / `onValueChange` use `string[]`.
- `type="single"` (default): opening one item closes others.

## 7. Keyboard behavior

Tab reaches each summary. Enter and Space toggle via native summary behavior. No custom arrow-key accordion pattern.

## 8. Accessibility semantics

Native disclosure per item. Trigger: `aria-expanded`, `aria-controls` → stable content `id`. Disabled: `aria-disabled`, cannot activate. No `role="tab"`, `role="dialog"`, or `role="menu"`. Closed panels are not in the tab order (native).

## 9. Trigger behavior

Summary row: title text, trailing `OurNearIcon` `chevron-down` with `group-open:rotate-180` (CSS). Optional `className` on trigger. Entire content area is not a trigger.

## 10. Content behavior

Caller `ReactNode` in `AccordionContent`; no child cloning or injected props. Content wrapped in `<div id={…}>` for `aria-controls` only.

## 11. Disabled behavior

Per-item `disabled` on `AccordionItem`: summary styled disabled, toggles blocked, item forced closed.

## 12. Animation decision

None in this PR (no canonical motion system). Chevron rotation via CSS only.

## 13. Icon behavior

`OurNearIcon` + registry (`chevron-down`); no Tabler direct imports.

## 14. V2 inspection findings (read-only)

- **No reusable Accordion component** in V2.
- **Native `<details>` / `<summary>`** used for independent filter disclosures, e.g. `EmployerStaffingSearch.tsx`, `EmployerApplicantSearch.tsx`, `EmployerPartnershipSearch.tsx` — typically **multiple panels can be open**; uncontrolled local state; bordered/surface styling.
- **Jobs search:** `JobsSearchPanel.tsx` uses responsive details/summary for mobile filters.
- No URL/query-driven expansion observed for these patterns.

## 15. Non-features

Not tabs, segmented control, navigation, modal/drawer, automatic headings, FormField wrapper, default animation, or V2 migration/adapters.

## 16. Confirmation V2 was not modified

V2 product components and pages were inspected only; no V2 files changed in PR-3Z.

## 17. Validation results

- `npm run typecheck` — **PASS**
- `npm run build` — **PASS**
