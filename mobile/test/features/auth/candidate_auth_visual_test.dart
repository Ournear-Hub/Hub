import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/auth/auth_service.dart';
import 'package:ournear_mobile/core/theme/ournear_theme.dart';
import 'package:ournear_mobile/features/auth/data/account_access_repository.dart';
import 'package:ournear_mobile/features/auth/login_screen.dart';
import 'package:ournear_mobile/features/auth/password_recovery_screen.dart';
import 'package:provider/provider.dart';

import '../../helpers/fake_http_client.dart';

Widget _authApp(Widget home) {
  final auth = AuthController(
    AuthService(
      apiBaseOverride: 'http://localhost',
      client: FakeHttpClient((_) async => http.Response('', 404)),
    ),
  );
  return ChangeNotifierProvider.value(
    value: auth,
    child: MaterialApp(theme: OurNearTheme.hireLight(), home: home),
  );
}

void main() {
  testWidgets('auth tabs switch between login and signup panels', (tester) async {
    await tester.binding.setSurfaceSize(const Size(360, 800));
    addTearDown(() => tester.binding.setSurfaceSize(null));

    await tester.pumpWidget(_authApp(const LoginScreen()));
    await tester.pumpAndSettle();

    expect(find.byKey(const Key('auth-login-panel')), findsOneWidget);
    expect(find.byKey(const Key('login-field')), findsOneWidget);
    expect(find.byKey(const Key('register-username')), findsNothing);

    await tester.tap(find.byKey(const Key('auth-tab-signup')));
    await tester.pumpAndSettle();
    expect(find.byKey(const Key('auth-signup-panel')), findsOneWidget);
    expect(find.byKey(const Key('register-username')), findsOneWidget);
    expect(find.byKey(const Key('register-consent-terms')), findsOneWidget);
    expect(find.byKey(const Key('register-consent-privacy')), findsOneWidget);

    await tester.tap(find.byKey(const Key('auth-tab-login')));
    await tester.pumpAndSettle();
    expect(find.byKey(const Key('auth-login-panel')), findsOneWidget);
  });

  testWidgets('login-to-register switches to signup tab in place', (tester) async {
    await tester.pumpWidget(_authApp(const LoginScreen()));
    await tester.pumpAndSettle();

    await tester.tap(find.byKey(const Key('login-to-register')));
    await tester.pumpAndSettle();
    expect(find.byKey(const Key('register-username')), findsOneWidget);
  });

  testWidgets('signup opens on registration route with full form', (tester) async {
    await tester.pumpWidget(_authApp(const LoginScreen(initialTabIndex: 1)));
    await tester.pumpAndSettle();

    expect(find.byKey(const Key('ournear-auth-logo')), findsOneWidget);
    expect(find.byKey(const Key('register-mobile')), findsOneWidget);
    expect(find.textContaining('OURNEAR_API_BASE'), findsOneWidget);
  });

  testWidgets('signup submit blocked without consent', (tester) async {
    await tester.binding.setSurfaceSize(const Size(360, 900));
    addTearDown(() => tester.binding.setSurfaceSize(null));
    await tester.pumpWidget(_authApp(const LoginScreen(initialTabIndex: 1)));
    await tester.pumpAndSettle();

    await tester.enterText(find.byKey(const Key('register-username')), 'asha_hire');
    await tester.enterText(find.byKey(const Key('register-name')), 'Asha');
    await tester.enterText(find.byKey(const Key('register-mobile')), '9876543210');
    await tester.enterText(find.byKey(const Key('register-email')), 'asha@example.com');
    await tester.enterText(find.byKey(const Key('register-password')), 'secret');
    await tester.enterText(find.byKey(const Key('register-confirm')), 'secret');
    final scroll = find.byType(Scrollable).first;
    await tester.scrollUntilVisible(
      find.byKey(const Key('register-submit')),
      80,
      scrollable: scroll,
    );
    await tester.tap(find.byKey(const Key('register-submit')));
    await tester.pumpAndSettle();

    expect(
      find.textContaining('Terms & Conditions and Privacy Policy'),
      findsOneWidget,
    );
    expect(find.text(AccountAccessException.registrationUnavailable), findsNothing);
  });

  testWidgets('recovery still opens from login tab', (tester) async {
    await tester.pumpWidget(_authApp(const LoginScreen()));
    await tester.pumpAndSettle();

    await tester.tap(find.byKey(const Key('login-to-recovery')));
    await tester.pumpAndSettle();
    expect(find.byType(PasswordRecoveryScreen), findsOneWidget);
  });
}
