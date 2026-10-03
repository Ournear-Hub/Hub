# Frontend architecture

## Stack

React 19 + TypeScript + Inertia 3 + Vite 7 + Tailwind 4. Entry: `resources/js` (see `vite.config`).

## Layouts

- Public: `PublicLayout`
- Account: `AccountLayout`
- Admin: `AdminLayout` + `Sidebar.tsx` (permission-gated links; grouped sections e.g. People; hiring/analytics items above groups)
- Admin auth: `AdminAuthLayout`

## Pages

`resources/js/Pages/{Home,Jobs,Companies,Platform,Account,Admin,Profiles,Legal,...}`. Inertia component names match these paths (e.g. `Companies/Index`, `Platform/Hire`, `Platform/TalentPool`, `Platform/Staffing`, `Admin/Hiring/CommandCenter`). Public primary nav is `resources/js/content/navigation.ts`. Brand lockup: `BrandMark` (`surface="admin"` omits the Hire product label). Admin sidebar sections keep existing permission names.

## Components

`resources/js/Components/Admin/ui` (Button, DataTable, Drawer, StatusBadge, …), Account panels (OTP), Profile, Documents, Resume.

## Types

`resources/js/types/*.ts` — applications, jobs, layout, hiring. Prefer updating types when Inertia props change.

## State

No global client store. Inertia page props + `useForm` + `router`. Flash via shared HandleInertiaRequests.

## Patterns / debt

- Large workspace pages (`ApplicationWorkspacePage`, employer/admin indexes).
- Duplicate Hostinger copy under `ournear_app_Hostinger_2026-09-19/resources` — **do not edit as live UI**.
- Loading: Inertia visits; not a dedicated React Query layer.
- Empty/error: page-level copy + validation errors from Laravel.

## Mobile reuse

No FlutterFlow in repo. Reuse is via future API (does not exist) or shared types — [PLANNED].

## Checks

`npx tsc --noEmit`, `npm run build`.
