import 'package:flutter/material.dart';
import '../../helpers/hire_candidate_test_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:ournear_mobile/app/hire_candidate_app.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/auth/auth_service.dart';
import 'package:ournear_mobile/core/theme/ournear_theme.dart';
import 'package:ournear_mobile/features/profile/profile_screen.dart';
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

  testWidgets('candidate profile hides Memory and keeps sign in', (
    tester,
  ) async {
    await tester.binding.setSurfaceSize(const Size(420, 860));
    addTearDown(() => tester.binding.setSurfaceSize(null));

    await tester.pumpWidget(const HireCandidateApp());
    await tester.pumpAndSettle();

    await tester.tap(_navLabel('Profile'));
    await tester.pumpAndSettle();

    expect(find.text('Memory'), findsNothing);
    expect(
      find.textContaining('Sign in to view and edit your professional profile'),
      findsOneWidget,
    );
    expect(find.text('Sign in'), findsOneWidget);
    expect(find.text('Notifications'), findsNothing);
  });

  testWidgets('default profile still shows Memory for the unified shell', (
    tester,
  ) async {
    await tester.pumpWidget(
      ChangeNotifierProvider(
        create: (_) => AuthController(AuthService())..bootstrap(),
        child: MaterialApp(
          theme: OurNearTheme.light(),
          home: const ProfileScreen(),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Memory'), findsOneWidget);
    expect(find.text('Sign in'), findsOneWidget);
  });
}
