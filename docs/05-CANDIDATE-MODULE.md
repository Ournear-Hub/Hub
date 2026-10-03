# Candidate module

**Status:** IMPLEMENTED.

## Registration / login

- Routes: `register`, `register.store`, `register.check-username`, `login`, `login.store`, password reset (`routes/account.php`).
- Controllers: `RegisteredUserController`, `AuthenticatedSessionController`.
- Creates `User` + `CandidateProfile` for candidate registration.

## Profile / professional

- `ProfileController`, `CandidateProfessionalProfileController` (professional, skills, employment, privacy).
- Certifications, languages, education, projects: dedicated account controllers.
- Presenter: `UnifiedProfilePresenter` (Stage 23F).
- Public: `/profiles/{username}` (`PublicProfileController`), `/profile/{username}` redirects.
- Display ID: `OurNearHireIdentity` on the user (`ON-HIR-CAN-###` in candidate context). `business_id` remains the internal ONT identifier.
- Verification entry: account nav **Verification** → existing `/account/settings/security`.

## Files

See [09-RESUME-DOCUMENTS-FILES.md](09-RESUME-DOCUMENTS-FILES.md).

## Applications / saved jobs

- `CandidateApplicationsController`, `SavedJobController`.
- Apply: `PublicSiteController::jobApplyStore` + `ApplicationSubmissionService`.
- Withdraw via account applications routes.

## Notifications / OTP

- `CandidateNotificationsController`, `CommunicationPreferenceService`.
- Mobile: `MobileVerificationController` + `MobileVerificationService`.
- WhatsApp: `WhatsAppVerificationController` + `WhatsAppVerificationService` (OTP only).

## Reviews

`CandidateRelationshipReviewController` after placement/employment relationship exists.

## Key models

`User`, `CandidateProfile`, `Application`, `SavedJob`, `CandidateResume`, `CandidateDocument`, `VerificationChallenge`.

## Tests (examples)

`CandidateTalentPoolStage3Test`, `UnifiedProfileStage23FTest`, `AccountCandidateDashboardTest`, `AccountCandidateResumeDocumentTest`, `MobileOtpVerificationTest`, `WhatsAppOtpVerificationTest`, `DualGuardAuthenticationTest`.

## Dependencies

Auth account guard, jobs, applications, private disks, notifications.

## Must not

Expose private files on public profiles. Do not store recruiter identity on candidate. Do not treat OTP WhatsApp as recruitment messaging.
