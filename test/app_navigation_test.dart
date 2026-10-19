import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:ournear_mobile/app/ournear_app.dart';

Finder _navLabel(String label) {
  return find.descendant(
    of: find.byType(NavigationBar),
    matching: find.text(label),
  );
}

void main() {
  setUp(() {
    FlutterSecureStorage.setMockInitialValues({});
  });

  testWidgets('bottom navigation opens each destination', (tester) async {
    await tester.pumpWidget(const OurNearApp());
    await tester.pumpAndSettle();

    expect(find.text('One platform'), findsOneWidget);

    await tester.tap(_navLabel('Vibes'));
    await tester.pumpAndSettle();
    expect(find.text('Vibes is not live yet'), findsOneWidget);

    await tester.tap(_navLabel('Drops'));
    await tester.pumpAndSettle();
    expect(find.text('Drops is not live yet'), findsOneWidget);

    await tester.tap(_navLabel('Hire'));
    await tester.pumpAndSettle();
    expect(find.textContaining('OURNEAR_API_BASE'), findsOneWidget);

    await tester.tap(_navLabel('Profile'));
    await tester.pumpAndSettle();
    expect(find.text('Sign in to OurNear'), findsOneWidget);

    await tester.tap(_navLabel('Home'));
    await tester.pumpAndSettle();
    expect(find.text('One platform'), findsOneWidget);
  });

  testWidgets('home module card opens hire tab', (tester) async {
    await tester.binding.setSurfaceSize(const Size(800, 1200));
    addTearDown(() => tester.binding.setSurfaceSize(null));

    await tester.pumpWidget(const OurNearApp());
    await tester.pumpAndSettle();

    await tester.scrollUntilVisible(find.byKey(const ValueKey('module-Hire')), 80);
    await tester.ensureVisible(find.byKey(const ValueKey('module-Hire')));
    await tester.tap(find.byKey(const ValueKey('module-Hire')));
    await tester.pumpAndSettle();
    expect(find.textContaining('OURNEAR_API_BASE'), findsWidgets);
  });
}
