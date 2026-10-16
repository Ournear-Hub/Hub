import 'package:flutter/material.dart';
import '../../../helpers/hire_candidate_test_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:ournear_mobile/app/hire_candidate_app.dart';
import 'package:ournear_mobile/core/api/models/public_job.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/auth/auth_service.dart';
import 'package:ournear_mobile/core/auth/auth_user.dart';
import 'package:ournear_mobile/core/navigation/root_tab_shell.dart';
import 'package:ournear_mobile/core/navigation/shell_destination.dart';
import 'package:ournear_mobile/core/theme/ournear_theme.dart';
import 'package:ournear_mobile/features/hire/applications/application_readiness_screen.dart';
import 'package:ournear_mobile/features/hire/applications/application_wizard_screen.dart';
import 'package:ournear_mobile/features/hire/applications/candidate_application_detail_screen.dart';
import 'package:ournear_mobile/features/hire/applications/models/candidate_application.dart';
import 'package:ournear_mobile/features/hire/applications/candidate_applications_screen.dart';
import 'package:ournear_mobile/features/hire/applications/data/applications_repository.dart';
import 'package:ournear_mobile/features/hire/applications/data/mock_applications_repository.dart';
import 'package:provider/provider.dart';

const _openJob = PublicJobDetail(
  title: 'Ward Nurse',
  slug: 'ward-nurse',
  companyName: 'City Clinic',
  location: 'Chennai',
  acceptsApplications: true,
);

const _closedJob = PublicJobDetail(
  title: 'Closed Role',
  slug: 'closed-role',
  companyName: 'City Clinic',
  isClosed: true,
  acceptsApplications: false,
);

void main() {
  setUp(() {
    setHireCandidateTestStorage();
  });

  test(
    'live repository refuses history, detail, and submit when API base is unset',
    () async {
      final repository = LiveApplicationsRepository();
      await expectLater(
        repository.fetchApplications(),
        throwsA(
          isA<ApplicationsRepositoryException>().having(
            (error) => error.kind,
            'kind',
            ApplicationsFailureKind.unavailable,
          ),
        ),
      );
      await expectLater(
        repository.fetchApplication('demo-app-001'),
        throwsA(
          isA<ApplicationsRepositoryException>().having(
            (error) => error.kind,
            'kind',
            ApplicationsFailureKind.unavailable,
          ),
        ),
      );
      await expectLater(
        repository.submitApplication(
          const ApplicationDraft(
            jobSlug: 'ward-nurse',
            jobTitle: 'Ward Nurse',
            companyName: 'City Clinic',
          ),
        ),
        throwsA(
          isA<ApplicationsRepositoryException>().having(
            (error) => error.kind,
            'kind',
            ApplicationsFailureKind.unavailable,
          ),
        ),
      );
    },
  );

  test(
    'mock submit is labelled, not server-confirmed, and blocks a duplicate',
    () async {
      final repository = MockApplicationsRepository(
        artificialDelay: Duration.zero,
        clock: () => DateTime.utc(2026, 4, 1, 8),
      );
      const draft = ApplicationDraft(
        jobSlug: 'ward-nurse',
        jobTitle: 'Ward Nurse',
        companyName: 'City Clinic',
        location: 'Chennai',
      );

      final created = await repository.submitApplication(draft);
      expect(created.confirmedByServer, isFalse);
      expect(created.source, ApplicationsDataSource.mockDemo);
      expect(created.application.id, 'demo-app-local-1');
      expect(created.application.timeline.map((event) => event.label), [
        'Submitted',
      ]);
      expect(
        created.application.timeline.map((event) => event.label),
        isNot(contains('Interview')),
      );

      await expectLater(
        repository.submitApplication(draft),
        throwsA(
          isA<ApplicationsRepositoryException>().having(
            (error) => error.message,
            'message',
            contains('Nothing new was submitted'),
          ),
        ),
      );

      final listed = await repository.fetchApplications();
      expect(listed.source, ApplicationsDataSource.mockDemo);
      expect(listed.applications.map((application) => application.id), [
        'demo-app-001',
        'demo-app-002',
        'demo-app-003',
        'demo-app-local-1',
      ]);
    },
  );

  test('mock detail misses an unknown id', () async {
    final repository = MockApplicationsRepository(
      artificialDelay: Duration.zero,
    );
    await expectLater(
      repository.fetchApplication('missing'),
      throwsA(
        isA<ApplicationsRepositoryException>().having(
          (error) => error.kind,
          'kind',
          ApplicationsFailureKind.notFound,
        ),
      ),
    );
  });

  testWidgets('live applications screen does not show demo records', (
    tester,
  ) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: OurNearTheme.hireLight(),
        home: CandidateApplicationsScreen(
          repository: LiveApplicationsRepository(),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(
      find.textContaining('not connected'),
      findsOneWidget,
    );
    expect(find.textContaining('Demo sample data'), findsNothing);
    expect(find.text('Customer Success Associate'), findsNothing);
    expect(find.byType(FilterChip), findsNothing);
  });

  testWidgets('candidate shell applications tab stays on the live repository', (
    tester,
  ) async {
    await tester.pumpWidget(const HireCandidateApp());
    await tester.pumpAndSettle();

    await tester.tap(
      find.descendant(
        of: find.byType(NavigationBar),
        matching: find.text('Applications'),
      ),
    );
    await tester.pumpAndSettle();

    expect(
      find.textContaining('Sign in to load application history'),
      findsOneWidget,
    );
    expect(find.text('Customer Success Associate'), findsNothing);
  });

  testWidgets('demo status filter applies only to the local demo list', (
    tester,
  ) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: OurNearTheme.hireLight(),
        home: CandidateApplicationsScreen(
          repository: MockApplicationsRepository(
            artificialDelay: Duration.zero,
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.byKey(const Key('demo-status-filters')), findsOneWidget);
    expect(find.text('Customer Success Associate'), findsOneWidget);

    await tester.tap(find.widgetWithText(FilterChip, 'Not selected'));
    await tester.pumpAndSettle();

    expect(find.text('Customer Success Associate'), findsNothing);
    expect(find.text('No demo applications use this label.'), findsOneWidget);
    expect(find.textContaining('not server statuses'), findsOneWidget);
  });

  testWidgets('closed jobs cannot continue into the wizard', (tester) async {
    await tester.pumpWidget(
      const MaterialApp(home: ApplicationReadinessScreen(job: _closedJob)),
    );

    expect(find.text('Closed Role'), findsOneWidget);
    expect(find.text('This job is closed.'), findsOneWidget);
    expect(find.byKey(const Key('readiness-continue')), findsNothing);
    expect(find.textContaining('Nothing has been submitted'), findsOneWidget);
    expect(find.textContaining('guided profile setup'), findsOneWidget);
  });

  testWidgets(
    'wizard keeps the job, validates sign-in, and preserves the account step',
    (tester) async {
      final auth = _signedIn('Asha', 'asha');
      await tester.pumpWidget(
        _app(
          auth: auth,
          applications: LiveApplicationsRepository(),
          home: ApplicationReadinessScreen(
            job: _openJob,
            repository: LiveApplicationsRepository(),
          ),
        ),
      );

      expect(
        find.textContaining('Continuing does not submit an application'),
        findsOneWidget,
      );
      await tester.tap(find.byKey(const Key('readiness-continue')));
      await tester.pumpAndSettle();
      expect(find.text('Ward Nurse'), findsWidgets);

      await tester.tap(find.byKey(const Key('wizard-next')));
      await tester.pumpAndSettle();
      expect(
        find.textContaining('primary resume'),
        findsOneWidget,
      );

      await tester.tap(find.byKey(const Key('wizard-back')));
      await tester.pumpAndSettle();
      expect(find.text('Name: Asha'), findsOneWidget);

      await tester.tap(find.byKey(const Key('wizard-next')));
      await tester.pumpAndSettle();
      await tester.tap(find.byKey(const Key('wizard-next')));
      await tester.pumpAndSettle();

      expect(find.text('Job: Ward Nurse'), findsOneWidget);
      expect(find.text('Company: City Clinic'), findsOneWidget);
      expect(find.text('Account: Asha (asha)'), findsOneWidget);
      expect(find.text('Resume: not attached'), findsOneWidget);
      expect(
        find.textContaining('No job questions were provided'),
        findsOneWidget,
      );
      expect(find.byType(TextField), findsNothing);
    },
  );

  testWidgets('next without an account shows a field error', (tester) async {
    final auth = AuthController(AuthService());
    auth.status = AuthStatus.unauthenticated;
    await tester.pumpWidget(
      _app(
        auth: auth,
        home: const ApplicationWizardScreen(job: _openJob),
      ),
    );

    await tester.tap(find.byKey(const Key('wizard-next')));
    await tester.pumpAndSettle();

    expect(find.byKey(const Key('wizard-account-error')), findsOneWidget);
    expect(find.text('Review'), findsNothing);
  });

  testWidgets('live submit stays unavailable and can be tried again', (
    tester,
  ) async {
    await tester.pumpWidget(
      _app(
        auth: _signedIn('Asha', 'asha'),
        applications: LiveApplicationsRepository(),
        home: ApplicationWizardScreen(
          job: _openJob,
          repository: LiveApplicationsRepository(),
        ),
      ),
    );
    await _reachReview(tester);

    await tester.tap(find.byKey(const Key('wizard-submit')));
    await tester.pumpAndSettle();

    expect(find.textContaining('Nothing was sent'), findsOneWidget);
    expect(find.byKey(const Key('wizard-demo-confirmation')), findsNothing);
    expect(find.byKey(const Key('wizard-server-confirmation')), findsNothing);
    expect(find.text('Submit application'), findsOneWidget);

    await tester.tap(find.byKey(const Key('wizard-submit')));
    await tester.pumpAndSettle();
    expect(find.textContaining('Nothing was sent'), findsOneWidget);
  });

  testWidgets('a second tap does not submit the demo record twice', (
    tester,
  ) async {
    final repository = _CountingApplicationsRepository();
    await tester.pumpWidget(
      _app(
        auth: _signedIn('Asha', 'asha'),
        applications: repository,
        home: ApplicationWizardScreen(job: _openJob, repository: repository),
      ),
    );
    await _reachReview(tester);

    await tester.tap(find.byKey(const Key('wizard-submit')));
    await tester.tap(find.byKey(const Key('wizard-submit')));
    await tester.pumpAndSettle();

    expect(repository.submits, 1);
    expect(find.byKey(const Key('wizard-demo-confirmation')), findsOneWidget);
    expect(find.text('Recorded'), findsOneWidget);
    expect(find.textContaining('Demo sample data'), findsOneWidget);

    await tester.tap(find.text('Recorded'));
    await tester.pumpAndSettle();
    expect(repository.submits, 1);
  });

  testWidgets('an expired session does not confirm a submission', (
    tester,
  ) async {
    await tester.pumpWidget(
      _app(
        auth: _signedIn('Asha', 'asha'),
        applications: _SessionApplicationsRepository(),
        home: const ApplicationWizardScreen(job: _openJob),
      ),
    );
    await _reachReview(tester);
    await tester.tap(find.byKey(const Key('wizard-submit')));
    await tester.pumpAndSettle();

    expect(find.textContaining('session has expired'), findsOneWidget);
    expect(find.byKey(const Key('wizard-demo-confirmation')), findsNothing);
    expect(find.byKey(const Key('wizard-server-confirmation')), findsNothing);
  });

  testWidgets('application history shows session expiry from the repository', (
    tester,
  ) async {
    await tester.pumpWidget(
      MaterialApp(
        home: CandidateApplicationsScreen(
          repository: _SessionApplicationsRepository(),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Sign in again'), findsOneWidget);
    expect(find.textContaining('session has expired'), findsOneWidget);
    expect(find.text('Customer Success Associate'), findsNothing);
  });

  testWidgets('detail timeline uses only supplied events', (tester) async {
    await tester.binding.setSurfaceSize(const Size(800, 1400));
    addTearDown(() => tester.binding.setSurfaceSize(null));
    await tester.pumpWidget(
      MaterialApp(
        home: CandidateApplicationsScreen(
          repository: MockApplicationsRepository(
            artificialDelay: Duration.zero,
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();
    await tester.ensureVisible(find.text('Retail Team Lead'));
    await tester.tap(find.text('Retail Team Lead'));
    await tester.pumpAndSettle();

    expect(find.text('Timeline'), findsOneWidget);
    expect(find.textContaining('Submitted:'), findsOneWidget);
    expect(find.textContaining('Updated:'), findsNothing);
    expect(find.textContaining('Interview'), findsNothing);
    expect(find.text('Withdraw'), findsNothing);
    expect(find.text('View job'), findsNothing);
    expect(find.textContaining('Message threads'), findsOneWidget);
  });

  testWidgets('detail lookup shows loading and then not found', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        home: CandidateApplicationDetailScreen.fetch(
          applicationId: 'missing',
          repository: MockApplicationsRepository(
            artificialDelay: const Duration(milliseconds: 50),
          ),
        ),
      ),
    );
    expect(find.bySemanticsLabel('Loading application'), findsOneWidget);

    await tester.pumpAndSettle();
    expect(find.text('Application not found'), findsOneWidget);
    expect(find.textContaining('Interview'), findsNothing);
  });

  testWidgets('system back leaves the wizard on the previous step', (
    tester,
  ) async {
    await tester.pumpWidget(
      _app(
        auth: _signedIn('Asha', 'asha'),
        home: const ApplicationReadinessScreen(job: _openJob),
      ),
    );
    await tester.tap(find.byKey(const Key('readiness-continue')));
    await tester.pumpAndSettle();
    await tester.tap(find.byKey(const Key('wizard-next')));
    await tester.pumpAndSettle();

    await tester.binding.handlePopRoute();
    await tester.pumpAndSettle();
    expect(find.text('Name: Asha'), findsOneWidget);
    expect(find.text('Apply'), findsOneWidget);

    await tester.binding.handlePopRoute();
    await tester.pumpAndSettle();
    expect(find.text('Application readiness'), findsOneWidget);
    expect(find.text('Ward Nurse'), findsOneWidget);
  });

  testWidgets('a wizard route does not change the root tab', (tester) async {
    await tester.binding.setSurfaceSize(const Size(420, 860));
    addTearDown(() => tester.binding.setSurfaceSize(null));

    await tester.pumpWidget(
      _app(
        auth: _signedIn('Asha', 'asha'),
        home: RootTabShell(
          destinations: [
            ShellDestination(
              id: 'home',
              label: 'Home',
              icon: Icons.home,
              selectedIcon: Icons.home,
              builder: (_) => const Text('Home landmark'),
            ),
            ShellDestination(
              id: 'jobs',
              label: 'Jobs',
              icon: Icons.work,
              selectedIcon: Icons.work,
              builder: (context) => TextButton(
                onPressed: () {
                  Navigator.of(context).push(
                    MaterialPageRoute<void>(
                      builder: (_) =>
                          const ApplicationWizardScreen(job: _openJob),
                    ),
                  );
                },
                child: const Text('Open apply'),
              ),
            ),
          ],
        ),
      ),
    );

    await tester.tap(
      find.descendant(
        of: find.byType(NavigationBar),
        matching: find.text('Jobs'),
      ),
    );
    await tester.pumpAndSettle();
    await tester.tap(find.text('Open apply'));
    await tester.pumpAndSettle();

    await tester.fling(find.text('Step 1 of 3'), const Offset(-400, 0), 2000);
    await tester.pumpAndSettle();

    expect(find.text('Step 1 of 3'), findsOneWidget);
    expect(find.text('Apply'), findsOneWidget);

    await tester.binding.handlePopRoute();
    await tester.pumpAndSettle();
    expect(find.text('Open apply'), findsOneWidget);
    expect(find.text('Home landmark'), findsNothing);
  });
}

Future<void> _reachReview(WidgetTester tester) async {
  await tester.tap(find.byKey(const Key('wizard-next')));
  await tester.pumpAndSettle();
  await tester.tap(find.byKey(const Key('wizard-next')));
  await tester.pumpAndSettle();
}

AuthController _signedIn(String name, String username) {
  final auth = AuthController(AuthService());
  auth.status = AuthStatus.authenticated;
  auth.user = AuthUser(
    name: name,
    username: username,
    role: 'candidate',
    emailVerified: true,
    mustChangePassword: false,
  );
  return auth;
}

Widget _app({
  required Widget home,
  AuthController? auth,
  ApplicationsRepository? applications,
}) {
  return MultiProvider(
    providers: [
      if (auth != null)
        ChangeNotifierProvider<AuthController>.value(value: auth),
      if (applications != null)
        Provider<ApplicationsRepository>.value(value: applications),
    ],
    child: MaterialApp(theme: OurNearTheme.hireLight(), home: home),
  );
}

class _CountingApplicationsRepository extends MockApplicationsRepository {
  _CountingApplicationsRepository()
    : super(artificialDelay: const Duration(milliseconds: 300));

  var submits = 0;

  @override
  Future<ApplicationSubmission> submitApplication(ApplicationDraft draft) {
    submits += 1;
    return super.submitApplication(draft);
  }
}

class _SessionApplicationsRepository implements ApplicationsRepository {
  @override
  Future<ApplicationsQueryResult> fetchApplications() async {
    throw ApplicationsRepositoryException.sessionExpired();
  }

  @override
  Future<CandidateApplication> fetchApplication(String id) async {
    throw ApplicationsRepositoryException.sessionExpired();
  }

  @override
  Future<ApplicationSubmission> submitApplication(
    ApplicationDraft draft,
  ) async {
    throw ApplicationsRepositoryException.sessionExpired();
  }
}
