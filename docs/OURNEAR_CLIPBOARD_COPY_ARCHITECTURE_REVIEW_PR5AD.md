# OurNear Clipboard & Copy Interaction Architecture Review (PR-5AD)

## Objective and scope

PR-5AD reviews **clipboard write and copy-link interactions** in OurNear Hire and decides whether the frozen Design System needs a shared utility or molecule beyond existing **Button**, **IconButton**, **Tooltip**, **Toast**, and **Alert**.

**In scope:** Read-only inspection of `navigator.clipboard`, copy buttons, paste handlers tied to copy UX, and related domain side effects (share analytics).

**Out of scope:** Re-opening PR-5AA–PR-5AC or other frozen DS areas; V2 adoption/migration; page refactors; new npm clipboard libraries; PR-6A.

**Decision summary:** **No new DS component and no new shared utility in PR-5AD.** Copy behavior is **page-owned** in two production modules with a **small, duplicated** `writeText` + `window.prompt` fallback. That repetition is insufficient for a stable corporate contract without migrating consumers. Future consolidation belongs in a **domain or adoption PR**, not the Design System layer.

---

## Evidence inspected

| Item | Path / finding |
|------|----------------|
| Clipboard **write** (production) | `resources/js/Components/Public/JobActions.tsx` |
| Clipboard **write** (production) | `resources/js/Components/Profile/ProfileShareMenu.tsx` |
| Clipboard **read** (paste) | `resources/js/Components/Account/Verification/OtpSixInput.tsx` — OTP digit paste via `clipboardData.getData('text')` |
| UI Lab (labels only) | `ui-lab/sections/TooltipLabSection.tsx`, `PopoverLabSection.tsx` — “Copy link” / “Copy candidate ID” **without** `writeText` |
| Icons | `icons/registry.ts` — `clipboard` icon (nav/decorative); legacy `Components/UI/icons.tsx` `ClipboardIcon` on Account mobile nav |
| Domain enum | `app/Enums/ShareChannel.php` — `Copy = 'copy'` for job share channel logging |
| Backend (non-React clipboard) | `WhatsAppVerificationService.php` — WhatsApp OTP template “copy button” via Cloud API config, not `navigator.clipboard` |
| Dependencies | `package.json` — **no** `copy-to-clipboard`, `clipboard-polyfill`, or similar |
| Shared helpers | **No** `resources/js/lib/*clipboard*`, **no** `useCopy*` hook under `resources/js` |

### Search methodology

Ripgrep on `resources/js` and relevant `app/` for: `clipboard`, `writeText`, `readText`, `execCommand`, `Copy link`, `copyLink`, `clipboardData`.

---

## Existing clipboard implementations

### 1. Job share — copy link (`JobActions.tsx`)

**Trigger:** Text button “Copy link” on job card/detail (not list variant).

**Mechanics:**

```tsx
await navigator.clipboard.writeText(shareUrl);
// on failure: window.prompt('Copy job link', shareUrl);
```

**URL construction:** `window.location.origin` + path, optional `?ref=` referral code — **domain logic**.

**Side effects:** Always calls `router.post('/jobs/{slug}/share', { channel: 'copy' })` after the try/catch (clipboard success or fallback), **preserveScroll/state** — analytics, not DS.

**Feedback:** **Silent** on success; prompt dialog on clipboard API failure.

**Related:** Optional `navigator.share` with separate `recordShare('native')`.

### 2. Profile share — copy link (`ProfileShareMenu.tsx`)

**Trigger:** “Copy link” inside modal share sheet.

**Mechanics:** Same `writeText` + `prompt('Copy profile link', absoluteUrl)` pattern.

**URL construction:** `origin + profileUrl` prop — **domain**.

**Side effects:** None on copy (no share POST).

**Feedback:** Silent success; prompt on failure.

**Related:** WhatsApp, email, native share — separate actions, not clipboard.

### 3. OTP paste (`OtpSixInput.tsx`)

**Concern:** **Input paste normalization** (six-digit OTP), not “copy to clipboard” product feature. Stays **field-owned**; no DS abstraction required.

### 4. UI Lab demonstrations

Tooltip/Popover examples use **copy-oriented labels** for documentation only. They do **not** implement clipboard APIs and must not be treated as canonical copy components.

---

## Candidate contracts and decisions

| Candidate | Evidence | Decision |
|-----------|----------|----------|
| **`CopyButton` / `CopyToClipboard` molecule** | Two inline `<button>` implementations; different shells (inline vs modal); job copy has analytics POST | **Reject** — would duplicate **Button** without stable props; domain hooks differ |
| **Shared `copyTextToClipboard(text)` utility** | Identical try/catch/prompt in two files only; no third consumer; job flow adds POST after copy | **Reject for PR-5AD** — acceptable **page-level duplication** until a migration PR extracts a **non-DS** `lib/` helper and updates both call sites together |
| **Toast/Alert on success** | Neither production copy flow shows Toast; PR-5V Toast is general-purpose | **Reject export-specific molecule** — pages may compose **Toast** when product requires explicit confirmation |
| **Clipboard provider / queue** | Not present; not needed | **Reject** |
| **Paste helper for OTP** | Single component | **Keep field-local** |

### Recommended pattern (documentation-only guidance)

When adding new copy actions (invite codes, reference IDs, admin snippets):

1. **Presentation:** **Button** or **IconButton** with visible text or `aria-label` (not icon-only without label).
2. **Mechanics:** Prefer `navigator.clipboard.writeText` in a **user gesture** handler; on rejection/unavailability, use **`window.prompt`** or show the value in a **readonly Input** + manual select — match existing Hire fallback.
3. **Feedback:** Optional **Toast** (“Link copied”) — product choice; do not assume success.
4. **Domain:** URL building, share logging, permissions stay in the **page or controller**, not DS.

---

## Permission, failure, feedback, and accessibility

| Topic | Finding |
|-------|---------|
| **Secure context** | Clipboard API requires HTTPS (or localhost); production Hire assumes secure deployment |
| **Permission** | No explicit `permissions.query`; failures handled by **try/catch** → prompt |
| **User gesture** | Copy handlers run on **click** — correct for API availability |
| **Success feedback** | Currently **none** in production — users infer from lack of error; acceptable but improvable via Toast at page level |
| **Failure feedback** | **Prompt** pre-filled with text — accessible fallback, dated UX but consistent |
| **Labels** | “Copy link” text buttons — accessible; profile sheet has dialog title and close control |
| **Tooltip “Copy …” in UI Lab** | Tooltip on a button that does not copy — lab-only; do not copy that pattern for real copy actions |

---

## State and responsibility boundaries

| Layer | Responsibility |
|-------|------------------|
| **Browser** | Clipboard read/write, native share sheet |
| **Page / domain component** | What to copy, when, analytics (`JobActions` share POST), modal UX (`ProfileShareMenu`) |
| **Design System** | **Button**, **IconButton**, **Tooltip**, **Toast**, **Dialog**-like patterns (profile uses bespoke modal, not DS Dialog migration in this PR) |
| **Backend** | Share channel enum, WhatsApp template copy button — outside React clipboard |

DS must **not** own referral URLs, profile URLs, or share logging.

---

## Dependencies

**No clipboard-specific npm packages.** Native **`navigator.clipboard`** and **`window.prompt`** only.

---

## Files created and modified (PR-5AD)

| Action | Path |
|--------|------|
| **Created** | `docs/OURNEAR_CLIPBOARD_COPY_ARCHITECTURE_REVIEW_PR5AD.md` |
| **Modified** | *(none — application code unchanged)* |

**Application code changed:** **No**  
**Dependencies changed:** **No**  
**V2 consumers changed:** **No**

---

## Verification

| Check | Result |
|-------|--------|
| TypeScript (`npm run typecheck`) | **Not run** — documentation-only |
| Production build (`npm run build`) | **Not run** — documentation-only |

---

## V2 isolation and adoption status

| Item | Status |
|------|--------|
| V2 inspection | Read-only; no separate V2 clipboard SDK or shared hook found beyond same public/profile patterns |
| V2 modification | **None** |
| V2 adoption / migration | **Not started** |

---

## Stop condition

PR-5AD complete. **Do not start PR-6A.** **Do not begin V2 adoption or migration.**
