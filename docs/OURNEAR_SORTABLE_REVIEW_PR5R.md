# OURNEAR Sortable / Reorderable list review — PR-5R

**Status:** Review complete — **no SortableList / ReorderableList component created** (architecture phase — **V2 not modified**)

## 1. Decision

**SORTABLE / REORDER ARCHITECTURE DOCUMENTED — NO COMPONENT**

Not **MOVE-CONTROL PATTERN DOCUMENTED** — V2 has **no** repeated Move Up / Move Down (or equivalent) reorder controls.

No UI Lab, exports, DnD dependencies, or reorder services in this PR.

## 2. Primary architectural question (A–E)

| Option | Assessment |
|--------|------------|
| **A — SortableList molecule** | **Not justified** — no V2 sortable list UI or shared contract. |
| **B — ReorderableList molecule** | **Not justified** — same; naming would duplicate a non-existent pattern. |
| **C — Sortable organism** | **Rejected** — no coordinated ordering system. |
| **D — Product / data ordering** | **Partial:** `sort_order` **numeric form fields** and **read-only display** — **data model**, not list reorder interaction. |
| **E — No component (this PR)** | **Selected.** |

**Final classification:** **No user-controlled list reordering in V2 frontend**; table **column sorting** also absent (PR-5M); **Pagination** handles page navigation only.

## 3. Evidence inspected (read-only)

**Search method:** PowerShell `Get-ChildItem -Recurse` + `Select-String` on `D:\Ournear Hub\Ournear Hire\resources\js`; `package.json` read for dependencies.

| Term / pattern | Result (app TS/TSX) |
|----------------|---------------------|
| `SortableList`, `Sortable.tsx`, `ReorderableList`, `DraggableList`, `DragDrop` | **0** |
| `useSortable`, `useDraggable`, `@dnd-kit`, `react-beautiful-dnd`, `react-dnd`, `sortablejs`, `react-sortable` | **0** imports/usages |
| `onDragStart`, `onDragEnd`, `onDragOver`, `onDrop`, `draggable=`, `dataTransfer`, `setDragImage` | **0** |
| `Move up`, `Move down`, `moveUp`, `moveDown`, `shiftUp`, `shiftDown` | **0** |
| `reorder` / `Reorder` (UI) | **0** meaningful matches |
| `aria-sort`, `aria-grabbed`, `aria-dropeffect` | **0** |
| `dropzone` / file drop UI | **0** in surveyed TSX (no conflated file-upload DnD found) |

| Source | Finding |
|--------|---------|
| `package.json` | Dependencies: `@inertiajs/react`, `@tabler/icons-react`, `react`, `react-dom` only — **no DnD library**. |
| `Pages/Admin/MasterData/Index.tsx` | Drawer form: **`sort_order`** as **`Input type="number"`** with label “Sort order” — **manual numeric entry**, not drag or move-button reorder of a list. |
| `types/masterData.ts`, `types/cms.ts`, `types/media.ts` | **`sort_order: number`** on types — backend/display ordering fields. |
| `Pages/Admin/Interviews/Index.tsx`, `Pages/Admin/Work/Index.tsx` | **`sort_order`** shown as **interview sequence label** (`Interview #N`) — **read-only metadata**, not reorder UI. |
| `Components/Admin/ApplicationDrawer.tsx` | Type/reference to `sort_order` — domain data, not sortable list widget. |
| Frozen **Combobox / DropdownMenu / Tabs** | List keyboard navigation — **selection/menus**, not reorder (unchanged). |

## 4. Candidate patterns

| Pattern | Where | Reuse | Interaction | Verdict |
|---------|--------|------:|-------------|---------|
| Numeric `sort_order` field | Master Data drawer | 1 workspace | Type number, save via form POST/PUT | **Database ordering** — not SortableList |
| Interview `#sort_order` label | Interviews, Work | Few | Display only | **Domain metadata** |
| Admin DataTable lists | PR-5M | Many | Row click, pagination | **No column sort, no reorder** |
| Server list order | All paginated lists | Many | Laravel default sort | **Server-side** — not UI reorder |

## 5. Sorting vs reordering

### A — Table column sorting

- **No** clickable column headers, **`aria-sort`**, or client/server table sort UI in V2 frontend (consistent with PR-5M).
- **Not** a SortableList concern.

### B — Database / display ordering

- **`sort_order`** on master records: edited as a **single number** in a form.
- Does **not** imply dragging list items or move up/down among siblings in the UI.

### C — Move Up / Move Down

- **Not evidenced** in V2 TSX.

### D — Drag-and-drop reorder

- **Not evidenced** — no handlers, no libraries, no draggable lists.

### E — Genuine sortable / reorderable list

- **Not met** — no ordered-item manipulation contract in shared components.

## 6. Database ordering findings

- Master Data CRUD exposes **`sort_order`** for administrator-entered priority relative to other records (backend applies on save).
- CMS/media types include **`sort_order`** — likely server-ordered collections without a shared reorder widget in `resources/js`.
- **Architectural rule:** data **`sort_order` / `position` fields ≠ Design-System SortableList.**

## 7. Move-control findings

**None.** No reusable pair of controls to shift item index in a visible list.

## 8. Drag-and-drop findings

**None** for UI list reorder.

- No HTML5 drag event handlers in application components.
- No third-party sortable/DnD packages installed.

(File upload drag areas were not found as a competing pattern in this pass; even if present elsewhere, they would be **out of scope** for list reorder.)

## 9. Keyboard findings

- **No** keyboard reorder (ArrowUp/ArrowDown to change item order in a list).
- Arrow keys in frozen molecules serve **Combobox, DropdownMenu, Tabs, MultiSelect** — unrelated.
- **Do not** invent keyboard reorder API without V2 evidence.

## 10. Accessibility findings

- **No** `aria-grabbed`, **`aria-dropeffect`**, or reorder **`aria-live`** regions.
- Master Data **`sort_order`** field uses **`Label` + `Input`** — standard form field semantics.
- If drag reorder were added in future, keyboard and announcement requirements would need explicit product/DS decision — **not predefined in V2**.

## 11. Package / library findings

`D:\Ournear Hub\Ournear Hire\package.json`:

- **No** `@dnd-kit/*`, `react-beautiful-dnd`, `react-dnd`, `sortablejs`, `dragula`, `react-draggable`, etc.
- **Do not install** DnD libraries in architecture PRs without V2 proof and explicit scope.

## 12. State ownership (V2)

| Concern | Owner |
|---------|--------|
| List item order (UI) | **N/A** — no reorder UI |
| `sort_order` value | **Master Data form** + server persistence on submit |
| Interview sequence display | **Server-provided** `sort_order` on interview entity |
| Paginated list order | **Server query** |

Future canonical component would require caller-owned **`items`**, **`onReorder`**, and **persistence** — no V2 template today.

## 13. Responsive findings

**N/A** — no reorder interaction.

## 14. Visual contract

**N/A** for sortable lists.

Observed related UI: Master Data **number input** for sort order; no drag handles, drop indicators, or move-button columns.

## 15. Relationship to frozen components

| Component | Boundary |
|-----------|----------|
| **Button / IconButton** | Would compose move actions **if** a pattern existed |
| **Cluster** | Horizontal action groups — not ordering |
| **Pagination** | Page navigation — not item reorder |
| **DataTable (PR-5M)** | Tabular data — no sort/reorder mechanics in V2 |
| **DropdownMenu** | Actions menu — not reorder |
| **NumberField / Input** | Appropriate for **numeric `sort_order`** entry |

## 16. Why rejected

1. **Zero** shared Sortable/Reorderable list components or imports.
2. **Zero** drag-and-drop list implementation and **zero** DnD dependencies.
3. **Zero** Move Up/Down reorder control pattern.
4. **`sort_order`** is **form/metadata**, not interactive list reordering.
5. Table **column sort** already absent — separate from but consistent with “no ordering UI framework.”
6. Adding SortableList now would be **speculative** and invite unused DnD infrastructure.

## 17. Future adoption boundary

Revisit a **`SortableList` / `ReorderableList` molecule** only if V2 introduces a **repeated** pattern with several of:

- visible **ordered collection**,
- user-driven **reorder** (drag or explicit move controls),
- caller **`onReorder`** / index mutation,
- optional persist hook (caller-owned),

and documented **keyboard + accessibility** behavior.

Until then:

- Continue **numeric `sort_order`** (or similar) via **Input/NumberField** in forms.
- Continue **server-defined** list order for tables and paginated lists.
- **Do not** add DnD providers, reorder services, or optimistic ordering frameworks in the design system without V2 proof.

**UI Lab:** N/A unless a component is created in a future PR.

## 18. V2 safety

- V2 files **not modified**.
- Frozen **Button**, **IconButton**, **Cluster**, **Pagination**, **DropdownMenu**, table architecture docs — **unchanged**.
- **No** dependency changes.

## 19. Stop condition

PR-5R complete. Do not start PR-5S. Do not begin V2 reorder migration.
