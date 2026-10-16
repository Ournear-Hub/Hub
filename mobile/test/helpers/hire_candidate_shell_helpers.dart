import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

/// Waits through session gate when a stored token cannot be verified.
Future<void> ensureCandidateShellVisible(WidgetTester tester) async {
  if (find.byKey(const Key('session-gate-checking')).evaluate().isNotEmpty) {
    await tester.pumpAndSettle();
  }
  final browse = find.text('Browse jobs without account');
  if (browse.evaluate().isNotEmpty) {
    await tester.tap(browse);
    await tester.pumpAndSettle();
  }
}

Future<void> scrollToHomeLatestJobs(WidgetTester tester) async {
  final list = find.byKey(const Key('candidate-home-scroll'));
  for (var attempt = 0; attempt < 6; attempt++) {
    if (find.byKey(const Key('latest-jobs-heading')).evaluate().isNotEmpty) {
      return;
    }
    await tester.drag(list, const Offset(0, -320));
    await tester.pumpAndSettle();
  }
}

Future<void> scrollHomeUntilVisible(WidgetTester tester, Finder target) async {
  final list = find.byKey(const Key('candidate-home-scroll'));
  for (var attempt = 0; attempt < 8; attempt++) {
    if (target.evaluate().isNotEmpty) {
      await tester.ensureVisible(target);
      return;
    }
    await tester.drag(list, const Offset(0, -320));
    await tester.pumpAndSettle();
  }
  if (target.evaluate().isNotEmpty) {
    await tester.ensureVisible(target);
  }
}

Future<void> closeCandidateMenuIfOpen(WidgetTester tester) async {
  if (find.byKey(const Key('candidate-app-menu')).evaluate().isEmpty) {
    return;
  }
  await tester.tapAt(const Offset(24, 400));
  await tester.pumpAndSettle();
}

Future<void> openCandidateMenu(WidgetTester tester) async {
  await ensureCandidateShellVisible(tester);
  await tester.tap(candidateNavLabel('Profile'));
  await tester.pumpAndSettle();
  expect(find.byKey(const Key('candidate-menu-button')), findsOneWidget);
  await tester.tap(find.byKey(const Key('candidate-menu-button')));
  await tester.pumpAndSettle();
}

Future<void> assertMenuAbsentOnTab(WidgetTester tester, String tabLabel) async {
  await ensureCandidateShellVisible(tester);
  await tester.tap(candidateNavLabel(tabLabel));
  await tester.pumpAndSettle();
  expect(find.byKey(const Key('candidate-menu-button')), findsNothing);
}

Future<void> scrollMenuUntilVisible(WidgetTester tester, Key key) async {
  final list = find.byKey(const Key('candidate-menu-list'));
  for (var attempt = 0; attempt < 10; attempt++) {
    if (find.byKey(key).evaluate().isNotEmpty) {
      await tester.ensureVisible(find.byKey(key));
      return;
    }
    await tester.drag(list, const Offset(0, -280));
    await tester.pumpAndSettle();
  }
  await tester.ensureVisible(find.byKey(key));
}

Finder candidateNavLabel(String label) {
  return find.descendant(
    of: find.byType(NavigationBar),
    matching: find.text(label),
  );
}
