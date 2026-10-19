import '../../../helpers/hire_candidate_shell_helpers.dart';
import '../../../helpers/hire_candidate_test_storage.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:ournear_mobile/app/hire_candidate_app.dart';
void main() {
  setUp(() {
    setHireCandidateTestStorage();
  });

  testWidgets('home banner, menu order, and dark mode toggle', (tester) async {
    await tester.binding.setSurfaceSize(const Size(360, 640));
    addTearDown(() => tester.binding.setSurfaceSize(null));

    await tester.pumpWidget(const HireCandidateApp());
    await tester.pumpAndSettle();
    await ensureCandidateShellVisible(tester);

    expect(find.byKey(const Key('home-welcome-banner')), findsOneWidget);
    await scrollToHomeLatestJobs(tester);
    expect(find.byKey(const Key('latest-jobs-heading')), findsOneWidget);

    await openCandidateMenu(tester);

    expect(find.byKey(const Key('candidate-app-menu')), findsOneWidget);
    final menu = find.byKey(const Key('candidate-menu-list'));
    expect(find.descendant(of: menu, matching: find.text('View Profile')), findsOneWidget);
    expect(find.descendant(of: menu, matching: find.text('Saved Jobs')), findsOneWidget);
    expect(find.descendant(of: menu, matching: find.text('Applied Jobs')), findsOneWidget);
    expect(find.descendant(of: menu, matching: find.text('Resumes')), findsOneWidget);
    expect(find.descendant(of: menu, matching: find.text('Settings')), findsOneWidget);
    expect(find.descendant(of: menu, matching: find.text('Dark Mode')), findsOneWidget);
    expect(find.descendant(of: menu, matching: find.text('OURNEAR APPS')), findsOneWidget);
    await scrollMenuUntilVisible(tester, const Key('menu-logout'));
    expect(find.descendant(of: menu, matching: find.text('Logout')), findsOneWidget);
    expect(find.text('Verification'), findsNothing);
    expect(find.text('Notifications'), findsNothing);

    await scrollMenuUntilVisible(tester, const Key('menu-dark-mode'));
    await tester.tap(find.byKey(const Key('menu-dark-mode')));
    await tester.pumpAndSettle();
    final app = tester.widget<MaterialApp>(find.byType(MaterialApp));
    expect(app.theme?.brightness, Brightness.dark);

    await tester.tap(find.byKey(const Key('menu-delete-account')));
    await tester.pumpAndSettle();
    expect(find.textContaining('not connected'), findsOneWidget);
    await tester.tap(find.text('Cancel'));
    await tester.pumpAndSettle();
  });

  testWidgets('delete account does not clear session', (tester) async {
    await tester.pumpWidget(const HireCandidateApp());
    await tester.pumpAndSettle();
    await openCandidateMenu(tester);
    await scrollMenuUntilVisible(tester, const Key('menu-delete-account'));
    await tester.tap(find.byKey(const Key('menu-delete-account')));
    await tester.pumpAndSettle();
    await tester.tap(find.text('I understand'));
    await tester.pumpAndSettle();
    expect(find.textContaining('Nothing was deleted'), findsOneWidget);
    await tester.tap(find.text('Close'));
    await tester.pumpAndSettle();
  });
}
