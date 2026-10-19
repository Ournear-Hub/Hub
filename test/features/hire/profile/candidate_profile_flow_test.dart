import 'package:flutter/material.dart';
import '../../../helpers/hire_candidate_test_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:ournear_mobile/core/api/models/public_job.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/auth/auth_service.dart';
import 'package:ournear_mobile/core/auth/auth_user.dart';
import 'package:ournear_mobile/core/theme/ournear_theme.dart';
import 'package:ournear_mobile/features/hire/applications/application_readiness_screen.dart';
import 'package:ournear_mobile/features/hire/profile/candidate_profile_screen.dart';
import 'package:ournear_mobile/features/hire/profile/data/documents_repository.dart';
import 'package:ournear_mobile/features/hire/profile/data/mock_documents_repository.dart';
import 'package:ournear_mobile/features/hire/profile/data/mock_profile_repository.dart';
import 'package:ournear_mobile/features/hire/profile/data/profile_repository.dart';
import 'package:ournear_mobile/features/hire/profile/models/candidate_profile.dart';
import 'package:provider/provider.dart';

const _job = PublicJobDetail(
  title: 'Ward Nurse',
  slug: 'ward-nurse',
  companyName: 'City Clinic',
  acceptsApplications: true,
);

void main() {
  setUp(() {
    setHireCandidateTestStorage();
  });

  test('live profile and documents repositories do not succeed', () async {
    final profiles = LiveProfileRepository();
    final documents = LiveDocumentsRepository();
    await expectLater(
      profiles.fetchProfile(),
      throwsA(
        isA<ProfileRepositoryException>().having(
          (error) => error.message,
          'message',
          LiveProfileRepository.readUnavailable,
        ),
      ),
    );
    await expectLater(
      profiles.save(
        ProfileSection.summary,
        const ProfileSectionDraft(text: 'Hello'),
      ),
      throwsA(
        isA<ProfileRepositoryException>().having(
          (error) => error.message,
          'message',
          contains('Nothing was saved'),
        ),
      ),
    );
    await expectLater(
      documents.fetchDocuments(),
      throwsA(isA<DocumentRepositoryException>()),
    );
    await expectLater(
      documents.replaceUploadedResume('cv.pdf'),
      throwsA(
        isA<DocumentRepositoryException>().having(
          (error) => error.message,
          'message',
          contains('No file was uploaded'),
        ),
      ),
    );
  });

  test('mock profile edits stay in memory and are not persisted', () async {
    final repository = MockProfileRepository(
      profile: const CandidateProfile(summary: 'Hello'),
    );
    await expectLater(
      repository.save(
        ProfileSection.summary,
        const ProfileSectionDraft(text: ' '),
      ),
      throwsA(
        isA<ProfileRepositoryException>().having(
          (error) => error.kind,
          'kind',
          ProfileFailureKind.validation,
        ),
      ),
    );
    final unchanged = await repository.fetchProfile();
    expect(unchanged.profile.summary, 'Hello');
    expect(unchanged.persisted, isFalse);
    expect(unchanged.source, ProfileDataSource.mockDemo);

    final saved = await repository.save(
      ProfileSection.summary,
      const ProfileSectionDraft(text: 'Ward coverage'),
    );
    expect(saved.persisted, isFalse);
    expect(saved.profile.summary, 'Ward coverage');
  });

  test('mock documents keep one resume file name', () async {
    final repository = MockDocumentsRepository(
      hirePreviewText: 'Demo preview text',
    );
    final first = await repository.replaceUploadedResume('cv.pdf');
    expect(first.uploadedResume?.fileName, 'cv.pdf');
    expect(first.persisted, isFalse);
    final second = await repository.replaceUploadedResume('cv-2.pdf');
    expect(second.uploadedResume?.fileName, 'cv-2.pdf');
    expect(second.hirePreviewText, 'Demo preview text');
    await expectLater(
      repository.replaceUploadedResume('  '),
      throwsA(
        isA<DocumentRepositoryException>().having(
          (error) => error.message,
          'message',
          contains('not a server policy'),
        ),
      ),
    );
    final after = await repository.fetchDocuments();
    expect(after.uploadedResume?.fileName, 'cv-2.pdf');
  });

  testWidgets(
    'signed-out candidate profile has no Memory and no invented history',
    (tester) async {
      await tester.pumpWidget(_profileApp(auth: AuthController(AuthService())));
      await tester.pumpAndSettle();

      expect(find.text('Sign in to OurNear'), findsOneWidget);
      expect(find.text('Memory'), findsNothing);
      expect(find.textContaining('100%'), findsNothing);
      expect(find.textContaining('Work history'), findsNothing);
    },
  );

  testWidgets('live profile explains the missing service', (tester) async {
    await tester.binding.setSurfaceSize(const Size(420, 1200));
    addTearDown(() => tester.binding.setSurfaceSize(null));
    await tester.pumpWidget(
      _profileApp(
        auth: _signedIn(),
        profiles: LiveProfileRepository(),
        documents: LiveDocumentsRepository(),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Asha'), findsOneWidget);
    expect(find.text('@asha'), findsOneWidget);
    expect(
      find.textContaining('Local setup progress is shown in guided profile setup'),
      findsOneWidget,
    );
    expect(
      find.textContaining('Live saves to OurNear are not connected'),
      findsOneWidget,
    );
    expect(find.textContaining(LiveProfileRepository.readUnavailable), findsOneWidget);
    expect(find.textContaining('Demo profile'), findsNothing);
    expect(find.text('Memory'), findsNothing);
    expect(find.textContaining('phone'), findsNothing);
    await tester.scrollUntilVisible(
      find.text('Sign out'),
      48,
      scrollable: find.byType(Scrollable).first,
    );
    expect(find.text('Sign out'), findsOneWidget);
  });

  testWidgets('professional profile renders only supplied demo data', (
    tester,
  ) async {
    await tester.binding.setSurfaceSize(const Size(420, 1400));
    addTearDown(() => tester.binding.setSurfaceSize(null));
    await tester.pumpWidget(
      _profileApp(
        auth: _signedIn(),
        profiles: MockProfileRepository(
          profile: const CandidateProfile(
            headline: 'Nurse',
            summary: 'Ward coverage',
            skills: ['Care'],
            experience: [
              ProfileExperience(title: 'Nurse', organization: 'City Clinic'),
            ],
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.textContaining('Demo profile'), findsOneWidget);
    expect(find.text('Ward coverage'), findsOneWidget);
    await tester.tap(find.text('View professional profile'));
    await tester.pumpAndSettle();
    expect(find.text('Professional profile'), findsOneWidget);
    expect(find.textContaining('Nurse, City Clinic'), findsOneWidget);
    expect(
      find.textContaining('Recruiter visibility is not established'),
      findsOneWidget,
    );
    expect(find.textContaining('Interview'), findsNothing);
  });

  testWidgets('empty summary is rejected and cancel can discard edits', (
    tester,
  ) async {
    await tester.binding.setSurfaceSize(const Size(420, 1600));
    addTearDown(() => tester.binding.setSurfaceSize(null));
    final repository = MockProfileRepository(
      profile: const CandidateProfile(summary: 'Hello'),
    );
    await tester.pumpWidget(
      _profileApp(auth: _signedIn(), profiles: repository),
    );
    await tester.pumpAndSettle();
    await tester.ensureVisible(find.byKey(const Key('profile-action-Edit profile')));
    await tester.tap(find.byKey(const Key('profile-action-Edit profile')));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Professional summary'));
    await tester.pumpAndSettle();

    expect(
      tester
          .widget<TextField>(find.byKey(const Key('profile-primary-field')))
          .controller
          ?.text,
      'Hello',
    );
    await tester.enterText(find.byKey(const Key('profile-primary-field')), '');
    await tester.tap(find.byKey(const Key('profile-save')));
    await tester.pumpAndSettle();
    expect(find.textContaining('not a server rule'), findsOneWidget);
    expect((await repository.fetchProfile()).profile.summary, 'Hello');

    await tester.enterText(
      find.byKey(const Key('profile-primary-field')),
      'Changed',
    );
    await tester.tap(find.byKey(const Key('profile-cancel')));
    await tester.pumpAndSettle();
    expect(find.text('Discard unsaved changes?'), findsOneWidget);
    await tester.tap(find.text('Keep editing'));
    await tester.pumpAndSettle();
    expect(
      tester
          .widget<TextField>(find.byKey(const Key('profile-primary-field')))
          .controller
          ?.text,
      'Changed',
    );

    await tester.tap(find.byKey(const Key('profile-cancel')));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Discard'));
    await tester.pumpAndSettle();
    expect(find.text('Edit profile'), findsOneWidget);
    expect((await repository.fetchProfile()).profile.summary, 'Hello');
  });

  testWidgets('a demo save is not described as saved on OurNear', (
    tester,
  ) async {
    final repository = MockProfileRepository();
    await tester.binding.setSurfaceSize(const Size(420, 1600));
    addTearDown(() => tester.binding.setSurfaceSize(null));
    await tester.pumpWidget(
      _profileApp(auth: _signedIn(), profiles: repository),
    );
    await tester.pumpAndSettle();
    await tester.ensureVisible(find.byKey(const Key('profile-action-Edit profile')));
    await tester.tap(find.byKey(const Key('profile-action-Edit profile')));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Professional summary'));
    await tester.pumpAndSettle();
    await tester.enterText(
      find.byKey(const Key('profile-primary-field')),
      'Ward coverage',
    );
    await tester.tap(find.byKey(const Key('profile-save')));
    await tester.pumpAndSettle();

    expect(find.textContaining('Nothing was saved on OurNear'), findsOneWidget);
    expect(find.text('Saved on OurNear.'), findsNothing);
    expect((await repository.fetchProfile()).profile.summary, 'Ward coverage');
  });

  testWidgets('save confirmation appears only after a persisted response', (
    tester,
  ) async {
    await tester.binding.setSurfaceSize(const Size(420, 1600));
    addTearDown(() => tester.binding.setSurfaceSize(null));
    await tester.pumpWidget(
      _profileApp(auth: _signedIn(), profiles: _ConfirmingProfileRepository()),
    );
    await tester.pumpAndSettle();
    await tester.ensureVisible(find.byKey(const Key('profile-action-Edit profile')));
    await tester.tap(find.byKey(const Key('profile-action-Edit profile')));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Professional summary'));
    await tester.pumpAndSettle();
    await tester.enterText(
      find.byKey(const Key('profile-primary-field')),
      'Confirmed',
    );
    await tester.tap(find.byKey(const Key('profile-save')));
    await tester.pumpAndSettle();

    expect(find.text('Saved on OurNear.'), findsOneWidget);
  });

  testWidgets(
    'live documents stay unavailable and mock resume names replace once',
    (tester) async {
      await tester.binding.setSurfaceSize(const Size(420, 1400));
      addTearDown(() => tester.binding.setSurfaceSize(null));
      await tester.pumpWidget(
        _profileApp(
          auth: _signedIn(),
          documents: LiveDocumentsRepository(),
        ),
      );
      await tester.pumpAndSettle();
      await tester.tap(find.text('Resume and documents'));
      await tester.pumpAndSettle();
      expect(
        find.textContaining(LiveDocumentsRepository.readUnavailable),
        findsOneWidget,
      );

      final documents = MockDocumentsRepository(
        hirePreviewText: 'Generated demo text',
      );
      await tester.pumpWidget(
        _profileApp(auth: _signedIn(), documents: documents),
      );
      await tester.pumpAndSettle();
      await tester.tap(find.text('Resume and documents'));
      await tester.pumpAndSettle();
      await tester.enterText(
        find.byKey(const Key('resume-file-name')),
        'cv.pdf',
      );
      await tester.tap(find.byKey(const Key('resume-replace')));
      await tester.pumpAndSettle();
      await tester.enterText(
        find.byKey(const Key('resume-file-name')),
        'cv-2.pdf',
      );
      await tester.tap(find.byKey(const Key('resume-replace')));
      await tester.pumpAndSettle();
      expect(find.textContaining('cv-2.pdf'), findsOneWidget);
      expect(find.textContaining('Demo file name: cv.pdf'), findsNothing);

    },
  );

  testWidgets('readiness shows a demo headline without attaching it', (
    tester,
  ) async {
    await tester.pumpWidget(
      MultiProvider(
        providers: [
          Provider<ProfileRepository>(
            create: (_) => MockProfileRepository(
              profile: const CandidateProfile(headline: 'Ward nurse'),
            ),
          ),
          Provider<DocumentsRepository>(
            create: (_) => MockDocumentsRepository(
              uploadedResume: const UploadedResumeRecord(fileName: 'cv.pdf'),
            ),
          ),
        ],
        child: const MaterialApp(home: ApplicationReadinessScreen(job: _job)),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.textContaining('guided profile setup'), findsOneWidget);
    expect(find.textContaining('Demo headline: Ward nurse'), findsOneWidget);
    expect(find.textContaining('will not be attached'), findsWidgets);
    expect(find.textContaining('cv.pdf'), findsOneWidget);
    expect(
      find.textContaining('not attached to this application'),
      findsOneWidget,
    );
  });
}

AuthController _signedIn() {
  final auth = AuthController(AuthService());
  auth.status = AuthStatus.authenticated;
  auth.user = const AuthUser(
    name: 'Asha',
    username: 'asha',
    role: 'candidate',
    emailVerified: true,
    mustChangePassword: false,
  );
  return auth;
}

Widget _profileApp({
  required AuthController auth,
  ProfileRepository? profiles,
  DocumentsRepository? documents,
}) {
  return MultiProvider(
    providers: [
      ChangeNotifierProvider<AuthController>.value(value: auth),
      Provider<ProfileRepository>(
        create: (_) => profiles ?? LiveProfileRepository(),
      ),
      Provider<DocumentsRepository>(
        create: (_) => documents ?? LiveDocumentsRepository(),
      ),
    ],
    child: MaterialApp(
      theme: OurNearTheme.hireLight(),
      home: CandidateProfileScreen(
        profileRepository: profiles,
        documentsRepository: documents,
      ),
    ),
  );
}

class _ConfirmingProfileRepository implements ProfileRepository {
  @override
  Future<ProfileSnapshot> fetchProfile() async {
    return const ProfileSnapshot(
      profile: CandidateProfile.empty,
      source: ProfileDataSource.liveApi,
      persisted: true,
    );
  }

  @override
  Future<ProfileSnapshot> save(
    ProfileSection section,
    ProfileSectionDraft draft,
  ) async {
    return ProfileSnapshot(
      profile: CandidateProfile(summary: draft.text),
      source: ProfileDataSource.liveApi,
      persisted: true,
    );
  }

  @override
  Future<ProfileSnapshot> deleteRecord(
    ProfileSection section,
    int recordId,
  ) async {
    return fetchProfile();
  }

  @override
  Future<ProfileSnapshot> uploadProfilePhoto({
    required List<int> bytes,
    required String filename,
  }) async {
    return const ProfileSnapshot(
      profile: CandidateProfile(photoUrl: 'https://cdn.test/photo.jpg'),
      source: ProfileDataSource.liveApi,
      persisted: true,
    );
  }
}
