# OURNEAR Calendar / Date-picker architecture review — PR-5S

**Status:** Review complete — **no Calendar / DatePicker / DateRangePicker component created** (architecture phase — **V2 not modified**)

## 1. Decision

**DATE COMPONENT SYSTEM DOCUMENTED — NO COMPONENT**

The canonical date/time surface for OurNear remains **native HTML pickers** via frozen **DateField**, **DateTimeField**, and **TimeField** (wrapping **Input** + `showPicker` where supported). No separate Calendar grid, DatePicker popover, or DateRangePicker molecule is warranted by V2 evidence.

## 2. Architectural classification

| Class | V2 / DS finding |
|-------|-----------------|
| **Custom Calendar grid** | **Absent** — no month/day cell UI, no month navigation component |
| **DatePicker (trigger + popover calendar)** | **Absent** — no shared trigger+calendar pattern |
| **DateRangePicker** | **Absent** — filter **pairs** use two independent native date inputs |
| **Native date input** | **Dominant** — ~63 `type="date"`, ~12 `type="datetime-local"`, ~2 `type="time"` in TSX |
| **DS DateField / DateTimeField / TimeField** | **Frozen** — native types only; explicitly **do not own calendar UI** |
| **Input showPicker** | **Canonical** — click opens **browser** picker for date/time types |
| **Date libraries** | **None** in `package.json` |
| **Scheduling / booking calendar** | **Not evidenced** as reusable UI |

## 3. Evidence inspected (read-only)

**Paths:** `D:\Ournear Hub\Ournear Hire\resources\js`, `package.json`, `docs` (prior PRs), UI Lab date sections.

**Search method:** PowerShell `Get-ChildItem -Recurse` + `Select-String`.

| Term | App TS/TSX count |
|------|------------------|
| `DatePicker`, `DateRangePicker`, `MonthPicker`, `WeekPicker` | **0** |
| `react-day-picker`, `react-datepicker`, `flatpickr`, `dayjs`, `date-fns`, `luxon`, `@internationalized/date` | **0** |
| `Calendar` (component name) | **0** implementations — only doc comments (“calendar UI” = browser) |
| `role="grid"` / `gridcell` / `aria-current="date"` (date UI) | **0** |
| `type="date"` | **~63** |
| `type="datetime-local"` | **~12** |
| `type="time"` | **~2** |
| `type="month"` / `type="week"` | **0** observed |
| `date_from` / `date_to` (filter pairs) | **~26** references — separate fields, not one range widget |

| Source | Finding |
|--------|---------|
| `package.json` | React, Inertia, Tabler icons only — **no date/calendar npm packages** |
| `Components/atoms/Input.tsx` | `isPickerType` for date/datetime-local/time/month/week; **`showPicker()`** on click when supported |
| `Components/molecules/DateField.tsx` | `type="date"` via Input — **“Does not own value, parsing, or calendar UI”** |
| `Components/molecules/DateTimeField.tsx` | `type="datetime-local"` — same contract |
| `Components/molecules/TimeField.tsx` | `type="time"` — same contract |
| `ui-lab/sections/DateFieldLabSection.tsx` | Documents **native browser date picker**; min/max/step via HTML attributes |
| V2 production forms | **`Admin/ui/Input`** or **`Input type="date"`** in FilterBar/forms (e.g. Candidates, Employers, Master Data, JobForm, ApplicationWorkspace filters) |
| `DateField` in pages | **UI Lab / Design System Show** — not broad V2 page migration yet |

## 4. Calendar evidence

**No custom calendar** implementation:

- No day grid markup
- No previous/next month controls as a shared component
- No selected-day cell styling system
- No “today” indicator component
- No disabled-date matrix in React UI (constraints use native **`min`/`max`** where set)

## 5. Date-picker evidence

**No DatePicker molecule** in V2:

- No text field + calendar popover composite
- No Popover/Dialog hosting a custom calendar (Popover/Dialog frozen for other uses)
- Selection UX is **native picker** surfaced by **`input type="date"`** / **`datetime-local`**

Industry DatePicker patterns are **not** evidence; V2 consistently delegates picker UI to the **user agent**.

## 6. Native date input evidence

| Pattern | Where | Notes |
|---------|--------|--------|
| Filter date range | Admin indexes | `date_from` + `date_to` as **two** `<Input type="date">` (or name attrs) in FilterBar |
| Job / application forms | `JobForm`, `ApplicationWorkspacePage`, candidate profile sections | Native date fields for deadlines, joining, etc. |
| DS canonical | `DateField`, `DateTimeField`, `TimeField` | Styling + `showPicker`; forward **`min`/`max`/`step`/`value`/`defaultValue`** |
| Uncontrolled filters | Many admin pages | **`defaultValue`** from server `filters` + form submit — Inertia |

**Architectural rule (PR-5S):** Native `<input type="date">` **is sufficient** and **is already canonicalized** as **DateField** — it is **not** missing Calendar evidence.

## 7. Date-range evidence

- **Semantic range filtering** exists (start/end query params).
- **UI** is **two independent native date inputs**, not a unified range picker, highlights, or preset chips.
- **Do not** create **DateRangePicker** without a repeated custom range **interaction** (shared state UI, range highlight, single control).

## 8. Dependency / library findings

`D:\Ournear Hub\Ournear Hire\package.json`:

- **No** `react-day-picker`, `react-datepicker`, `flatpickr`, `dayjs`, `date-fns`, `luxon`, `moment`, `@internationalized/date`, Radix/shadcn calendar packages.
- **Do not install** date libraries in architecture PRs without V2 usage proof.

Backend PHP may format dates; frontend TSX surveyed shows **no** imported JS date library for picker UI.

## 9. Accessibility findings

- Native date/time inputs: browser-provided picker accessibility (platform-dependent).
- DS fields pass through **`aria-label`**, **`aria-invalid`**, **`required`**, **`disabled`** via Input/FormField.
- **No** custom `role="grid"` date table — avoids duplicating browser picker a11y.
- **No** `aria-live` month announcements — no custom calendar to announce.

If a **custom Calendar** were added later, it would need explicit grid semantics and keyboard spec — **not predefined by V2**.

## 10. Keyboard interaction findings

- **No** custom ArrowUp/Down/Home/End navigation between day cells in app code.
- **No** roving tabindex on a date grid.
- Native inputs: tab focus + browser picker keyboard (UA-dependent).
- Frozen **Combobox/DropdownMenu/Tabs** keyboard models — unrelated to dates.

## 11. State ownership findings

| Concern | Owner |
|---------|--------|
| Value | **Caller** — controlled or uncontrolled native input / Inertia form |
| Parsing/format display | **Browser** + server (Laravel) — not DS calendar |
| Range filter | **Two fields** + page submit → query params |
| Picker open | **Native** + Input **`showPicker`** on click |
| Timezone | **Server/product** — no timezone picker UI in V2 |

A future DatePicker would **not** own fetch or business rules; today **DateField already** limits DS ownership to presentation + native semantics.

## 12. Responsive findings

- Native pickers adapt per **platform** (mobile often full-screen date UI).
- **No** V2 split between “inline calendar desktop / modal mobile” custom component.
- Filters use same native inputs in responsive FilterBar grids.

## 13. Visual contract

**Canonical (frozen):**

- Input/control recipes from `design-system/classNames.ts` (`controlBase`, `adminControlBase`, `controlError`).
- Picker types: **`cursor-pointer`** on Input when type is date/time/datetime-local.

**No** custom calendar visual system (cell size, month header, range highlight).

## 14. Relationship to DateField / DateTimeField / TimeField

| Component | Role |
|-----------|------|
| **DateField** | **`type="date"`** — **the** canonical date entry; browser calendar = picker |
| **DateTimeField** | **`type="datetime-local"`** |
| **TimeField** | **`type="time"`** |
| **FormField** | Label/hint/error wrapper — compose with date fields |
| **Hypothetical Calendar** | Would only compose **inside** a future DatePicker if V2 proved need — **not created now** |
| **Hypothetical DatePicker** | Would duplicate DateField’s role unless V2 abandons native pickers — **not evidenced** |

**Do not** create Calendar merely because DateField exists.

## 15. Relationship to Popover / Dialog

- **No** V2 pattern of calendar inside **Popover** or **Dialog** for date selection.
- Popover/Dialog remain available for **future** adoption if product requires custom calendar — **compose then**, not speculatively now.

## 16. Candidate patterns

| Pattern | Where | Reuse | Verdict |
|---------|--------|------:|---------|
| Native date input | Admin/account forms, filters | High | **DateField** / Input |
| Native datetime-local | Forms/interviews | Low–medium | **DateTimeField** |
| date_from + date_to pair | Admin filters | ~several pages | **Two DateFields** — not DateRangePicker |
| Input showPicker | `Input.tsx` | All picker types | **Atom behavior** |
| DateField UI Lab | Design system | 1 | **Documents native contract** |

## 17. Why created / rejected

**Rejected: Calendar, DatePicker, DateRangePicker molecules**

1. **Zero** custom calendar/date-picker UI in V2.
2. **Zero** date UI libraries installed or imported.
3. **Zero** grid-based date ARIA or keyboard navigation.
4. **Frozen DateField/DateTimeField/TimeField** already encode the V2 strategy: **native pickers only**.
5. Date **ranges** in filters are **pairs of native inputs** — not a reusable range widget.
6. Adding react-day-picker (or similar) would be **speculative**, increase bundle/complexity, and **conflict** with current native-first frozen fields without adoption mandate.

**Accepted architecture:** **Native-first date/time fields**; optional future **DatePicker = Popover + Calendar** only after repeated custom UI in V2.

## 18. Final API contract (if created)

**N/A** — no new components.

**Existing (frozen reference):**

- **DateField:** standard Input props except `type`; optional wrapper `className`; `surface`, `error`, `inputClassName`.
- **DateTimeField / TimeField:** parallel for `datetime-local` / `time`.

## 19–22. Semantic / a11y / keyboard / interaction contracts (if created)

**N/A** for Calendar/DatePicker.

**Existing native fields:** semantic **`input`** with appropriate **`type`**; caller supplies **`aria-label`** or FormField label association.

## 23. State ownership contract

Caller owns **value**, **validation**, **form submit**, and **filter query**. DS fields do **not** own calendar state, parsing libraries, or timezone logic.

## 24. UI Lab route

**No new routes.** Existing:

- `/admin/design-system/molecules/date-field` (and datetime/time lab sections if present)

**N/A** for Calendar/DatePicker.

## 25. Documentation path

`docs/OURNEAR_CALENDAR_DATEPICKER_REVIEW_PR5S.md`

## 26. Files created

- `D:\Ournear Hub\Ournear Hire\docs\OURNEAR_CALENDAR_DATEPICKER_REVIEW_PR5S.md`

## 27. Files modified

**None**

## 28. V2 inspection status

**Complete (read-only)**

## 29. V2 modification status

**None**

## 30. Dependency changes

**None**

## 31. TypeScript result

**Not run** (documentation-only)

## 32. Build result

**Not run** (documentation-only)

## 33. Deviations

N/A

## 34. V2 migration / adoption status

**Not started.** V2 continues **Admin/ui Input** and inline `type="date"` where not yet on DateField. Future adoption = swap to **DateField**/**DateTimeField** — **not** introduce Calendar unless product adds custom picker requirements.

## 35. Stop condition

**PR-5S complete.** Do not start PR-5T. Do not install date libraries. Do not modify frozen DateField/DateTimeField/TimeField/Input/Popover/Dialog.
