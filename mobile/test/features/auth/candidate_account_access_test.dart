import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/auth/auth_service.dart';
import 'package:ournear_mobile/core/theme/ournear_theme.dart';
import 'package:ournear_mobile/features/auth/data/account_access_repository.dart';
import 'package:ournear_mobile/app/hire_candidate_app.dart';
import 'package:ournear_mobile/features/auth/login_screen.dart';
import 'package:ournear_mobile/features/auth/onboarding/candidate_onboarding_screen.dart';
import 'package:ournear_mobile/features/auth/password_recovery_screen.dart';
import 'package:http/http.dart' as http;
import 'package:provider/provider.dart';

import '../../helpers/fake_http_client.dart';
import '../../helpers/hire_candidate_shell_helpers.dart';
import '../../helpers/hire_candidate_test_storage.dart';

void main() {
  test('live account access repository requires API base', () async {
    final repository = LiveAccountAccessRepository(authService: AuthService());
    await expectLater(
      repository.register(
        username: 'asha',
        displayName: 'Asha',
        mobile: '+971501234567',
        email: 'asha@example.com',
        password: 'Password1!',
        passwordConfirmation: 'Password1!',
      ),
      throwsA(isA<AccountAccessException>()),
    );
    await expectLater(
      repository.requestPasswordRecovery(email: 'asha@example.com'),
      throwsA(isA<AccountAccessException>()),
    );
  });

  testWidgets('onboarding skip finishes without blocking sign-in path', (
    tester,
  ) async {
    var finished = false;
    await tester.binding.setSurfaceSize(const Size(360, 640));
    addTearDown(() => tester.binding.setSurfaceSize(null));

    await tester.pumpWidget(
      MaterialApp(
        theme: OurNearTheme.hireLight(),
        home: CandidateOnboardingScreen(onFinished: () => finished = true),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Discover jobs'), findsOneWidget);
    await tester.tap(find.byKey(const Key('onboarding-skip')));
    await tester.pumpAndSettle();
    expect(finished, isTrue);
  });

  testWidgets('registration validates locally and stays unavailable', (
    tester,
  ) async {
    await tester.binding.setSurfaceSize(const Size(360, 1200));
    addTearDown(() => tester.binding.setSurfaceSize(null));

    await tester.pumpWidget(
      MaterialApp(
        theme: OurNearTheme.hireLight(),
        home: const LoginScreen(initialTabIndex: 1),
      ),
    );
    await tester.pumpAndSettle();

    final scroll = find.byType(Scrollable).first;
    Future<void> dismissKeyboard() async {
      FocusManager.instance.primaryFocus?.unfocus();
      await tester.pumpAndSettle();
    }

    Future<void> tapKey(Key key, {bool checkbox = false}) async {
      final finder = checkbox
          ? find.descendant(
              of: find.byKey(key),
              matching: find.byType(Checkbox),
            )
          : find.byKey(key);
      await dismissKeyboard();
      await tester.scrollUntilVisible(finder, 120, scrollable: scroll);
      await tester.ensureVisible(finder);
      await tester.pump();
      await tester.tap(finder);
      await tester.pumpAndSettle();
    }

    Future<void> tapSubmit() => tapKey(const Key('register-submit'));

    await tapSubmit();
    expect(find.textContaining('not a server rule'), findsWidgets);
    expect(find.textContaining('No account was created'), findsNothing);

    await tester.enterText(find.byKey(const Key('register-username')), 'ab');
    await tester.enterText(find.byKey(const Key('register-name')), 'Asha');
    await tester.enterText(find.byKey(const Key('register-mobile')), '12');
    await tester.enterText(find.byKey(const Key('register-email')), 'bad');
    await tester.enterText(
      find.byKey(const Key('register-password')),
      'secret',
    );
    await tester.enterText(find.byKey(const Key('register-confirm')), 'other');
    await tapSubmit();
    expect(find.textContaining('valid email'), findsOneWidget);
    expect(find.textContaining('do not match'), findsOneWidget);

    await tester.enterText(find.byKey(const Key('register-username')), 'asha_hire');
    await tester.enterText(find.byKey(const Key('register-mobile')), '9876543210');
    await tester.enterText(
      find.byKey(const Key('register-email')),
      'asha@example.com',
    );
    await tester.enterText(find.byKey(const Key('register-confirm')), 'secret');

    await tapKey(const Key('register-consent-terms'), checkbox: true);
    await tapKey(const Key('register-consent-privacy'), checkbox: true);
    await tapSubmit();
    expect(find.byKey(const Key('register-message')), findsOneWidget);
    expect(find.textContaining('not configured'), findsOneWidget);
    expect(find.textContaining('check your inbox'), findsNothing);
  });

  testWidgets('recovery shows unavailable message and keeps typed email', (
    tester,
  ) async {
    await tester.binding.setSurfaceSize(const Size(360, 640));
    addTearDown(() => tester.binding.setSurfaceSize(null));

    await tester.pumpWidget(
      MaterialApp(
        theme: OurNearTheme.hireLight(),
        home: const PasswordRecoveryScreen(initialEmail: 'keep@example.com'),
      ),
    );
    await tester.pumpAndSettle();

    final emailField = tester.widget<TextFormField>(
      find.byKey(const Key('recovery-email')),
    );
    expect(emailField.controller?.text, 'keep@example.com');

    await tester.tap(find.byKey(const Key('recovery-submit')));
    await tester.pumpAndSettle();
    expect(find.byKey(const Key('recovery-message')), findsOneWidget);
    expect(find.textContaining('not configured'), findsOneWidget);
    expect(find.textContaining('check your inbox'), findsNothing);
    expect(
      tester
          .widget<TextFormField>(find.byKey(const Key('recovery-email')))
          .controller
          ?.text,
      'keep@example.com',
    );
  });

  testWidgets('first launch shows optional onboarding then jobs after skip', (
    tester,
  ) async {
    setHireCandidateFirstLaunchStorage();
    await tester.binding.setSurfaceSize(const Size(360, 640));
    addTearDown(() => tester.binding.setSurfaceSize(null));

    await tester.pumpWidget(const HireCandidateApp());
    await tester.pumpAndSettle();

    expect(find.text('Discover jobs'), findsOneWidget);
    await tester.tap(find.byKey(const Key('onboarding-skip')));
    await tester.pumpAndSettle();
    await ensureCandidateShellVisible(tester);
    await scrollToHomeLatestJobs(tester);
    expect(find.text('Latest Jobs'), findsOneWidget);
    expect(find.byType(NavigationDestination), findsNWidgets(4));
  });

  testWidgets('login links open registration and recovery', (tester) async {
    setHireCandidateTestStorage();
    await tester.binding.setSurfaceSize(const Size(360, 900));
    addTearDown(() => tester.binding.setSurfaceSize(null));
    final auth = AuthController(
      AuthService(
        apiBaseOverride: 'http://localhost',
        client: FakeHttpClient((_) async => http.Response('', 404)),
      ),
    );
    await auth.bootstrap();

    await tester.pumpWidget(
      ChangeNotifierProvider.value(
        value: auth,
        child: MaterialApp(
          theme: OurNearTheme.hireLight(),
          home: const LoginScreen(),
        ),
      ),
    );
    await tester.pumpAndSettle();

    await tester.tap(find.byKey(const Key('login-to-register')));
    await tester.pumpAndSettle();
    expect(find.byKey(const Key('register-username')), findsOneWidget);
    await tester.tap(find.byKey(const Key('auth-tab-login')));
    await tester.pumpAndSettle();
    expect(find.byKey(const Key('login-field')), findsOneWidget);

    await tester.tap(find.byKey(const Key('login-to-recovery')));
    await tester.pumpAndSettle();
    expect(find.byType(PasswordRecoveryScreen), findsOneWidget);
  });
}
