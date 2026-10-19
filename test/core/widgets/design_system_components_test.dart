import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:ournear_mobile/core/layout/ournear_responsive_frame.dart';
import 'package:ournear_mobile/core/theme/ournear_theme.dart';
import 'package:ournear_mobile/core/widgets/ournear_secondary_button.dart';
import 'package:ournear_mobile/core/widgets/platform_success_state.dart';

void main() {
  testWidgets('secondary button and success state render', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: OurNearTheme.hireLight(),
        home: Scaffold(
          body: Column(
            children: [
              OurNearSecondaryButton(label: 'Secondary', onPressed: () {}),
              const PlatformSuccessState(title: 'Saved', message: 'Changes stored locally.'),
            ],
          ),
        ),
      ),
    );

    expect(find.text('Secondary'), findsOneWidget);
    expect(find.text('Saved'), findsOneWidget);
  });

  testWidgets('responsive frame constrains width on wide screens', (tester) async {
    await tester.binding.setSurfaceSize(const Size(1200, 800));
    addTearDown(() => tester.binding.setSurfaceSize(null));

    await tester.pumpWidget(
      const MaterialApp(
        home: Scaffold(
          body: OurNearResponsiveFrame(
            child: SizedBox(width: double.infinity, height: 40, key: Key('inner')),
          ),
        ),
      ),
    );

    final box = tester.renderObject<RenderBox>(find.byKey(const Key('inner')));
    expect(box.size.width, 720);
  });
}
