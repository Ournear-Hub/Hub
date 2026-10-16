import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:ournear_mobile/core/navigation/root_tab_swipe_coordinator.dart';

void main() {
  test('clampIndex never wraps past first or last tab', () {
    final coordinator = RootTabSwipeCoordinator(tabCount: 4);
    expect(coordinator.clampIndex(0, -1), 0);
    expect(coordinator.clampIndex(3, 1), 3);
    expect(coordinator.clampIndex(1, 1), 2);
    expect(coordinator.clampIndex(2, -1), 1);
  });

  testWidgets('resolveTabDelta ignores swipes starting in edge inset', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        home: Builder(
          builder: (context) {
            return Scaffold(
              body: SizedBox(
                width: 400,
                key: const Key('probe'),
              ),
            );
          },
        ),
      ),
    );

    final coordinator = RootTabSwipeCoordinator(tabCount: 4);
    final context = tester.element(find.byKey(const Key('probe')));

    coordinator.onDragStart(
      DragStartDetails(globalPosition: const Offset(10, 200)),
      context,
    );
    final delta = coordinator.resolveTabDelta(
      DragEndDetails(
        velocity: Velocity(pixelsPerSecond: Offset(-500, 0)),
        primaryVelocity: -500,
      ),
      context,
    );
    expect(delta, 0);
  });

  testWidgets('resolveTabDelta advances one tab from center swipe', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        home: Builder(
          builder: (context) {
            return Scaffold(
              body: SizedBox(
                width: 400,
                key: const Key('probe'),
              ),
            );
          },
        ),
      ),
    );

    final coordinator = RootTabSwipeCoordinator(tabCount: 4);
    final context = tester.element(find.byKey(const Key('probe')));

    coordinator.onDragStart(
      DragStartDetails(globalPosition: const Offset(200, 300)),
      context,
    );
    final delta = coordinator.resolveTabDelta(
      DragEndDetails(
        velocity: Velocity(pixelsPerSecond: Offset(-500, 0)),
        primaryVelocity: -500,
      ),
      context,
    );
    expect(delta, 1);
  });

  testWidgets('resolveTabDelta ignores swipe when a nested route is open', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        home: Builder(
          builder: (context) {
            return Scaffold(
              body: ElevatedButton(
                key: const Key('open'),
                onPressed: () {
                  Navigator.of(context).push(
                    MaterialPageRoute<void>(builder: (_) => const Scaffold(body: Text('Detail'))),
                  );
                },
                child: const Text('Open'),
              ),
            );
          },
        ),
      ),
    );

    await tester.tap(find.text('Open'));
    await tester.pumpAndSettle();

    final coordinator = RootTabSwipeCoordinator(tabCount: 4);
    final context = tester.element(find.text('Detail'));

    coordinator.onDragStart(
      DragStartDetails(globalPosition: const Offset(200, 300)),
      context,
    );
    final delta = coordinator.resolveTabDelta(
      DragEndDetails(
        velocity: Velocity(pixelsPerSecond: Offset(-500, 0)),
        primaryVelocity: -500,
      ),
      context,
    );
    expect(delta, 0);
  });
}
