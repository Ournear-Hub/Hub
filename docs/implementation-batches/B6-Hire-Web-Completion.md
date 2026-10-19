\# B6-Hire-Web-Completion



\*\*Status:\*\* In progress — scope defined, implementation pending  

\*\*Priority:\*\* P0 release blockers first, then P1 browser acceptance  

\*\*Scope:\*\* Local web application and MySQL verification only. No production deployment or production migrations.



\## Scope



Complete and verify the remaining OurNear Hire web release requirements across Home, Hiring, Talent Pool, Staffing, Admin, and account navigation.



Preserve the existing Laravel API-first architecture, unified login, established business rules, design system, and completed Batch 3–7 functionality. Do not rebuild working features without a demonstrated defect.



\### In scope



1\. \*\*Release-critical migrations and showcase integration\*\*

&#x20;  - Verify the employer placement migration `2026\_10\_16\_210000`.

&#x20;  - Verify the showcase-to-employer-profile migration `2026\_10\_16\_220000`.

&#x20;  - Execute the relevant migrations against the local MySQL development database only, after confirming the target database.

&#x20;  - Run the previously unexecuted showcase-link regression test and relevant placement tests.

&#x20;  - Verify a showcase company can link to one canonical employer profile without incorrectly merging historical unlinked cards.



2\. \*\*Unified account and company activation\*\*

&#x20;  - Verify the personal `/account` dashboard does not display employer dashboard features.

&#x20;  - Verify company workspace and employer ATS functions remain under `/account/hiring`.

&#x20;  - Verify a user without a company is directed to the company-creation/activation flow when accessing employer functionality.

&#x20;  - Verify existing unified login and account navigation remain functional.



3\. \*\*Public web pages\*\*

&#x20;  - Verify Home, Hiring, Talent Pool, and Staffing routes and navigation.

&#x20;  - Verify company showcase placement and homepage statistics.

&#x20;  - Verify guest access to the public Staffing intake form.

&#x20;  - Verify existing Talent Pool and Staffing separation and established ownership rules.



4\. \*\*Employer Hiring and ATS\*\*

&#x20;  - Complete the browser walkthrough for the employer Hiring workspace.

&#x20;  - Verify job creation entry points, applicant stages, application timeline, and relevant employer access controls.

&#x20;  - Preserve the existing unverified-employer publishing policy.



5\. \*\*Admin and company management\*\*

&#x20;  - Verify branding/content management and showcase-company management in the browser.

&#x20;  - Verify company-profile linking and existing admin navigation.

&#x20;  - Assess legacy recruiter assignments for release impact without changing historical records unnecessarily.



6\. \*\*Profile and responsive release checks\*\*

&#x20;  - Reuse recorded responsive and profile-navigation evidence.

&#x20;  - Close remaining high-value gaps in photo upload, profile printing, and native print/PDF behavior where the browser environment permits.

&#x20;  - Document any browser-harness limitation separately from an actual application defect.



7\. \*\*Final release evidence\*\*

&#x20;  - Update the release issue register and current-state documentation.

&#x20;  - Record executed commands, test results, migration status, browser coverage, known limitations, and remaining blockers.

&#x20;  - Do not claim production readiness without sufficient evidence.



\## Tasks



\### B6.1 — Establish the release baseline

\- Preserve existing uncommitted changes.

\- Reuse existing evidence: public website browser tests (28 passed across two runs), recorded focused batch tests, TypeScript checks, and Vite builds.

\- Identify only the changes made after the historical full-suite run.

\- Confirm the active branch and local MySQL target before any migration or database-changing operation.



\### B6.2 — Verify migration and showcase regression

\- Inspect migration status for the two release migrations.

\- Confirm the database target is the local development database.

\- Apply any pending migration only to that confirmed local database.

\- Execute the showcase-company canonical-profile regression test and relevant placement tests.

\- Record results and resolve failures before continuing.



\### B6.3 — Accept unified account and company activation

\- Verify the personal account and employer workspace remain separated.

\- Verify the no-company entry path leads to company creation/activation.

\- Check existing login and access behavior for regressions.



\### B6.4 — Complete critical web browser acceptance

\- Verify Home, Hiring, Talent Pool, and Staffing navigation.

\- Verify employer Hiring/ATS.

\- Verify admin branding and showcase management.

\- Verify public guest Staffing intake.

\- Reuse prior passing evidence; rerun only flows that remain unverified or are affected by new changes.



\### B6.5 — Assess remaining profile and staffing gaps

\- Review photo upload and profile printing limitations.

\- Verify staffing submission-detail messaging only if suitable local test data exists; do not invent records or modify unrelated data to manufacture a pass.

\- Assess legacy recruiter assignment compatibility and record whether it blocks release.



\### B6.6 — Run regression and close documentation

\- Run focused tests for changed behavior first.

\- Run TypeScript and production frontend build if relevant changes warrant them.

\- Run the broader PHPUnit suite when the preceding changes are stable and the acceptance gate requires it.

\- Update the issue register and current-state document with new evidence.

\- Produce a final B6 acceptance report and explicitly list unresolved blockers.



\## Acceptance criteria



\### P0 — Required before B6 acceptance



\- \[ ] The two release migrations are verified against the intended local MySQL database.

\- \[ ] Showcase canonical-profile linking regression test passes.

\- \[ ] Relevant employer placement tests pass.

\- \[ ] Personal account and employer workspace separation is verified.

\- \[ ] The no-company employer entry path is verified.

\- \[ ] Home, Hiring, Talent Pool, and Staffing critical routes are accepted.

\- \[ ] Public guest Staffing intake remains accessible.

\- \[ ] Employer Hiring/ATS critical browser walkthrough passes.

\- \[ ] No new critical regression remains unresolved.

\- \[ ] TypeScript and Vite build pass if affected by changes.

\- \[ ] Release issue register and current-state documentation reflect actual results.



\### P1 — Complete or explicitly disposition



\- \[ ] Admin branding and showcase management browser acceptance.

\- \[ ] Relevant staffing submission-detail messaging journey, where local data permits.

\- \[ ] Legacy recruiter assignment compatibility assessed.

\- \[ ] Photo upload and print/PDF limitations resolved or documented with evidence.

\- \[ ] Responsive behavior verified or existing passing evidence referenced.



\### Release decision



B6 may be marked \*\*Accepted\*\* only when all P0 criteria pass or an explicitly authorized exception is documented. A failed, blocked, or unexecuted check must not be reported as passed.



Local acceptance does not establish staging or production acceptance. Production readiness requires a separate deployment and environment-verification gate.



\## Implementation notes



\- Laravel remains the source of truth; the existing API-first architecture must be preserved.

\- MySQL is the intended development/test database for this work. Do not silently switch to SQLite.

\- Preserve the current working tree and all user modifications.

\- Do not reset, clean, discard, commit, push, deploy, or run production migrations without explicit authorization.

\- Do not repeat completed test runs without a concrete reason.

\- Reuse existing evidence where it still applies. Historical full-suite results do not automatically cover subsequent code or migration changes.

\- Existing public website evidence: 28 passed, 0 failed across two runs. These tests do not replace employer ATS, admin, migration, or profile acceptance.

\- Historical full-suite evidence: 1,156 passed, 3 skipped, 0 failed; this predates the recorded showcase-link change and is not proof that the latest change passes.

\- If a browser flow is blocked by missing local data or a native OS dialog, record the exact limitation and use focused automated tests where appropriate.

\- Keep Talent Pool job creation in Admin, preserve the established company ownership model, and keep Talent Pool and Staffing as separate workflows.

\- Maintain the established brand palette: dark teal `#214449`, orange `#F15A24`, and white.

\- Do not treat staging or production as verified without direct evidence from those environments.



\## Pending work



1\. Inspect migration status and confirm the local MySQL database target.

2\. Run the showcase-link regression test and relevant placement tests.

3\. Verify the personal-account/company-creation flow.

4\. Complete the employer Hiring/ATS and admin showcase browser walkthroughs.

5\. Assess remaining profile, print, staffing-detail, and legacy-assignment gaps.

6\. Update the release issue register and issue the B6 acceptance verdict.



