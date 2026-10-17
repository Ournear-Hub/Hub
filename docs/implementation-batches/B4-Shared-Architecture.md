# B4-Shared-Architecture

Status: Documentation scope defined; implementation pending acceptance.

## Scope

Establish a traceable, non-destructive shared architecture contract for OurNear Hire and the wider OurNear platform, based on the existing authoritative documents:

- `docs/OURNEAR_UNIFIED_PLATFORM_ARCHITECTURE_v1.1.md`
- `docs/OURNEAR_CORPORATE_LIBRARY_SPECIFICATION_v1.0.md`
- `docs/OURNEAR_HIRE_FRONTEND_ARCHITECTURE_AUDIT.md`
- `mobile/docs/mobile/OURNEAR_HIRE_MOBILE_ARCHITECTURE.md`

This batch formalizes architecture boundaries and governance. It does not authorize application-code changes.

## Tasks

1. Record Architecture v1.1 and Corporate Library Specification v1.0 as the governing architecture and UI-library contracts.
2. Document the dependency direction: foundation/tokens/icons, UI layers, systems, features, shells, and pages.
3. Preserve Laravel as the authoritative backend for domain data, business rules, and authorization.
4. Document the web/mobile boundary: shared semantic contracts only; React components are not shared with Flutter.
5. Preserve existing V2 behavior and existing product shells. Do not merge navigation trees or perform broad folder moves.
6. Keep authentication/API implementation, domain migrations, and new application features outside this batch.
7. Record unresolved architecture conflicts as explicit decisions required; do not silently alter governing specifications.
8. Maintain a clear distinction between documented architecture and implemented behavior.

## Acceptance criteria

- The governing documents and their roles are explicitly recorded.
- Dependency direction and prohibited dependencies are documented without contradicting the governing specifications.
- Laravel authority and web/mobile separation are explicit.
- Existing V2 behavior and phased migration requirements are preserved.
- Batch 5 API authentication and later implementation phases are explicitly excluded.
- No application source, migrations, dependencies, environment files, production settings, or generated build output are changed by this documentation task.
- The batch status is not marked complete until the documentation change is reviewed and its evidence is recorded.

## Implementation notes

- Architecture source: `docs/OURNEAR_UNIFIED_PLATFORM_ARCHITECTURE_v1.1.md`.
- UI library contract: `docs/OURNEAR_CORPORATE_LIBRARY_SPECIFICATION_v1.0.md`.
- The library specification is a contract, not standalone authorization to modify code.
- Preserve existing V2 behavior until an intentional, phased implementation is approved.
- No mass moves, broad rewrites, backend replacement, microservice split, or API-first web-admin rewrite.
- Do not claim implementation, test, CI, or release success without current evidence.

## Pending work

- Review this scope against the governing documents.
- Record any specific contradiction that requires a product/architecture decision.
- Obtain approval before expanding into component implementation.
- Record review evidence and commit this batch only after the scoped documentation change is accepted.

## Review evidence

- Scope commit: `e967787` (`Define shared architecture batch scope`).
- Governing architecture reference: `docs/OURNEAR_UNIFIED_PLATFORM_ARCHITECTURE_v1.1.md`.
- Governing UI-library reference: `docs/OURNEAR_CORPORATE_LIBRARY_SPECIFICATION_v1.0.md`.
- The scope preserves phased implementation, existing V2 behavior, Laravel backend authority, and the React/Flutter boundary.
- No application-code implementation is authorized by this batch document.
- Review outcome: no scope-level contradiction is currently identified from the reviewed architecture requirements.
- Remaining gate: confirm the review evidence and obtain explicit approval before expanding into implementation.
- Batch status: pending acceptance; not complete.
