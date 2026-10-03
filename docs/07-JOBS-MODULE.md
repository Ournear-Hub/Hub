# Jobs module

**Model:** `App\Models\Job` / table `job_listings`.

## Publishing (`JobPublishingStatus`)

`draft`, `published`, `paused`, `closed`, `archived`. Public listing: `isListedPublicly()` → published.

## Who creates jobs

- Admin: `Admin\JobsController` (`admin.jobs.*`), lifecycle patch.
- Employer: `account.employer.jobs.*` if posting allowed.

## Public

`GET /jobs`, `GET /jobs/{slug}`, apply GET/POST, share POST. `PublicJobStateService` for apply/save state.

## Search / filter / sort

Admin and public controllers apply query objects (`Job` queries / `UserQuery` patterns). No Elasticsearch. [NOT VERIFIED] every filter combination.

## Recommendations

No dedicated recommendation engine. Matching is **application vs job** via `ScreeningMatchService`, not a job-for-you feed.

## Ownership

`employer_id` on job_listings. Unassigned jobs possible until linked.

## Tests

`JobsWorkspaceTest`, `JobsAtsFoundationStage5Test`, public job tests in `PublicWebsiteTest` / `CorrectionPassTest`.

## Related

ATS foundation migration `2026_09_27_220000_add_job_ats_foundation.php` (may be pending on some local DBs).
