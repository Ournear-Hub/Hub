# OURNEAR ConfirmDialog / AlertDialog review — PR-5K

**Status:** Review complete — **ConfirmDialog NOT created** (architecture phase — **V2 not modified**)

## 1. Decision

**ConfirmDialog was NOT created** as a design-system molecule.

Confirmation flows remain a **composition use case** of the frozen **Dialog** molecule (`Components/molecules/Dialog.tsx`).

V2 **`Components/Admin/ui/ConfirmDialog.tsx`** stays unchanged (production wrapper over V2 Modal).

## 2. Architectural classification

| Class | Finding | Verdict |
|-------|---------|---------|
| **A — Canonical Dialog (DS)** | General modal shell — frozen PR-5H | **Sufficient for confirmation UI** |
| **B — Confirmation inside Dialog/Modal** | Title + description + Cancel/Confirm footer | **Primary adoption pattern for DS** |
| **C — Dedicated ConfirmDialog** | V2 `Admin/ui/ConfirmDialog.tsx` — thin Modal wrapper | **V2 convenience only; not distinct DS primitive** |
| **D — Domain-specific confirmations** | Jobs delete/deactivate, MasterData activate/deactivate, ApplicationDrawer multi-action titles, Placements accept/withdraw, Finance issue invoice, etc. | **Caller-owned copy + router actions** |
| **E — Native `window.confirm`** | ~10 account/admin call sites | **Legacy/native; not DS** |
| **F — AlertDialog (`role="alertdialog"`)** | **Not found** in `resources/js` | **No AlertDialog molecule** |

## 3. Evidence inspected (read-only)

| Source | Finding |
|--------|---------|
| `Components/Admin/ui/ConfirmDialog.tsx` | Wraps V2 `Modal`; props: `open`, `title`, `description`, `confirmLabel`, `cancelLabel`, `danger`, `onConfirm`, `onClose`; internal **`busy`** during async `onConfirm`; footer = secondary Cancel + primary/danger Confirm; body = plain description string. |
| `Components/Admin/ui/Modal.tsx` | `role="dialog"`, `aria-modal="true"`, static `aria-labelledby`; Escape; no focus trap. |
| V2 `ConfirmDialog` imports | **9 files**: ApplicationDrawer, ApplicationWorkspacePage, Finance/Index, Jobs/Index, MasterData/Index, Placements/Index, Staff/Index, Submissions/Index, Users/Show. |
| `<ConfirmDialog` JSX | **9** component instances (one per importer; ApplicationDrawer may use one block with many domain titles). |
| `window.confirm` | **10** usages (Account profile/documents/applications/jobs; Admin Employers document delete, MediaSocial asset delete, CmsEditor discard). |
| `role="alertdialog"` | **0** matches (recursive search on `resources/js`). |
| `AlertDialog` / `ConfirmationDialog` component names | **None** in app tree. |
| DS `Dialog.tsx` | Unchanged; supports `title`, `description`, `footer`, `onClose` — covers confirmation layout without a second shell. |
| Search method | PowerShell `Get-ChildItem -Recurse` + `Select-String` on `D:\Ournear Hub\Ournear Hire\resources\js`. |

## 4. Candidate patterns

| Pattern | Where | Verdict |
|---------|--------|---------|
| V2 **ConfirmDialog** | Admin/ui + 9 consumers | **Reusable V2 helper** — same shell as Modal, preset footer + busy |
| V2 **Modal** + custom footer | Placements (offer), Finance (payment), Employees (forms), etc. | **Dialog use case** — forms/tasks, not ConfirmDialog-shaped |
| DS **Dialog** + footer (lab) | `DialogLabSection.tsx` documents caller-owned confirmation | **Target DS pattern** |
| **`window.confirm`** | Account + scattered admin | **Native** — out of scope for molecule |
| Multi-title **ConfirmDialog** in ApplicationDrawer | Single component, discriminated `confirm` union | **Domain logic in caller** — titles/descriptions vary by workflow |
| **danger** prop | Jobs, MasterData deactivate, some Staff flows | **Button variant in footer** — caller/compose via Dialog |

## 5. Why rejected (not created)

1. **No semantic distinction from Dialog** — V2 ConfirmDialog uses the same **modal dialog** surface as Modal/Dialog (`role="dialog"`, not `alertdialog`).
2. **No distinct interaction contract** — Escape, backdrop, focus behavior identical to Dialog; no focus trap or alertdialog keyboard model evidenced.
3. **Shell = Dialog + footer** — Stable visual (max-w-md, footer right-aligned Cancel + action) is already expressed by Dialog’s `description` + `footer` slots.
4. **V2 `busy` state is workflow** — async guard on confirm is **business/UI state**; DS molecules must not own it (PR-5K state ownership).
5. **Domain variance** — ApplicationDrawer alone encodes many confirmation **copy** branches; the reusable part is Modal shell, not a separate primitive.
6. **PR-5H precedent** — ConfirmDialog explicitly deferred as use case; PR-5K evidence confirms that decision.

Creating `ConfirmDialog.tsx` would duplicate Dialog with a fixed footer API and invite duplication with frozen Dialog without migration benefit.

## 6. Dialog relationship

| Layer | Rule |
|-------|------|
| **Dialog (DS)** | General-purpose blocking modal shell. |
| **Confirmation** | Compose `Dialog` with `title`, optional `description`, and `footer` (Cancel + Confirm/destructive **Button**). |
| **V2 ConfirmDialog** | Production helper until adoption; maps conceptually to Dialog composition, not a second DS molecule. |

Do **not** add: `DialogConfirm`, `ConfirmationModal`, `AlertModal`, `ModalConfirm`.

## 7. Semantic contract

- **Observed:** `role="dialog"` + `aria-modal="true"` (via V2 Modal → DS Dialog).
- **Not observed:** `role="alertdialog"`.
- **DS rule:** Confirmations use **Dialog** semantics unless a future review finds repeated, evidence-backed **alertdialog** requirements.

## 8. Accessibility contract

| Feature | V2 ConfirmDialog / Modal | DS Dialog |
|---------|--------------------------|-----------|
| Role | `dialog` | `dialog` |
| Name | `aria-labelledby` (static id in V2 Modal) | `useId` title id |
| Description | Body text only (no `aria-describedby` in V2 ConfirmDialog) | Optional `description` → `aria-describedby` |
| Escape | Yes | Yes |
| Focus trap | No | No |
| Alertdialog | No | N/A |

## 9. Keyboard contract

Native **button** activation (Enter/Space) on Cancel/Confirm; Escape closes via modal listener. No alertdialog-specific Tab cycle evidenced.

## 10. Interaction contract

**Caller owns:** `open`, which action is confirming, `onConfirm` (often `router.post/delete`), closing after success, error handling, and any **loading/disabled** state on buttons.

**Dialog owns:** overlay shell, Escape, backdrop dismiss, ARIA wiring.

V2 ConfirmDialog **incorrectly centralizes busy** for DS purposes — future adoption should keep busy on caller buttons or explicit caller state.

## 11. Visual contract

V2 ConfirmDialog inherits Modal: centered `max-w-md`, footer `flex justify-end gap-2`, Cancel **secondary**, Confirm **primary** or **danger** when `danger={true}`. No icon/warning banner pattern in the shared component. Order: Cancel then Confirm (destructive on the right).

## 12. State ownership

| State | Owner |
|-------|--------|
| Open / which confirm flow | Caller (`confirm !== null`, `statusTarget`, etc.) |
| Title / description / labels | Caller (domain copy) |
| `onConfirm` / API | Caller |
| `busy` during confirm (V2) | V2 ConfirmDialog implementation — **not** DS |
| Modal open/close mechanics | Dialog / V2 Modal |

## 13. Final API

**Not created.**

**Recommended DS composition (future adoption):**

```tsx
<Dialog
  open={open}
  onClose={onClose}
  title="Delete this job?"
  description="Jobs with applications cannot be deleted. This cannot be undone."
  footer={
    <>
      <Button surface="admin" variant="secondary" onClick={onClose} disabled={busy}>
        Cancel
      </Button>
      <Button surface="admin" variant="destructive" onClick={handleConfirm} disabled={busy} loading={busy}>
        Delete
      </Button>
    </>
  }
/>
```

(Exact Button props follow existing atom API; `busy` stays caller-owned.)

## 14. UI Lab route

**Not created.** Existing Dialog lab includes a “Confirmation pattern (caller-owned)” panel.

## 15. Files created

- `docs/OURNEAR_CONFIRM_DIALOG_REVIEW_PR5K.md`

## 16. Files modified

**None** (frozen Dialog, Drawer, Accordion, etc. untouched)

## 17. Dependencies

**None**

## 18. TypeScript result

**Not run** — documentation-only PR.

## 19. Build result

**Not run** — documentation-only PR.

## 20. Deviations

N/A — no DS component.

## 21. V2 untouched confirmation

**Confirmed** — no V2 files modified.

## 22. Migration / adoption confirmation

**Not started.**

**Future rule:** Replace V2 `ConfirmDialog` with **`Dialog` + footer** when adopting DS in admin; do not introduce a parallel `ConfirmDialog` molecule unless new evidence shows a contract **materially beyond** Dialog (e.g. standardized alertdialog with proven a11y requirements).

## Explicit non-goals (PR-5K)

- ConfirmDialogProvider / promise-based confirm / global modal store
- AlertDialog molecule without `role="alertdialog"` evidence
- Modifying frozen `Dialog.tsx`
- Migrating V2 ConfirmDialog callers
