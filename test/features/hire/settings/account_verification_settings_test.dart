import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/auth/auth_service.dart';
import 'package:ournear_mobile/core/auth/auth_user.dart';
import 'package:ournear_mobile/core/theme/ournear_theme.dart';
import 'package:ournear_mobile/features/hire/settings/account_verification_screen.dart';
import 'package:ournear_mobile/features/hire/settings/candidate_settings_screen.dart';
import 'package:provider/provider.dart';

void main() {
  testWidgets('settings lists Verification and shows account flags', (
    tester,
  ) async {
    final auth = AuthController(AuthService());
    auth.status = AuthStatus.authenticated;
    auth.user = const AuthUser(
      name: 'Asha',
      username: 'asha',
      role: 'candidate',
      emailVerified: false,
      mustChangePassword: true,
    );

    await tester.pumpWidget(
      ChangeNotifierProvider.value(
        value: auth,
        child: MaterialApp(
          theme: OurNearTheme.hireLight(),
          home: const CandidateSettingsScreen(),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.byKey(const Key('settings-Verification')), findsOneWidget);

    await tester.tap(find.byKey(const Key('settings-Verification')));
    await tester.pumpAndSettle();

    expect(find.byKey(const Key('settings-verification')), findsOneWidget);
    expect(find.text('Email not verified'), findsOneWidget);
    expect(find.text('Password change required'), findsOneWidget);
  });

  testWidgets('verification screen signed out explains sign in', (tester) async {
    final auth = AuthController(AuthService());
    auth.status = AuthStatus.unauthenticated;
    await tester.pumpWidget(
      ChangeNotifierProvider.value(
        value: auth,
        child: MaterialApp(
          theme: OurNearTheme.hireLight(),
          home: const AccountVerificationScreen(),
        ),
      ),
    );
    await tester.pumpAndSettle();
    expect(
      find.textContaining('Sign in to view verification status'),
      findsOneWidget,
    );
  });
}
