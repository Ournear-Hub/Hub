import 'package:flutter/material.dart';
import '../../helpers/hire_candidate_test_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:ournear_mobile/app/hire_candidate_app.dart';

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

  testWidgets('candidate shell swipes one tab without wraparound', (
    tester,
  ) async {
    await tester.binding.setSurfaceSize(const Size(420, 860));
    addTearDown(() => tester.binding.setSurfaceSize(null));

    await tester.pumpWidget(const HireCandidateApp());
    await tester.pumpAndSettle();

    expect(find.text('Latest Jobs'), findsOneWidget);

    final body = find.text('Latest Jobs');
    await tester.fling(body, const Offset(-300, 0), 2000);
    await tester.pumpAndSettle();
    expect(find.textContaining('OURNEAR_API_BASE'), findsWidgets);

    await tester.fling(
      find.textContaining('OURNEAR_API_BASE'),
      const Offset(-300, 0),
      2000,
    );
    await tester.pumpAndSettle();
    expect(
      find.textContaining('Sign in to load application history'),
      findsOneWidget,
    );

    await tester.fling(
      find.textContaining('Sign in to load application history'),
      const Offset(-300, 0),
      2000,
    );
    await tester.pumpAndSettle();
    expect(
      find.textContaining('Sign in to view and edit your professional profile'),
      findsOneWidget,
    );

    await tester.fling(
      find.textContaining('Sign in to view and edit'),
      const Offset(-300, 0),
      2000,
    );
    await tester.pumpAndSettle();
    expect(
      find.textContaining('Sign in to view and edit your professional profile'),
      findsOneWidget,
    );

    await tester.tap(_navLabel('Home'));
    await tester.pumpAndSettle();
    expect(find.text('Latest Jobs'), findsOneWidget);
  });
}
