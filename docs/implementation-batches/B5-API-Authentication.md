# B5 â€” API Authentication and Account Access

Status: Accepted - backend API authentication regression scope; mobile-client lifecycle and runtime token-expiry configuration remain unverified.

## Purpose

Define a secure, maintainable authentication and account-access contract for supported mobile API clients while preserving the existing Laravel application, Inertia web authentication, and authorization rules.

Laravel remains the authoritative source for identity, authentication, authorization, validation, and business rules.

## Scope

1. Establish the existing API authentication mechanism from repository evidence.
2. Document login, authenticated account retrieval, logout, and applicable session/token lifecycle behavior.
3. Establish handling for invalid credentials, validation failures, unauthenticated requests, expired or invalid credentials, and forbidden operations.
4. Document candidate/employer account behavior under the unified login model.
5. Establish the minimum required API authentication contract and current implementation status.
6. Document the mobile client's responsibilities for credential handling, authentication state, expiry, logout, and API errors.
7. Identify necessary middleware, dependency, schema, and test changes before implementation.
8. Define focused acceptance tests and record evidence for each accepted behavior.

## Architecture constraints

- Preserve the existing Laravel application and domain services.
- Preserve existing Inertia web login, sessions, and authorization behavior.
- Retain the established single User model, dual admin/account guards, and permission architecture unless repository evidence identifies an approved requirement to change them.
- Use the Laravel API as the mobile boundary: Flutter -> OurNear API -> Laravel -> MySQL.
- Keep authentication and authorization enforcement on the server.
- Preserve unified candidate/employer account behavior.
- Do not introduce Firebase or Supabase as production authentication or domain backends.
- Do not replace existing mechanisms merely to adopt a different pattern.

## Explicit exclusions

- Rewriting web authentication or converting the web application/admin into an API-first SPA.
- Redesigning mobile screens or implementing unrelated mobile features.
- Unrelated candidate, employer, ATS, staffing, or admin functionality.
- Production infrastructure, Hostinger, Cloudflare, deployment, or environment changes.
- Editing secrets, .env files, or production credentials.
- Modifying generated public/build output.
- Broad or unrelated database migrations.
- Merging into main, develop, staging, or release branches without explicit authorization.

## Existing evidence and limitations

The project handover records these API calls as previously verified:

- GET /api/v1/jobs
- GET /api/v1/jobs/{slug}
- POST /api/v1/auth/login
- GET /api/v1/account
- POST /api/v1/auth/logout

This is historical endpoint-level evidence only. It does not independently prove complete authentication lifecycle behavior, secure client storage, token/session invalidation, authorization boundaries, or end-to-end mobile authentication.

The architecture specification records the existing Inertia-first web approach, Laravel source-of-truth principle, and dual admin/account guards. Current repository implementation must be distinguished from architectural intent and historical endpoint evidence.

## Readiness tasks

1. Locate the existing authentication routes, controllers, middleware, guards, and relevant tests using the narrowest required inspection.
2. Determine the actual API authentication mechanism before specifying token/session storage or invalidation.
3. Map each recorded endpoint to its current implementation and existing evidence.
4. Identify any missing behavior without inferring it from endpoint names.
5. Define request/response and error contracts from the actual implementation; propose changes only where a demonstrated gap requires them.
6. Confirm authorization expectations for candidate and employer access without weakening existing policies.
7. Identify mobile client integration requirements and dependencies.
8. Record focused acceptance criteria and required evidence.
9. Obtain explicit approval before implementation if a material architecture or scope change is needed.

## Acceptance criteria

- The actual authentication mechanism and its lifecycle are documented from repository evidence.
- Existing web authentication and session behavior remain preserved.
- Endpoint status distinguishes implemented, tested, partially implemented, and unverified behavior.
- Login, account retrieval, logout, expiry, invalid credentials, validation errors, and authorization outcomes are specified where applicable.
- Candidate/employer access follows the established unified identity and permission architecture.
- Any required implementation changes are narrowly scoped and justified.
- Focused tests cover the changed contract and relevant security boundaries.
- No production changes, unrelated modifications, or unsupported completion claims are made.
- Implementation and acceptance evidence are recorded before the batch is marked accepted.

## Implementation notes

No application-code implementation is authorized by this scope record alone. First establish readiness from existing code and evidence. Do not repeat broad audits or test suites when prior evidence remains sufficient.

## Current implementation inventory

Findings from source inspection and focused test execution in the B5 worktree. Runtime claims below are limited to the explicitly recorded test evidence.

### Authentication and account eligibility

- `MobileLoginRequest` accepts a `login` identifier, password, and optional device name. It trims the login value and falls back to the `email` input when `login` is absent.
- `AccountCredentialService` supports username or email lookup, checks the password hash, restricts account login to candidate and employer roles, and rejects accounts that cannot sign in.
- Failed login attempts are rate-limited and logged. Invalid credentials use a generic credentials message; account-status failures use the status-specific message.
- The login route has a named `account-login` throttle.
- Login issues a Laravel Sanctum personal access token with the `account:mobile` ability.
- Token expiry is read from `SANCTUM_TOKEN_EXPIRATION` through `config('sanctum.expiration')`. A positive numeric value results in an explicit token expiry; otherwise, the controller creates the token without an explicit expiry timestamp. The actual runtime configuration value remains unverified.

### Protected access and role boundaries

- Account endpoints use `auth:sanctum` and `api.account` middleware.
- The account middleware requires an eligible, active account member who can sign in; otherwise, it returns HTTP 401.
- Candidate-only endpoints use candidate-role middleware and return HTTP 403 when the authenticated user is not a candidate.
- Logout deletes the current access token.
- The existing implementation preserves Laravel session-based web authentication and the existing account/admin guard architecture.

### Existing test definitions identified

- Candidate registration and token issuance.
- Successful login and invalid credentials.
- Generic password-reset response for existing and nonexistent email addresses.
- Unauthenticated account access and authenticated account retrieval.
- Logout revocation, including rejection of the revoked token.
- Continued web-session login.
- Candidate endpoint access restrictions for employer accounts.
- Candidate resource ownership and email-verification restrictions in relevant candidate API tests.

These are existing test definitions found during source inspection, not evidence that the tests were executed during B5.

### Known unverified areas

- Actual runtime value and deployment configuration of `SANCTUM_TOKEN_EXPIRATION`.
- Scope decision: Flutter implementation and real-client end-to-end testing are deferred to the mobile workstream. B5 documents these responsibilities but does not claim client-side verification.
- Flutter token storage, restoration, expiry handling, and logout cleanup.
- End-to-end authentication behavior on a real mobile client.
- Broader authorization boundaries and real mobile-client authentication remain unverified unless supported by separate existing evidence.

No application-code changes are authorized by this inventory alone. Any implementation change must address a demonstrated gap, preserve web authentication, and remain narrowly scoped.
## Focused test execution evidence

- Test file: 	tests/Feature/Api/V1/MobileAuthApiTest.php
- Database: ournear_hire_test (dedicated MySQL test database)
- Result: **13 passed, 77 assertions, 0 failures**
- Verified cases include registration, duplicate identity validation, forgot-password generic response, valid and invalid login, protected account access, account summary, logout token revocation, non-active account login rejection, configured and unconfigured token expiry, expired bearer-token rejection, and preservation of web-session login.
- Candidate/employer authorization regression: `MobileCandidateApiTest::test_employer_token_cannot_access_candidate_endpoints` - **1 passed, 1 assertion**. An authenticated employer token received HTTP 403 from a candidate-only profile endpoint.
- The test run does not prove Flutter token storage/restoration or real-device end-to-end authentication.
- phpunit.xml was configured to use the dedicated MySQL test database; the database password was not added to tracked configuration.
- No application controller or production code was changed.

## Pending work

- [x] Establish the current API authentication mechanism from source inspection.
- [x] Map documented routes and behavior to existing test definitions.
- [x] Document the existing session/token lifecycle, including the unverified runtime expiry configuration.
- [x] Identify concrete backend authentication gaps covered by the focused regression tests; document remaining mobile-client and broader authorization limitations.
- [x] Limit the current change to focused regression tests and test-database configuration; no application-controller changes were made.
- [x] Add and run focused authentication regression tests against the dedicated MySQL test database: 13 passed, 77 assertions, 0 failures.
- [x] Record the scope decision: mobile-client implementation and real-client lifecycle verification are deferred to the mobile workstream; B5 records the client responsibilities and backend contract.

## Acceptance record

**Decision:** Accepted for the B5 backend API authentication regression scope.

**Execution evidence:**
- `MobileAuthApiTest`: 13 passed, 77 assertions, 0 failures.
- `MobileCandidateApiTest::test_employer_token_cannot_access_candidate_endpoints`: 1 passed, 1 assertion; employer access to a candidate-only endpoint returned HTTP 403.
- The focused tests preserve coverage of web-session login and verify token issuance, expiry behavior, protected account access, and logout revocation.

**Limitations retained:**
- The deployed/runtime value of `SANCTUM_TOKEN_EXPIRATION` has not been verified.
- Flutter token storage, restoration, expiry handling, logout cleanup, and real-client end-to-end authentication remain deferred to the mobile workstream.
- This acceptance does not represent a production deployment, a commit, or a push.

**Change boundary:** Changes are limited to the B5 documentation, PHPUnit test-database configuration, and focused authentication regression tests. No application-controller or production infrastructure changes were made.
