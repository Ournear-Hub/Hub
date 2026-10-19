import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/auth/auth_service.dart';
import 'package:ournear_mobile/core/auth/auth_user.dart';
import 'package:ournear_mobile/core/theme/ournear_theme.dart';
import 'package:ournear_mobile/features/hire/profile/candidate_direct_profile_screen.dart';
import 'package:ournear_mobile/features/hire/profile/data/mock_profile_repository.dart';
import 'package:ournear_mobile/features/hire/profile/profile_setup/guided_profile_setup_screen.dart';
import 'package:provider/provider.dart';

void main() {
  testWidgets('signed-in profile shows professional sections and edit', (
    tester,
  ) async {
    final auth = AuthController(AuthService());
    auth.status = AuthStatus.authenticated;
    auth.user = const AuthUser(
      name: 'Alex',
      username: 'alex',
      role: 'candidate',
      emailVerified: true,
      mustChangePassword: false,
    );

    await tester.pumpWidget(
      ChangeNotifierProvider.value(
        value: auth,
        child: MaterialApp(
          theme: OurNearTheme.hireLight(),
          home: CandidateDirectProfileScreen(
            profileRepository: MockProfileRepository(),
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Professional summary'), findsOneWidget);
    expect(find.text('Saved on OurNear.'), findsNothing);
    await tester.scrollUntilVisible(
      find.byKey(const Key('profile-edit-action')),
      48,
    );
    expect(find.byKey(const Key('profile-edit-action')), findsOneWidget);
  });

  testWidgets('edit profile opens guided setup and returns', (tester) async {
    final auth = AuthController(AuthService());
    auth.status = AuthStatus.authenticated;
    auth.user = const AuthUser(
      name: 'Alex',
      username: 'alex',
      role: 'candidate',
      emailVerified: true,
      mustChangePassword: false,
    );

    await tester.pumpWidget(
      ChangeNotifierProvider.value(
        value: auth,
        child: MaterialApp(
          theme: OurNearTheme.hireLight(),
          home: CandidateDirectProfileScreen(
            profileRepository: MockProfileRepository(),
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();

    await tester.scrollUntilVisible(
      find.byKey(const Key('profile-edit-action')),
      120,
      scrollable: find.byType(Scrollable).first,
    );
    await tester.ensureVisible(find.byKey(const Key('profile-edit-action')));
    await tester.tap(find.byKey(const Key('profile-edit-action')));
    await tester.pumpAndSettle();
    expect(find.byType(GuidedProfileSetupScreen), findsOneWidget);
    await tester.pageBack();
    await tester.pumpAndSettle();
    expect(find.byKey(const Key('profile-edit-action')), findsOneWidget);
  });
}
