# OurNear Hire — source release preparation (2026-10-17)

**Status:** plan only. No commit, push, deploy, history rewrite, or copy into the Git checkout was performed.

**Sources inspected**

| Location | Role |
| --- | --- |
| `C:\Ournear Hub\Ournear Hire` | Working Laravel app. Not a Git repository. Contains `.env` (must stay local). |
| `C:\Ournear Hub\Hire-Remote-Audit` | Existing clone of `https://github.com/ournear/Hire.git`. Clean `main` at `a291804`, tracking `origin/main`. |
| `C:\Ournear Hub\ournear_mobile` | Flutter app. Not a Git repository. |

**Remote branches present locally:** `origin/main`, `origin/staging-baseline-2026-10-07`. Neither branch contains a `.github/` directory. There is no GitHub Actions workflow file in this clone to preserve or replace. If one is added on GitHub later, do not delete it as part of this release.

## Source to add or update

Copy application source from the working Laravel folder onto a **new branch** created from `origin/main`. Do not edit `main` or `staging-baseline-2026-10-07`.

Path-level difference versus the Git checkout (stat only; not a file-by-file reprint):

| Path | Difference |
| --- | --- |
| `app/` | 310 files, about +22,962 / −1,042 lines |
| `routes/` | 5 files, +687 / −63 |
| `database/migrations/` | 21 files present only in the working app (list below) |
| `config/` | 3 files, including new `sanctum.php` and `sharing.php` |
| `resources/js/` | 429 files, about +40,510 / −5,276 |
| `resources/css/app.css` | small change |
| `tests/` | 137 files |
| `docs/` | 232 files (includes QA screenshots) |
| `composer.json`, `package.json`, `vite.config.js`, `phpunit.xml`, `AGENTS.md` | small updates |

**Migrations only in the working app**

- `2026_10_09_160000_create_personal_access_tokens_table.php`
- `2026_10_10_120000_add_recruitment_self_service_support.php`
- `2026_10_11_040000_add_job_listing_skills_and_qualifications.php`
- `2026_10_11_120000_create_master_data_import_previews_table.php`
- `2026_10_11_120000_create_message_templates_tables.php`
- `2026_10_11_130000_enhance_resume_import_items.php`
- `2026_10_11_140000_add_mail_delivery_settings_to_website_settings_table.php`
- `2026_10_12_000001_create_job_search_events_table.php`
- `2026_10_13_210000_add_logo_path_to_employer_profiles_table.php`
- `2026_10_14_120000_create_recruiter_talent_pool_assignments.php`
- `2026_10_14_180000_add_listing_priority_to_job_listings_table.php`
- `2026_10_14_200000_extend_recruiter_talent_pool_assignments.php`
- `2026_10_14_210000_add_employer_staffing_access_and_job_listing_scope.php`
- `2026_10_16_120000_create_showcase_companies_and_homepage_statistics.php`
- `2026_10_16_140000_create_company_relationship_recruiter_assignments.php`
- `2026_10_16_160000_create_talent_pool_company_submissions.php`
- `2026_10_16_180000_extend_recruitment_activities_recruiter_operations.php`
- `2026_10_16_190000_create_staffing_candidate_submissions.php`
- `2026_10_16_210000_allow_direct_employer_placements.php`
- `2026_10_16_220000_add_employer_profile_to_showcase_companies.php`
- `2026_10_16_220000_link_showcase_companies_to_employer_profiles.php`

`public/build` is already tracked (159 files, from commit `2f87766`). After a fresh `npm run build` in the working app, update those built assets on the release branch. Do not commit `node_modules` or `public/hot`.

## Flutter under `mobile/`

Copy `C:\Ournear Hub\ournear_mobile` to `mobile/` on the same release branch, including `lib/`, `test/`, `android/`, `ios/`, `web/`, `linux/`, `macos/`, `windows/`, `pubspec.yaml`, `pubspec.lock`, `analysis_options.yaml`, `.metadata`, and the Flutter `.gitignore` files. Package name is `ournear_mobile`; `publish_to: none`.

The nested `.gitignore` files already exclude `.dart_tool/`, `/build/`, Android `key.properties`, `*.jks`, `*.keystore`, `local.properties`, and `config/candidate_android_lan.json`. No keystore or `key.properties` was present in the Flutter folder at inspection time.

Also exclude from the copy: `.dart_tool/`, `build/`, `.idea/`, `.vscode/`, `*.iml`, `test_output.txt`, `test_run.log`, `test_run2.log`.

## Files to exclude

Already ignored by the Laravel `.gitignore` (same rules in both trees): `.env`, `.env.*` except `.env.example`, `vendor/`, `node_modules/`, `storage/logs`, framework cache/sessions/views, `storage/*.key`, `auth.json`, `*.sql`, `*.zip`, Hostinger snapshot folders, `public/hot`, `public/storage`.

Also exclude even if a diff shows them:

- `bootstrap/cache/*.php` (generated; the working tree currently has `packages.php` and `services.php`)
- local databases, uploads under `storage/app` that are not source fixtures
- Flutter build output, `.dart_tool`, keystores, `key.properties`, `local.properties`
- Cursor agent stores, terminals, and IDE state

`.env` exists in the working Laravel folder and is ignored. `.env.example` is tracked and should remain the only env template.

## Branch strategy

1. In `C:\Ournear Hub\Hire-Remote-Audit` only, fetch and create `release/web-mobile-2026-10-17` from `origin/main` (`a291804`).
2. Leave `main` and `staging-baseline-2026-10-07` unchanged.
3. Copy the source sets above into that checkout. Do not delete the working directories.
4. Review `git status` and `git diff --stat` before any commit.
5. Commit only when explicitly requested. **Do not push** until a later, separate approval. No force-push. No deployment workflow.

## Potential conflicts

- Two migrations share the timestamp `2026_10_16_220000` and both add `showcase_companies.employer_profile_id`. Keep one before the release commit. Running both will fail on the second.
- `public/build` is tracked. A stale or partial build will ship if it is committed without `npm run build`.
- Docs include large QA PNGs. They are source documentation, not secrets, but they increase the commit size.
- The Git checkout has no `.github/workflows`. Nothing to merge. Do not add a deploy workflow as part of this release.
- Flutter `android/.gitignore` ignores `gradlew` and `gradle-wrapper.jar`. Confirm those wrapper files are still intended to be untracked before the first mobile commit.

## Required follow-up tests (after the branch is populated, before push)

```powershell
cd "C:\Ournear Hub\Hire-Remote-Audit"
php artisan test
npx tsc --noEmit
npm run build
cd mobile
flutter analyze
flutter test
```

Also confirm `git status` does not list `.env`, `vendor/`, `node_modules/`, `.dart_tool/`, `build/`, `*.jks`, `key.properties`, or `*.sql`.

## Commands for later publication

Run only after review. These do not deploy and do not touch production hosting.

```powershell
cd "C:\Ournear Hub\Hire-Remote-Audit"
git fetch origin
git switch -c release/web-mobile-2026-10-17 origin/main
# copy reviewed source from "C:\Ournear Hub\Ournear Hire" and Flutter into mobile\
git status
git diff --stat
# commit only when asked:
# git add -A
# git commit -m "Release web and mobile source for review"
# push only when asked, without --force:
# git push -u origin release/web-mobile-2026-10-17
```
