# OurNear UI Lab (Stage 1)

Internal authenticated design-system laboratory inside Ournear Hire.

## Access

- **URL:** `/admin/design-system` (and nested paths)
- **Auth:** Same as admin panel (`admin.auth`, `admin`, `password.change` middleware)
- **Not in admin sidebar** — navigate directly while logged in as admin staff
- **SEO:** `noindex, nofollow` via Inertia `<Head>` on lab pages only

## Architecture

```text
resources/js/ui-lab/     → showcase only (imports library)
resources/js/Components/atoms/  → canonical components
resources/js/foundation/ → tokens
resources/js/icons/      → OurNearIcon + registry
```

## Lab coverage (PR-5AE)

The UI Lab documents frozen canonical **atoms** and **molecules** under `resources/js/Components/atoms/` and `molecules/`. Navigation and the overview **Current status** table live in `resources/js/ui-lab/metadata.ts`. **Text**, **Heading**, **Label**, **Badge**, and **StatusBadge** are demonstrated under **Foundation** (typography atoms), not separate atom routes.

Freeze inventory and PR-5 closure: [OURNEAR_CORPORATE_ARCHITECTURE_CLOSURE_PR5AE.md](./OURNEAR_CORPORATE_ARCHITECTURE_CLOSURE_PR5AE.md).

## Stage 1 sections (historical)

Initial delivery included only the routes below; additional lab sections were added in PR-3–PR-5.

| Route | Section |
|-------|---------|
| `/admin/design-system` | Overview |
| `/admin/design-system/foundation` | Tokens / foundation |
| `/admin/design-system/icons` | Icon registry browser |
| `/admin/design-system/atoms/button` | Button lab |
| `/admin/design-system/atoms/icon-button` | IconButton lab |

## Non-goals (Stage 1)

- No Admin layout/sidebar changes
- No Storybook
- No placeholder production components for deferred library items
