import '../helpers/hire_candidate_shell_helpers.dart';
import '../helpers/hire_candidate_test_storage.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:ournear_mobile/app/hire_candidate_app.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/auth/auth_service.dart';
import 'package:ournear_mobile/core/auth/auth_user.dart';
import 'package:ournear_mobile/core/theme/ournear_theme.dart';
import 'package:ournear_mobile/features/hire/communication/data/notifications_repository.dart';
import 'package:ournear_mobile/features/hire/profile/data/profile_repository.dart';
import 'package:ournear_mobile/features/hire/settings/candidate_settings_screen.dart';
import 'package:provider/provider.dart';

Finder _navLabel(String label) {
  return find.descendant(
    of: find.byType(NavigationBar),
    matching: find.text(label),
  );
}

void main() {
  setUp(() {
    setHireCandidateTestStorage();
  });

  for (final size in const [Size(360, 640), Size(1280, 800)]) {
    testWidgets(
      'candidate shell stays on four tabs at ${size.width}x${size.height}',
      (tester) async {
        await tester.binding.setSurfaceSize(size);
        addTearDown(() => tester.binding.setSurfaceSize(null));

        await tester.pumpWidget(const HireCandidateApp());
        await tester.pumpAndSettle();
        await ensureCandidateShellVisible(tester);

        final app = tester.widget<MaterialApp>(find.byType(MaterialApp));
        expect(app.title, 'OurNear Hire');
        expect(find.byType(NavigationDestination), findsNWidgets(4));
        expect(_navLabel('Home'), findsOneWidget);
        expect(_navLabel('Jobs'), findsOneWidget);
        expect(_navLabel('Applications'), findsOneWidget);
        expect(_navLabel('Profile'), findsOneWidget);
        expect(_navLabel('Messages'), findsNothing);
        await scrollToHomeLatestJobs(tester);
        expect(find.text('Latest Jobs'), findsOneWidget);
        expect(find.text('Memory'), findsNothing);

        await tester.tap(_navLabel('Jobs'));
        await tester.pumpAndSettle();
        expect(find.textContaining('OURNEAR_API_BASE'), findsWidgets);
        expect(find.text('demo-app-001'), findsNothing);

        await tester.tap(_navLabel('Applications'));
        await tester.pumpAndSettle();
        expect(
          find.textContaining('Sign in to load application history'),
          findsOneWidget,
        );
        expect(find.text('demo-app-001'), findsNothing);
        expect(find.textContaining('Nothing was sent'), findsNothing);

        await tester.tap(_navLabel('Profile'));
        await tester.pumpAndSettle();
        expect(find.text('Memory'), findsNothing);
        expect(
          find.textContaining('Sign in to view and edit your professional profile'),
          findsOneWidget,
        );

        await tester.tap(_navLabel('Home'));
        await tester.pumpAndSettle();
        await tester.tap(_homeAction('Notifications'));
        await tester.pumpAndSettle();
        expect(find.text(LiveNotificationsRepository.emptyInbox), findsNothing);
        expect(find.text('Sign in to OurNear'), findsWidgets);
      },
    );
  }

  testWidgets('settings sign-out clears the visible account', (tester) async {
    await tester.binding.setSurfaceSize(const Size(360, 640));
    addTearDown(() => tester.binding.setSurfaceSize(null));

    final auth = AuthController(AuthService());
    auth.status = AuthStatus.authenticated;
    auth.user = const AuthUser(
      name: 'Asha',
      username: 'asha',
      role: 'candidate',
      emailVerified: true,
      mustChangePassword: false,
    );

    await tester.pumpWidget(
      MultiProvider(
        providers: [
          ChangeNotifierProvider<AuthController>.value(value: auth),
          Provider<ProfileRepository>(
            create: (_) => LiveProfileRepository(),
          ),
        ],
        child: MaterialApp(
          theme: OurNearTheme.hireLight(),
          home: const CandidateSettingsScreen(),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Asha'), findsOneWidget);
    await tester.ensureVisible(
      find.byKey(const Key('settings-Privacy and account')),
    );
    await tester.tap(find.byKey(const Key('settings-Privacy and account')));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Sign out'));
    await tester.pumpAndSettle();

    expect(auth.status, AuthStatus.unauthenticated);
    expect(auth.user, isNull);
    expect(find.text('Asha'), findsNothing);
  });
}

Finder _homeAction(String tooltip) {
  return find.descendant(
    of: find.ancestor(
      of: find.text('OurNear Hire'),
      matching: find.byType(AppBar),
    ),
    matching: find.byTooltip(tooltip),
  );
}
