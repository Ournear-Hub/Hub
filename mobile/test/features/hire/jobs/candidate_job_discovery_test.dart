import 'package:flutter/material.dart';
import '../../../helpers/hire_candidate_shell_helpers.dart';
import '../../../helpers/hire_candidate_test_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:ournear_mobile/app/hire_candidate_app.dart';
import 'package:ournear_mobile/core/api/models/public_job.dart';
import 'package:ournear_mobile/features/hire/jobs/data/jobs_repository.dart';
import 'package:ournear_mobile/features/hire/jobs/data/mock_jobs_repository.dart';

Finder _navLabel(String label) {
  return find.descendant(
    of: find.byType(NavigationBar),
    matching: find.text(label),
  );
}

PublicJob _job({
  required String title,
  required String slug,
  String? company,
  String? location,
  bool closed = false,
}) {
  return PublicJob(
    title: title,
    slug: slug,
    companyName: company,
    location: location,
    experience: '2 years',
    employmentType: 'Full time',
    isClosed: closed,
  );
}

void main() {
  setUp(() {
    setHireCandidateTestStorage();
  });

  testWidgets(
    'home shows latest jobs and hides fake rows when the API is unset',
    (tester) async {
      await tester.binding.setSurfaceSize(const Size(420, 900));
      addTearDown(() => tester.binding.setSurfaceSize(null));

      await tester.pumpWidget(const HireCandidateApp());
      await tester.pumpAndSettle();
      await ensureCandidateShellVisible(tester);

      expect(find.text('OurNear Hire'), findsOneWidget);
      await scrollToHomeLatestJobs(tester);
      expect(find.text('Latest Jobs'), findsOneWidget);
      expect(find.byKey(const Key('home-welcome-banner')), findsOneWidget);
      expect(find.text('Search jobs'), findsWidgets);
      expect(find.textContaining('OURNEAR_API_BASE'), findsWidgets);
      expect(find.textContaining('Demo sample jobs'), findsNothing);
      expect(find.text('Recommended for you'), findsNothing);
      expect(find.byType(FilterChip), findsNothing);
    },
  );

  testWidgets('home search opens jobs and keeps the keyword after details', (
    tester,
  ) async {
    await tester.binding.setSurfaceSize(const Size(420, 900));
    addTearDown(() => tester.binding.setSurfaceSize(null));

    final repository = MockJobsRepository(
      jobs: [
        _job(
          title: 'Nurse',
          slug: 'nurse',
          company: 'North Clinic',
          location: 'Pune',
        ),
        _job(
          title: 'Analyst',
          slug: 'analyst',
          company: 'Ledger',
          location: 'Mumbai',
        ),
      ],
      details: {
        'nurse': const PublicJobDetail(
          title: 'Nurse',
          slug: 'nurse',
          companyName: 'North Clinic',
          location: 'Pune',
          experience: '2 years',
          employmentType: 'Full time',
          description: 'Ward coverage for the published role.',
          responsibilities: 'Coordinate the published shift.',
          requirements: 'Registration as supplied by the job.',
          whatWeOffer: 'The published offer text.',
          industry: 'Care',
          category: 'Clinical',
        ),
      },
    );

    await tester.pumpWidget(HireCandidateApp(jobsRepository: repository));
    await tester.pumpAndSettle();
    await ensureCandidateShellVisible(tester);

    await scrollHomeUntilVisible(tester, find.text('Nurse'));
    expect(find.text('Nurse'), findsOneWidget);
    expect(find.textContaining('Demo sample jobs'), findsOneWidget);
    await scrollHomeUntilVisible(tester, find.text('Analyst'));
    expect(find.text('Analyst'), findsOneWidget);

    await tester.enterText(find.byKey(const Key('home-search-field')), 'nurse');
    await tester.tap(find.byKey(const Key('home-search-submit')));
    await tester.pumpAndSettle();

    expect(find.text('Jobs'), findsWidgets);
    expect(find.text('Nurse'), findsWidgets);
    expect(find.text('Analyst'), findsNothing);
    expect(find.text('1 published jobs'), findsOneWidget);
    expect(find.byKey(const Key('jobs-filters-unavailable')), findsOneWidget);
    expect(find.byType(FilterChip), findsNothing);

    await tester.tap(find.text('Nurse').first);
    await tester.pumpAndSettle();
    expect(find.text('Ward coverage for the published role.'), findsOneWidget);
    expect(find.text('Coordinate the published shift.'), findsOneWidget);
    await tester.scrollUntilVisible(
      find.text('Apply Now'),
      120,
      scrollable: find.byType(Scrollable).first,
    );
    expect(find.text('Apply Now'), findsOneWidget);

    await tester.pageBack();
    await tester.pumpAndSettle();

    expect(
      tester
          .widget<TextField>(find.byKey(const Key('job-search-field')))
          .controller
          ?.text,
      'nurse',
    );
    expect(find.text('Nurse'), findsWidgets);
    expect(find.text('Analyst'), findsNothing);
  });

  testWidgets('clearing the jobs query restores the full result', (
    tester,
  ) async {
    await tester.binding.setSurfaceSize(const Size(420, 900));
    addTearDown(() => tester.binding.setSurfaceSize(null));

    await tester.pumpWidget(
      HireCandidateApp(
        jobsRepository: MockJobsRepository(
          jobs: [
            _job(title: 'Nurse', slug: 'nurse'),
            _job(title: 'Analyst', slug: 'analyst'),
          ],
        ),
      ),
    );
    await tester.pumpAndSettle();
    await tester.tap(_navLabel('Jobs'));
    await tester.pumpAndSettle();

    await tester.enterText(
      find.byKey(const Key('job-search-field')),
      'analyst',
    );
    await tester.tap(find.widgetWithText(FilledButton, 'Search'));
    await tester.pumpAndSettle();
    expect(find.text('Analyst'), findsWidgets);
    expect(find.text('Nurse'), findsNothing);

    await tester.tap(find.widgetWithText(OutlinedButton, 'Clear'));
    await tester.pumpAndSettle();
    expect(find.text('Nurse'), findsOneWidget);
    expect(find.text('Analyst'), findsOneWidget);
    expect(
      tester
          .widget<TextField>(find.byKey(const Key('job-search-field')))
          .controller
          ?.text,
      isEmpty,
    );
  });

  testWidgets('jobs retry follows a repository failure', (tester) async {
    await tester.binding.setSurfaceSize(const Size(420, 900));
    addTearDown(() => tester.binding.setSurfaceSize(null));

    final repository = _FlakyJobsRepository(
      pendingFailure: JobsRepositoryException.offline(),
      jobs: [_job(title: 'Nurse', slug: 'nurse')],
    );
    await tester.pumpWidget(HireCandidateApp(jobsRepository: repository));
    await tester.pumpAndSettle();
    await ensureCandidateShellVisible(tester);

    expect(find.text('Offline'), findsWidgets);
    repository.allowSuccess = true;
    await scrollToHomeLatestJobs(tester);
    await tester.fling(
      find.byKey(const Key('candidate-home-scroll')),
      const Offset(0, 400),
      1200,
    );
    await tester.pumpAndSettle();
    await scrollHomeUntilVisible(tester, find.text('Nurse'));
    expect(find.text('Nurse'), findsWidgets);
  });

  testWidgets(
    'closed jobs do not offer apply, and saved jobs screen uses repository',
    (tester) async {
      await tester.binding.setSurfaceSize(const Size(420, 900));
      addTearDown(() => tester.binding.setSurfaceSize(null));

      await tester.pumpWidget(
        HireCandidateApp(
          jobsRepository: _UnavailableSavedJobsRepository(
            MockJobsRepository(
              jobs: [
                _job(title: 'Closed role', slug: 'closed-role', closed: true),
              ],
              details: {
                'closed-role': const PublicJobDetail(
                  title: 'Closed role',
                  slug: 'closed-role',
                  isClosed: true,
                  description: 'Published description.',
                ),
              },
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();
      await ensureCandidateShellVisible(tester);

      await openCandidateMenu(tester);
      await scrollMenuUntilVisible(tester, const Key('menu-saved-jobs'));
      await tester.tap(find.byKey(const Key('menu-saved-jobs')));
      await tester.pumpAndSettle();
      expect(find.byKey(const Key('saved-jobs-unavailable')), findsOneWidget);
      expect(find.textContaining('sync'), findsOneWidget);
      await tester.pageBack();
      await tester.pumpAndSettle();
      await tester.tap(candidateNavLabel('Jobs'));
      await tester.pumpAndSettle();
      expect(find.byKey(const Key('job-card-closed-role')), findsOneWidget);
      await tester.tap(find.byKey(const Key('job-card-closed-role')));
      await tester.pumpAndSettle();
      expect(find.text('Published description.'), findsOneWidget);
      expect(find.text('Apply Now'), findsNothing);
      await tester.scrollUntilVisible(
        find.textContaining('Apply is hidden'),
        120,
        scrollable: find.byType(Scrollable).last,
      );
      expect(find.textContaining('Apply is hidden'), findsOneWidget);
    },
  );

  testWidgets('a focused jobs search field does not change the root tab', (
    tester,
  ) async {
    await tester.binding.setSurfaceSize(const Size(420, 900));
    addTearDown(() => tester.binding.setSurfaceSize(null));

    await tester.pumpWidget(const HireCandidateApp());
    await tester.pumpAndSettle();
    await tester.tap(_navLabel('Jobs'));
    await tester.pumpAndSettle();

    await tester.tap(find.byKey(const Key('job-search-field')));
    await tester.pump();
    await tester.fling(
      find.byKey(const Key('job-search-field')),
      const Offset(-300, 0),
      2000,
    );
    await tester.pumpAndSettle();

    expect(find.byKey(const Key('job-search-field')), findsOneWidget);
    expect(find.textContaining('Demo sample data'), findsNothing);
  });

  testWidgets(
    'industry shortcuts come from the repository and search as a keyword',
    (tester) async {
      await tester.binding.setSurfaceSize(const Size(420, 900));
      addTearDown(() => tester.binding.setSurfaceSize(null));

      await tester.pumpWidget(
        HireCandidateApp(
          jobsRepository: MockJobsRepository(
            jobs: [_job(title: 'Nurse', slug: 'nurse')],
            industries: const [
              JobCatalogueShortcut(label: 'Care', keyword: 'nurse'),
            ],
          ),
        ),
      );
      await tester.pumpAndSettle();

      expect(find.text('Care'), findsOneWidget);
      expect(find.text('Mining'), findsNothing);
      expect(find.text('Construction'), findsNothing);
      await tester.tap(find.text('Care'));
      await tester.pumpAndSettle();
      expect(
        tester
            .widget<TextField>(find.byKey(const Key('job-search-field')))
            .controller
            ?.text,
        'nurse',
      );
      expect(find.text('1 published jobs'), findsOneWidget);
    },
  );
}

class _UnavailableSavedJobsRepository implements JobsRepository {
  _UnavailableSavedJobsRepository(this._inner);

  final MockJobsRepository _inner;

  @override
  bool get isConfigured => _inner.isConfigured;

  @override
  List<String> get supportedListParameters => _inner.supportedListParameters;

  @override
  Future<JobsPage> fetchJobs({
    int page = 1,
    int perPage = 15,
    String? keyword,
    String? industry,
    String? location,
    String? category,
    String? employmentType,
    String? experience,
    String? sort,
  }) =>
      _inner.fetchJobs(
        page: page,
        perPage: perPage,
        keyword: keyword,
        industry: industry,
        location: location,
        category: category,
        employmentType: employmentType,
        experience: experience,
        sort: sort,
      );

  @override
  Future<PublicJobDetail> fetchJobDetail(String slug) =>
      _inner.fetchJobDetail(slug);

  @override
  Future<List<JobCatalogueShortcut>> fetchIndustryShortcuts() =>
      _inner.fetchIndustryShortcuts();

  @override
  Future<Map<String, List<String>>> fetchPublicFilters() =>
      _inner.fetchPublicFilters();

  @override
  Future<JobsPage> fetchSavedJobs() async {
    throw JobsRepositoryException.unavailable(
      LiveJobsRepository.savedJobsUnavailable,
    );
  }
}

class _FlakyJobsRepository extends MockJobsRepository {
  _FlakyJobsRepository({
    required this.pendingFailure,
    required super.jobs,
  });

  final JobsRepositoryException pendingFailure;
  var allowSuccess = false;

  @override
  Future<JobsPage> fetchJobs({
    int page = 1,
    int perPage = 15,
    String? keyword,
    String? industry,
    String? location,
    String? category,
    String? employmentType,
    String? experience,
    String? sort,
  }) {
    if (!allowSuccess) {
      throw pendingFailure;
    }
    return super.fetchJobs(
      page: page,
      perPage: perPage,
      keyword: keyword,
      industry: industry,
      location: location,
      category: category,
      employmentType: employmentType,
      experience: experience,
      sort: sort,
    );
  }
}
