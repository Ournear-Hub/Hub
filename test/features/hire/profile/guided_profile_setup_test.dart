import 'package:flutter/material.dart';
import '../../../helpers/hire_candidate_test_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/auth/auth_service.dart';
import 'package:ournear_mobile/core/auth/auth_user.dart';
import 'package:ournear_mobile/core/theme/ournear_theme.dart';
import 'package:ournear_mobile/features/hire/profile/candidate_profile_screen.dart';
import 'package:ournear_mobile/features/hire/profile/data/mock_profile_repository.dart';
import 'package:ournear_mobile/features/hire/profile/data/profile_repository.dart';
import 'package:ournear_mobile/features/hire/profile/models/candidate_profile.dart';
import 'package:ournear_mobile/features/hire/profile/profile_setup/guided_profile_setup_screen.dart';
import 'package:ournear_mobile/features/hire/profile/profile_setup/profile_setup_progress.dart';
import 'package:provider/provider.dart';

const _user = AuthUser(
  name: 'Alex Candidate',
  username: 'alex',
  role: 'candidate',
  emailVerified: true,
  mustChangePassword: false,
);

Widget _signedInShell({
  required Widget child,
  ProfileRepository? profiles,
}) {
  final auth = AuthController(AuthService());
  auth.status = AuthStatus.authenticated;
  auth.user = _user;
  return ChangeNotifierProvider.value(
    value: auth,
    child: MaterialApp(
      theme: OurNearTheme.hireLight(),
      home: Provider<ProfileRepository>.value(
        value: profiles ?? MockProfileRepository(),
        child: child,
      ),
    ),
  );
}

void main() {
  setUp(() {
    setHireCandidateTestStorage();
  });

  test('progress counts reviewed sections from profile content only', () {
    const profile = CandidateProfile(
      publicLocation: 'London',
      summary: 'Ward nurse',
      skills: ['Triage'],
    );
    final progress = ProfileSetupProgress(profile: profile);
    expect(progress.reviewedLocallyCount, 3);
    expect(
      progress.statusFor(ProfileSection.personal),
      ProfileSetupSectionStatus.reviewedLocally,
    );
    expect(
      progress.statusFor(ProfileSection.experience),
      ProfileSetupSectionStatus.notStarted,
    );
  });

  test('touched empty section stays in progress until content exists', () {
    const progress = ProfileSetupProgress(
      profile: CandidateProfile.empty,
      touchedSections: {ProfileSection.summary},
    );
    expect(
      progress.statusFor(ProfileSection.summary),
      ProfileSetupSectionStatus.inProgress,
    );
  });

  testWidgets('signed-out setup shows sign-in guidance', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: OurNearTheme.hireLight(),
        home: GuidedProfileSetupScreen(
          profileRepository: MockProfileRepository(),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.byKey(const Key('profile-setup-sign-in')), findsOneWidget);
    expect(find.textContaining('Local setup progress'), findsNothing);
    final tile = tester.widget<ListTile>(
      find.byKey(const Key('profile-setup-section-summary')),
    );
    expect(tile.enabled, isFalse);
  });

  testWidgets('guided setup lists eight sections and local progress', (
    tester,
  ) async {
    await tester.pumpWidget(
      _signedInShell(
        child: GuidedProfileSetupScreen(
          profileRepository: MockProfileRepository(),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.byKey(const Key('profile-setup-progress-summary')), findsOneWidget);
    expect(find.textContaining('0 of 8 sections reviewed locally'), findsOneWidget);
    for (final section in guidedProfileSetupSections) {
      final key = Key('profile-setup-section-${section.name}');
      await tester.scrollUntilVisible(
        find.byKey(key),
        48,
        scrollable: find.byType(Scrollable).last,
      );
      expect(find.byKey(key), findsOneWidget);
    }
  });

  testWidgets('opening a section does not mark it reviewed without save', (
    tester,
  ) async {
    await tester.pumpWidget(
      _signedInShell(
        child: GuidedProfileSetupScreen(
          profileRepository: MockProfileRepository(),
        ),
      ),
    );
    await tester.pumpAndSettle();

    await tester.tap(find.byKey(const Key('profile-setup-section-summary')));
    await tester.pumpAndSettle();
    expect(find.byKey(const Key('profile-primary-field')), findsOneWidget);

    await tester.pageBack();
    await tester.pumpAndSettle();

    expect(find.textContaining('Status: In progress'), findsOneWidget);
    expect(find.textContaining('1 of 8 sections reviewed locally'), findsNothing);
  });

  testWidgets('mock save updates local progress without Saved on OurNear', (
    tester,
  ) async {
    await tester.pumpWidget(
      _signedInShell(
        child: GuidedProfileSetupScreen(
          profileRepository: MockProfileRepository(),
        ),
      ),
    );
    await tester.pumpAndSettle();

    await tester.tap(find.byKey(const Key('profile-setup-section-summary')));
    await tester.pumpAndSettle();
    await tester.enterText(
      find.byKey(const Key('profile-primary-field')),
      'Experienced nurse',
    );
    await tester.tap(find.byKey(const Key('profile-save')));
    await tester.pumpAndSettle();

    expect(find.text('Saved on OurNear.'), findsNothing);
    expect(
      find.textContaining('Demo profile updated in this session'),
      findsOneWidget,
    );

    await tester.pageBack();
    await tester.pumpAndSettle();

    await tester.scrollUntilVisible(
      find.byKey(const Key('profile-setup-progress-summary')),
      48,
      scrollable: find.byType(Scrollable).last,
    );
    expect(find.textContaining('1 of 8 sections reviewed locally'), findsOneWidget);
    expect(find.textContaining('Status: Reviewed locally'), findsOneWidget);
  });

  testWidgets('live repository save stays unavailable in guided flow', (
    tester,
  ) async {
    await tester.pumpWidget(
      _signedInShell(
        profiles: LiveProfileRepository(),
        child: const GuidedProfileSetupScreen(),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.byKey(const Key('profile-setup-load-error')), findsOneWidget);
    await tester.tap(find.byKey(const Key('profile-setup-section-summary')));
    await tester.pumpAndSettle();
    await tester.enterText(
      find.byKey(const Key('profile-primary-field')),
      'Try save',
    );
    await tester.tap(find.byKey(const Key('profile-save')));
    await tester.pumpAndSettle();

    expect(find.text(LiveProfileRepository.writeUnavailable), findsOneWidget);
    expect(find.text('Saved on OurNear.'), findsNothing);
  });

  testWidgets('profile tab opens guided setup when signed in', (tester) async {
    await tester.binding.setSurfaceSize(const Size(420, 900));
    addTearDown(() => tester.binding.setSurfaceSize(null));
    await tester.pumpWidget(
      _signedInShell(child: const CandidateProfileScreen()),
    );
    await tester.pumpAndSettle();

    await tester.scrollUntilVisible(
      find.byKey(const Key('profile-guided-setup')),
      48,
      scrollable: find.byType(Scrollable).first,
    );
    await tester.tap(find.byKey(const Key('profile-guided-setup')));
    await tester.pumpAndSettle();

    expect(find.byKey(const Key('profile-setup-intro')), findsOneWidget);
    await tester.pageBack();
    await tester.pumpAndSettle();
    expect(find.text('Complete your professional profile'), findsOneWidget);
  });

  testWidgets('signed-out profile tab can open setup without blocking jobs', (
    tester,
  ) async {
    final auth = AuthController(AuthService());
    auth.status = AuthStatus.unauthenticated;
    await tester.pumpWidget(
      ChangeNotifierProvider.value(
        value: auth,
        child: MaterialApp(
          theme: OurNearTheme.hireLight(),
          home: CandidateProfileScreen(
            profileRepository: MockProfileRepository(),
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();

    await tester.tap(find.byKey(const Key('profile-guided-setup-signed-out')));
    await tester.pumpAndSettle();
    expect(find.byKey(const Key('profile-setup-sign-in')), findsOneWidget);
  });
}
