import '../../../helpers/hire_candidate_shell_helpers.dart';
import '../../../helpers/hire_candidate_test_storage.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:ournear_mobile/app/hire_candidate_app.dart';

void main() {
  setUp(() {
    setHireCandidateTestStorage();
  });

  testWidgets('menu icon only on Profile tab', (tester) async {
    await tester.binding.setSurfaceSize(const Size(360, 640));
    addTearDown(() => tester.binding.setSurfaceSize(null));

    await tester.pumpWidget(const HireCandidateApp());
    await tester.pumpAndSettle();
    await ensureCandidateShellVisible(tester);

    await assertMenuAbsentOnTab(tester, 'Home');
    await assertMenuAbsentOnTab(tester, 'Jobs');
    await assertMenuAbsentOnTab(tester, 'Applications');

    await tester.tap(candidateNavLabel('Profile'));
    await tester.pumpAndSettle();
    expect(find.byKey(const Key('candidate-menu-button')), findsOneWidget);

    await openCandidateMenu(tester);
    expect(find.byKey(const Key('candidate-app-menu')), findsOneWidget);
    await closeCandidateMenuIfOpen(tester);

    await tester.tap(candidateNavLabel('Home'));
    await tester.pumpAndSettle();
    expect(find.byKey(const Key('candidate-app-menu')), findsNothing);
    expect(find.byKey(const Key('candidate-menu-button')), findsNothing);
    expect(find.byType(NavigationDestination), findsNWidgets(4));
  });

  testWidgets('menu navigation from Profile does not break tab selection', (
    tester,
  ) async {
    await tester.pumpWidget(const HireCandidateApp());
    await tester.pumpAndSettle();
    await openCandidateMenu(tester);
    await tester.tap(find.byKey(const Key('menu-applied-jobs')));
    await tester.pumpAndSettle(const Duration(seconds: 2));
    expect(find.text('Your applications'), findsWidgets);
    expect(find.byKey(const Key('candidate-menu-button')), findsNothing);
    expect(candidateNavLabel('Applications'), findsOneWidget);
  });
}
