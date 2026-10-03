# Documentation gaps

| Feature | Problem | Evidence | Recommended doc | Priority |
| --- | --- | --- | --- | --- |
| `V1.1_DEVELOPMENT_STATE.md` | Stale vs Stages 1–26 | File still says A.9 / V1.2 portal | Keep CURRENT-STATE as live; refresh V1.1 file in a later doc-only pass | High |
| Per-table column catalog | Not every column listed | 50+ migrations | Expand 04 only when a table is being changed | Medium |
| Every admin route row | 11 is grouped, not exhaustive | `routes/admin.php` length | Use `php artisan route:list` when needed | Medium |
| CommunicationPreference usage | Uncertain on all mail paths | Service exists; grep incomplete historically | Confirm before changing mail | Medium |
| Production host/CI | Not in repo | No workflow file found | Fill when CI is added | Low |
| Pipeline allowed transition matrix | Not copied from PHP | `ApplicationPipelineService` | Read service before changing statuses | High for ATS work |
| Flutter/mobile | Absent | No FF project | Do not document as implemented | — |
| Live Meta WhatsApp | Unconfigured typical | config default | Integrations doc | — |

Second-pass (docs 24–25): Hiring Command Center, Hire `person_number` / `ON-HIR` display, bulk resume import, public `/companies` `/hire` `/talent-pool` `/staffing` were implemented in code but only sketched in 06/09/22 — now have dedicated or expanded docs.

Still thin (intentionally): exhaustive admin `route:list`, every MySQL column, pipeline transition matrix (read `ApplicationPipelineService`).
