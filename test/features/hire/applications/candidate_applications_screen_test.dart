import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:ournear_mobile/core/theme/ournear_theme.dart';
import 'package:ournear_mobile/features/hire/applications/candidate_applications_screen.dart';
import 'package:ournear_mobile/features/hire/applications/data/mock_applications_repository.dart';

void main() {
  testWidgets('shows demo banner and application cards when populated', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: OurNearTheme.hireLight(),
        home: CandidateApplicationsScreen(
          repository: MockApplicationsRepository(artificialDelay: Duration.zero),
        ),
      ),
    );
    await tester.pump();
    await tester.pumpAndSettle();

    expect(find.textContaining('Demo sample data'), findsOneWidget);
    expect(find.text('Customer Success Associate'), findsOneWidget);
    expect(find.text('Under review'), findsWidgets);
  });

  testWidgets('empty state offers browse jobs action', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: OurNearTheme.hireLight(),
        home: CandidateApplicationsScreen(
          repository: MockApplicationsRepository(
            artificialDelay: Duration.zero,
            returnEmpty: true,
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('No applications yet'), findsOneWidget);
    expect(find.text('Browse jobs'), findsOneWidget);
  });

  testWidgets('opens detail screen on card tap', (tester) async {
    await tester.binding.setSurfaceSize(const Size(800, 1400));
    addTearDown(() => tester.binding.setSurfaceSize(null));
    await tester.pumpWidget(
      MaterialApp(
        theme: OurNearTheme.hireLight(),
        home: CandidateApplicationsScreen(
          repository: MockApplicationsRepository(artificialDelay: Duration.zero),
        ),
      ),
    );
    await tester.pumpAndSettle();

    await tester.ensureVisible(find.text('Retail Team Lead'));
    await tester.tap(find.text('Retail Team Lead'));
    await tester.pumpAndSettle();

    expect(find.text('Submitted'), findsWidgets);
    expect(find.textContaining('Message threads'), findsOneWidget);
  });

  testWidgets('offline state shows retry', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: OurNearTheme.hireLight(),
        home: CandidateApplicationsScreen(
          repository: MockApplicationsRepository(
            artificialDelay: Duration.zero,
            simulateOffline: true,
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Offline'), findsOneWidget);
    expect(find.text('Try again'), findsOneWidget);
  });
}
