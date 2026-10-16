# OURNEAR Notification / toast / message architecture review — PR-5V

**Status:** Review complete — **no components beyond frozen Alert and Toast** (architecture phase — **V2 not modified**)

## 1. Decision

**ALERT / TOAST ARCHITECTURE COMPLETE — NO ADDITIONAL COMPONENT**

Frozen **Alert** (PR-4D) and **Toast** (PR-4E) remain the full Design-System surface for **transient and inline user feedback**. V2 does **not** justify **NotificationCenter**, **NotificationBell**, **Snackbar**, **global toast provider/queue**, or a separate **Inline Message** molecule.

**Persistent, server-backed notifications** (admin/account in-app notification records) are **domain/product UI** — documented here for boundary clarity, **not** canonicalized as DS organisms.

## 2. Architectural classification

| Layer | Role in V2 | DS component |
|-------|------------|--------------|
| **Inline status / validation** | Form errors, notices in page flow | **Alert** (canonical); many pages use ad-hoc styled `<p>` |
| **Transient feedback** | Not used in production V2 yet | **Toast** (UI Lab only; caller-owned, portaled) |
| **Session flash (Inertia)** | Laravel session → `props.flash` | **Transport**; rendered inline (Admin **FlashMessage**, page-local banners) — semantically **Alert**, not a new primitive |
| **Admin bell + recent list** | **TopBar** dropdown | Domain chrome — **one** implementation |
| **Admin notification inbox** | **Notifications/Index** — DataTable, filters, drawer | Domain admin page |
| **Account notification center** | **Account/Notifications** — paginated list, mark read | Domain account page |
| **Admin Messages** | Conversations, compose, reply | Domain messaging — **not** system notifications |
| **Snackbar / toast libraries** | **None** | N/A |

## 3. Evidence inspected (read-only)

**Frontend:** `D:\Ournear Hub\Ournear Hire\resources\js` — `rg` across Pages, Components, Layouts, ui-lab, `types/page.ts`, `package.json`.

**Key files read:** `Components/molecules/Toast.tsx`, `Components/molecules/Alert.tsx`, `Components/UI/Alert.tsx`, `Components/Admin/FlashMessage.tsx`, `Components/Admin/TopBar.tsx`, `Layouts/AdminLayout.tsx`, `Layouts/AccountLayout.tsx`, `Pages/Admin/Notifications/Index.tsx`, `Pages/Account/Notifications.tsx`, `Pages/Admin/Messages/Index.tsx`, `ui-lab/sections/ToastLabSection.tsx`, `ui-lab/sections/AlertLabSection.tsx`.

**Backend (shared props only):** `app/Http/Middleware/HandleInertiaRequests.php` — `flash`, `adminAlerts`, `accountAlerts`.

| Term / package | Hire app |
|----------------|----------|
| `react-hot-toast`, `sonner`, `react-toastify`, `notistack`, Radix toast | **0** |
| `Snackbar`, `NotificationCenter`, `NotificationBell` (component names) | **0** |
| **`Toast` import in production pages** | **0** (UI Lab + Design System catalog only) |
| **`Components/molecules/Alert` in production** | **0** (UI Lab only) |
| **`Components/UI/Alert` consumers** | **6** files |
| **`flash.*` usage in TSX** | **~26** files |
| **Inline success/error banner styling** (border + tone classes) | **~26** files (overlap with flash) |

## 4. Existing Toast findings

**Frozen contract (unchanged):** `Components/molecules/Toast.tsx`

- Portal to `document.body`; placement classes (`bottom-end` default, top/bottom start/end).
- Default **duration 5000 ms**; **`duration={0}`** disables auto-dismiss timer.
- **Caller-owned lifecycle:** `onDismiss` required when dismissible or timed (DEV warning).
- Variants: `info` | `success` | `warning` | `error`.
- **Semantics:** `error` → `role="alert"`; others → `role="status"`.
- Dismiss via **IconButton** “Dismiss”.
- **No** global provider, queue, or context in codebase.

**Consumers:**

| Consumer | Role |
|----------|------|
| `ui-lab/sections/ToastLabSection.tsx` | Demos variants, placement, duration; **single** `active` toast state (lab-only pattern) |
| `Pages/Admin/DesignSystem/Show.tsx` | Routes to lab section |

**Production V2:** **Does not import Toast.** Transient feedback is not yet implemented via Toast in live UI.

**Multiple toasts:** UI Lab holds **at most one** active toast; no V2 evidence of a product-level multi-toast stack.

## 5. Existing Alert findings

**Canonical (frozen):** `Components/molecules/Alert.tsx`

- Inline block; variants mirror Toast tones; optional title, icon, dismissible + `onDismiss`.
- Same **error → alert / others → status** role split as Toast.

**Legacy public adapter:** `Components/UI/Alert.tsx`

- Simpler API: `tone`, optional `title`; always **`role="status"`** (including error tone — legacy divergence from canonical Alert).
- **6 consumers:** e.g. `JobDetail`, `Apply`, `ApplicationConfirmation`, `ResumeReadinessPanel`, `ProfileModuleContinueBar`, `ErrorState`.

**V2 inline patterns (not using canonical Alert):**

- **Admin `FlashMessage`:** success / error / warning / status as styled `<p>` blocks in layout.
- **Account/public pages:** repeated `rounded-md border border-success/20 bg-success/10…` (often with `role="status"`) for `flash.success`.
- **Form errors:** `FormField`, `Public/FormField`, field-level `role="alert"` — validation, not Alert component.

**Dismissible alerts:** Canonical Alert supports dismissible; UI Lab demonstrates. Widespread V2 flash banners are **not** dismissible (cleared on next navigation).

**No** separate **Banner** or **Announcement** component — profile “banner” wording in filenames is layout chrome, not a notification primitive.

## 6. Flash / session message findings

**Transport:** Inertia shared `flash` in `HandleInertiaRequests`:

- `success`, `error`, `warning`, `status`
- Plus structured payloads: `application_confirmation`, `freelancer_confirmation`, `duplicate_matches`, OTP retry hints, etc.

**Rendering strategies (inconsistent, page-local):**

| Strategy | Where |
|----------|--------|
| **Admin layout global** | `AdminLayout` → **`FlashMessage`** (all admin pages) |
| **Inline tone paragraphs** | Account settings, resumes, reviews, admin freelancer show, etc. |
| **Dedicated confirmation UI** | `ApplicationConfirmation` + **`UI/Alert`** for `flash.application_confirmation` on job flows |
| **Plain text success** | Account notifications (`role="status"` only, minimal styling) |

Flash is **server round-trip state**, not a client notification service. **Do not** introduce `FlashProvider` or global toast mapping without product decision — DS already has **Alert** (persist until navigate) and **Toast** (explicit client dismiss/timer) for when adoption chooses UX.

## 7. Global notification UI findings

**Admin (`adminAlerts` shared prop):**

- Unread count + up to **8 recent** notifications.
- Rendered in **`TopBar`**: bell button, badge (9+ cap), dropdown `role="menu"`, “Mark all read”, links to items and **View all notifications**.

**Account (`accountAlerts` shared prop):**

- Unread count only (no recent list in shared props).
- **`AccountLayout`**: **BellIcon** **Link** to `/account/notifications` + dot badge (not a dropdown).

**No** global client notification context, queue, or WebSocket push UI evidenced in frontend survey.

## 8. Notification center findings

**Two distinct product surfaces — not a shared React component:**

1. **`Pages/Admin/Notifications/Index.tsx`**
   - Paginated **DataTable**, FilterBar (search, event, category, read state, dates, recipient).
   - Drawer detail for selected notification; recruitment chain context.
   - Unread/read **StatusBadge**; server-driven filters.

2. **`Pages/Account/Notifications.tsx`**
   - Page titled **“Notification center”**; card list (`<ul>` of `<li>`), pagination.
   - Mark read / open related page / mark all read; **EmptyState** when caught up.

**No** third reusable “notification list” molecule used elsewhere. Patterns differ (table vs cards, admin vs account actions). **Insufficient repeated generic contract** for **NotificationList** or **NotificationCenter** organism.

## 9. Notification trigger / bell findings

| Trigger | Implementation | Repeated? |
|---------|----------------|-----------|
| Admin bell + dropdown | **TopBar** only | **Single** |
| Account bell link | **AccountLayout** only | **Single** |

Different behavior (dropdown vs navigate). Emoji bell on admin vs **BellIcon** on account. **Not** a stable cross-surface **NotificationBell** molecule.

## 10. Notification library findings

`package.json`: `@inertiajs/react`, `@tabler/icons-react`, `react`, `react-dom` only.

**No** toast/notification npm packages installed or imported.

## 11. Accessibility findings

**Canonical Alert / Toast (frozen):**

- Variant-driven **`role="alert"`** vs **`role="status"`**.
- Toast dismiss control: **IconButton** with **“Dismiss”** label.

**V2 patterns:**

- Flash success often **`role="status"`** on inline `<p>`.
- Form errors: **`role="alert"`** on error text (FormField, OTP, apply flow).
- Admin TopBar bell: **`aria-label="Notifications"`**, **`aria-expanded`**, **`aria-haspopup="menu"`** on dropdown.
- Account bell: **IconButton** **`label="Notifications"`**.
- Loading/verification: selective **`aria-live="polite"`** (not notification-specific).

**No** evidence requiring a new a11y model beyond Alert/Toast + existing page patterns for domain lists.

## 12. Keyboard findings

**Toast (frozen):** Dismiss button focusable; no documented global shortcut stack.

**TopBar menus:** **Escape** closes notification and account menus; click-outside dismiss. Notification **links** activate via native link/button behavior — **no** roving listbox pattern for recent items.

**Account notification list:** Buttons for mark read / open — standard tab order; no arrow-key list navigation.

## 13. Responsive findings

- **Toast:** Placement utilities (corners); UI Lab documents portaled positioning — **no** separate mobile variant in component (viewport-relative fixed classes in design tokens).
- **Admin notification dropdown:** `w-80`, `max-h-80` scroll — desktop-oriented; mobile still uses same TopBar.
- **Account notifications:** Stacked cards, `min-h-11` touch targets, responsive flex on actions.

## 14. State ownership findings

| Concern | Owner |
|---------|--------|
| Toast visibility | **Caller** local state + `onDismiss` |
| Alert dismissible | **Caller** |
| Flash messages | **Laravel session** → Inertia **`flash`** → page/layout render |
| Admin recent + unread | **Server** (`adminAlerts` lazy shared prop) |
| Account unread badge | **Server** (`accountAlerts`) |
| Notification records | **Server** DB; pages mutate via **Inertia POST** (read, read-all) |
| TopBar dropdown open | **TopBar** local `useState` |

**No** `ToastContext`, **no** `NotificationProvider`, **no** client-side notification store.

## 15. Domain-vs-design-system classification

| System | DS? |
|--------|-----|
| Success/error after form POST (flash) | **Maps to Alert** (or Toast if client-only transient) — adoption gap only |
| Validation messages | **FormField** / inline errors — not Message molecule |
| Admin/account **in-app notifications** | **Domain** — hiring/recruitment notification product |
| **Messages** (conversations) | **Domain** — admin comms |
| Email templates / automations | **Backend + admin forms** — not notification UI primitive |

## 16. Relationship to frozen Alert

- **Alert** = inline, in-document-flow status; persistent until navigation or caller dismisses.
- V2 **should** converge ad-hoc flash banners and **FlashMessage** toward **canonical Alert** on adoption — **out of scope for PR-5V implementation**.
- **UI/Alert** legacy remains in public flows; migration to **molecules/Alert** is adoption, not a new component.
- **Do not** create **InlineMessage** — duplicate of Alert.

## 17. Relationship to frozen Toast

- **Toast** = transient, portaled, caller-owned; **not** Snackbar duplicate — **do not** add Snackbar.
- V2 does not use Toast in production; optional future: client-only actions (copy link, background save) — still **Toast**, not a provider.
- **Do not** add **ToastProvider/Queue** — no V2 evidence; lab uses single-toast state intentionally.

## 18. Candidate patterns

| Pattern | Verdict |
|---------|---------|
| Flash → inline colored box | Use **Alert** when adopting DS |
| Client transient feedback | Use **Toast** |
| Admin bell dropdown | Keep in **TopBar** (domain chrome) |
| Account bell → full page | Keep in **AccountLayout** + **Notifications** page |
| Admin notification inbox | Keep page + **DataTable** |
| Account notification cards | Keep page-local list |
| **UI/Alert** success confirmation | Adopt **molecules/Alert** later |

## 19. Why created / rejected

**Rejected — NotificationCenter, NotificationBell, NotificationList, Snackbar, Banner, Message, global providers:**

- Only **one** bell dropdown and **two** page-specific inbox UIs; layouts differ.
- Persistent notifications are **server records** with domain fields (recruitment chain, related entities) — not generic DS list items.
- Snackbar ≡ frozen **Toast**.
- Flash is **transport**; **Alert** already covers inline semantics.

**Rejected — modifying Alert/Toast:**

- PR-5V is discovery only; frozen contracts stand.

**Accepted — documentation only:**

- Clarifies **feedback (Alert/Toast)** vs **product notifications (domain pages)** vs **messages (domain)**.

## 20. Final API contract (if created)

**N/A** — no new components.

**Alert / Toast:** unchanged frozen APIs (see PR-4D / PR-4E docs and UI Lab catalogs).

## 21. Semantic contract (if created)

**N/A** for new components.

**Feedback:** Inline persistent → **Alert**; ephemeral overlay → **Toast**. Session flash → string or structured payload on **`SharedPageProps.flash`**, rendered as page content (prefer Alert when adopting DS).

**Product notifications:** HTTP resources under `/admin/notifications` and `/account/notifications` — not DS toast semantics.

## 22. Accessibility contract (if created)

**N/A** for new components. Continue frozen Alert/Toast role rules; domain pages keep existing labels and menu semantics.

## 23. Keyboard contract (if created)

**N/A** for new components.

## 24. Interaction contract (if created)

**N/A** for new components.

**Toast (reference):** Show via caller render; auto-dismiss or dismiss button → `onDismiss`. **Alert (reference):** Static in flow until navigate or optional dismiss.

## 25. State ownership contract

- **DS feedback:** caller-owned (Toast/Alert visibility).
- **Flash:** server session, consumed once per request cycle through Inertia.
- **Unread counts / lists:** server-owned; UI triggers POST then Inertia refresh.

## 26. Responsive contract

Documented in §13; no new DS responsive recipes beyond existing Toast placement tokens and page-local account/admin layouts.

## 27. UI Lab route (if created)

**N/A** — no changes. Existing **`molecules-alert`** and **`molecules-toast`** sections unchanged.

## 28. Documentation path

`docs/OURNEAR_NOTIFICATION_ARCHITECTURE_REVIEW_PR5V.md`

## 29. Files created

- `D:\Ournear Hub\Ournear Hire\docs\OURNEAR_NOTIFICATION_ARCHITECTURE_REVIEW_PR5V.md`

## 30. Files modified

**None**

## 31. V2 inspection status

**Complete (read-only)**

## 32. V2 modification status

**None** — frozen **Alert** and **Toast** untouched.

## 33. Dependency changes

**None**

## 34. TypeScript result

**Not run** (documentation-only — no implementation changed)

## 35. Build result

**Not run** (documentation-only — no implementation changed)

## 36. Deviations

None.

## 37. V2 migration / adoption status

**Not started.** Reasonable adoption paths (documented only):

- Map **FlashMessage** and repeated flash `<p>` banners → **molecules/Alert**.
- Map legacy **UI/Alert** → **molecules/Alert** (align error `role="alert"`).
- Introduce **Toast** only where **client-side transient** feedback is desired without full navigation.

**Do not** extract **NotificationCenter** unless a **third** repeated inbox UI with identical item contract appears.

## 38. Stop condition

**PR-5V complete. Stop. Do not start PR-5W. Do not begin V2 adoption/migration.**
