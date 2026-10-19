import 'package:ournear_mobile/features/hire/applications/data/applications_repository.dart';
import 'package:ournear_mobile/features/hire/applications/models/candidate_application.dart';
import 'package:ournear_mobile/features/hire/applications/models/candidate_application_status.dart';

/// Deterministic demo applications for UI development and QA.
///
/// IDs and copy are synthetic and clearly local — not server-persisted records.
class MockApplicationsRepository implements ApplicationsRepository {
  MockApplicationsRepository({
    this.simulateOffline = false,
    this.simulateError = false,
    this.returnEmpty = false,
    this.artificialDelay = const Duration(milliseconds: 120),
    DateTime Function()? clock,
  }) : _clock = clock ?? DateTime.now;

  final bool simulateOffline;
  final bool simulateError;
  final bool returnEmpty;
  final Duration artificialDelay;
  final DateTime Function() _clock;
  final List<CandidateApplication> _applications = [];
  var _seeded = false;
  var _localSequence = 0;

  static final List<CandidateApplication> demoApplications = [
    CandidateApplication(
      id: 'demo-app-001',
      roleTitle: 'Customer Success Associate',
      companyName: 'Northbridge Services Group',
      location: 'Remote · India',
      status: CandidateApplicationStatus.underReview,
      statusLabel: CandidateApplicationStatus.underReview.label,
      submittedAt: DateTime.utc(2026, 3, 4, 9, 15),
      updatedAt: DateTime.utc(2026, 3, 10, 14, 30),
      jobSlug: null,
      timeline: CandidateApplication.timelineFromTimestamps(
        submittedAt: DateTime.utc(2026, 3, 4, 9, 15),
        updatedAt: DateTime.utc(2026, 3, 10, 14, 30),
      ),
    ),
    CandidateApplication(
      id: 'demo-app-002',
      roleTitle: 'Field Operations Coordinator',
      companyName: 'Harborline Logistics',
      location: 'Chennai, Tamil Nadu',
      status: CandidateApplicationStatus.shortlisted,
      statusLabel: CandidateApplicationStatus.shortlisted.label,
      submittedAt: DateTime.utc(2026, 2, 18, 11, 0),
      updatedAt: DateTime.utc(2026, 3, 8, 10, 5),
      jobSlug: null,
      timeline: CandidateApplication.timelineFromTimestamps(
        submittedAt: DateTime.utc(2026, 2, 18, 11, 0),
        updatedAt: DateTime.utc(2026, 3, 8, 10, 5),
      ),
    ),
    CandidateApplication(
      id: 'demo-app-003',
      roleTitle: 'Retail Team Lead',
      companyName: 'Urban Market Collective',
      location: 'Bengaluru, Karnataka',
      status: CandidateApplicationStatus.submitted,
      statusLabel: CandidateApplicationStatus.submitted.label,
      submittedAt: DateTime.utc(2026, 3, 12, 16, 45),
      jobSlug: null,
      timeline: CandidateApplication.timelineFromTimestamps(
        submittedAt: DateTime.utc(2026, 3, 12, 16, 45),
      ),
    ),
  ];

  void _seed() {
    if (_seeded) {
      return;
    }
    _applications.addAll(demoApplications);
    _seeded = true;
  }

  Future<void> _guard() async {
    await Future<void>.delayed(artificialDelay);
    if (simulateOffline) {
      throw ApplicationsRepositoryException.offline();
    }
    if (simulateError) {
      throw ApplicationsRepositoryException.unavailable(
        'Demo error — retry to reload sample data.',
      );
    }
  }

  @override
  Future<ApplicationsQueryResult> fetchApplications() async {
    await _guard();
    if (returnEmpty) {
      return const ApplicationsQueryResult(
        applications: [],
        source: ApplicationsDataSource.mockDemo,
      );
    }
    _seed();
    return ApplicationsQueryResult(
      applications: List.unmodifiable(_applications),
      source: ApplicationsDataSource.mockDemo,
    );
  }

  @override
  Future<CandidateApplication> fetchApplication(String id) async {
    await _guard();
    _seed();
    for (final application in _applications) {
      if (application.id == id) {
        return application;
      }
    }
    throw ApplicationsRepositoryException.notFound(
      'This application is not in the demo list.',
    );
  }

  @override
  Future<ApplicationSubmission> submitApplication(
    ApplicationDraft draft,
  ) async {
    await _guard();
    _seed();
    if (_applications.any(
      (application) => application.jobSlug == draft.jobSlug,
    )) {
      throw ApplicationsRepositoryException.unavailable(
        'A demo application for this job is already recorded. Nothing new was submitted.',
      );
    }
    _localSequence += 1;
    final submittedAt = _clock().toUtc();
    final created = CandidateApplication(
      id: 'demo-app-local-$_localSequence',
      roleTitle: draft.jobTitle,
      companyName: draft.companyName,
      location: draft.location,
      status: CandidateApplicationStatus.submitted,
      statusLabel: CandidateApplicationStatus.submitted.label,
      submittedAt: submittedAt,
      jobSlug: draft.jobSlug,
      timeline: CandidateApplication.timelineFromTimestamps(
        submittedAt: submittedAt,
      ),
    );
    _applications.add(created);
    return ApplicationSubmission(
      application: created,
      source: ApplicationsDataSource.mockDemo,
      confirmedByServer: false,
    );
  }
}
