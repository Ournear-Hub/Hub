import 'package:flutter/material.dart';
import '../../helpers/hire_candidate_test_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:ournear_mobile/app/hire_candidate_app.dart';
import 'package:ournear_mobile/core/navigation/ournear_motion.dart';
import 'package:ournear_mobile/core/navigation/root_tab_swipe_coordinator.dart';

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

  testWidgets(
    'reduced motion keeps one-step swipe and suppresses route decoration',
    (tester) async {
      tester.platformDispatcher.accessibilityFeaturesTestValue =
          const FakeAccessibilityFeatures(disableAnimations: true);
      addTearDown(
        tester.platformDispatcher.clearAccessibilityFeaturesTestValue,
      );
      await tester.binding.setSurfaceSize(const Size(420, 860));
      addTearDown(() => tester.binding.setSurfaceSize(null));

      await tester.pumpWidget(const HireCandidateApp());
      await tester.pumpAndSettle();

      expect(
        tester
            .widget<NavigationBar>(find.byType(NavigationBar))
            .animationDuration,
        Duration.zero,
      );

      final home = find.text('Latest Jobs');
      await tester.fling(home, const Offset(-300, 0), 2000);
      await tester.pumpAndSettle();
      expect(find.textContaining('OURNEAR_API_BASE'), findsWidgets);

      await tester.tap(_navLabel('Profile'));
      await tester.pumpAndSettle();
      expect(find.text('Sign in'), findsOneWidget);

      final profileContext = tester.element(
        find.widgetWithText(FilledButton, 'Sign in'),
      );
      final transitions = Theme.of(profileContext).pageTransitionsTheme;
      final builder = transitions.builders[Theme.of(profileContext).platform];
      expect(builder, isA<InstantPageTransitionsBuilder>());

      final built = builder!.buildTransitions<void>(
        MaterialPageRoute<void>(builder: (_) => const SizedBox.shrink()),
        profileContext,
        const AlwaysStoppedAnimation<double>(0),
        const AlwaysStoppedAnimation<double>(0),
        const Text('instant-destination'),
      );
      expect(built, isA<Text>());
      expect((built as Text).data, 'instant-destination');

      await tester.tap(find.text('Sign in'));
      await tester.pumpAndSettle();
      expect(find.byKey(const Key('login-field')), findsOneWidget);
      expect(find.byKey(const Key('login-password')), findsOneWidget);
    },
  );

  testWidgets(
    'ordinary motion keeps the default navigation indicator duration',
    (tester) async {
      tester.platformDispatcher.accessibilityFeaturesTestValue =
          const FakeAccessibilityFeatures(disableAnimations: false);
      addTearDown(
        tester.platformDispatcher.clearAccessibilityFeaturesTestValue,
      );

      await tester.pumpWidget(const HireCandidateApp());
      await tester.pumpAndSettle();

      expect(
        tester
            .widget<NavigationBar>(find.byType(NavigationBar))
            .animationDuration,
        isNull,
      );
      final homeContext = tester.element(find.text('Latest Jobs'));
      final builder = Theme.of(
        homeContext,
      ).pageTransitionsTheme.builders[Theme.of(homeContext).platform];
      expect(builder, isNot(isA<InstantPageTransitionsBuilder>()));
    },
  );

  testWidgets('reduced motion does not cancel a center swipe delta', (
    tester,
  ) async {
    tester.platformDispatcher.accessibilityFeaturesTestValue =
        const FakeAccessibilityFeatures(disableAnimations: true);
    addTearDown(tester.platformDispatcher.clearAccessibilityFeaturesTestValue);

    await tester.pumpWidget(
      const MaterialApp(
        home: Scaffold(body: SizedBox(width: 400, key: Key('probe'))),
      ),
    );

    final coordinator = RootTabSwipeCoordinator(tabCount: 4);
    final context = tester.element(find.byKey(const Key('probe')));
    expect(OurNearMotion.prefersReducedMotion(context), isTrue);

    coordinator.onDragStart(
      DragStartDetails(globalPosition: const Offset(200, 300)),
      context,
    );
    final delta = coordinator.resolveTabDelta(
      DragEndDetails(
        velocity: const Velocity(pixelsPerSecond: Offset(-500, 0)),
        primaryVelocity: -500,
      ),
      context,
    );
    expect(delta, 1);
    expect(coordinator.clampIndex(0, delta), 1);
    expect(coordinator.clampIndex(3, delta), 3);
  });
}
