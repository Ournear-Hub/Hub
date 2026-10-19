import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:ournear_mobile/core/theme/ournear_theme.dart';
import 'package:ournear_mobile/core/widgets/platform_empty_state.dart';
import 'package:ournear_mobile/core/widgets/platform_error_state.dart';
import 'package:ournear_mobile/core/widgets/platform_loading_state.dart';
import 'package:ournear_mobile/core/widgets/platform_offline_state.dart';
import 'package:ournear_mobile/core/widgets/platform_success_state.dart';

void main() {
  testWidgets('foundation async states render accessible titles', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: OurNearTheme.hireLight(),
        home: Scaffold(
          body: ListView(
            children: const [
              PlatformLoadingState(message: 'Loading jobs'),
              PlatformEmptyState(title: 'Empty', message: 'Nothing here'),
              PlatformErrorState(title: 'Error', message: 'Failed'),
              PlatformOfflineState(),
              PlatformSuccessState(title: 'Done', message: 'Complete'),
            ],
          ),
        ),
      ),
    );

    await tester.scrollUntilVisible(find.text('Done'), 120);

    expect(find.text('Loading jobs'), findsOneWidget);
    expect(find.text('Empty'), findsOneWidget);
    expect(find.text('Error'), findsOneWidget);
    expect(find.text('Offline'), findsOneWidget);
    expect(find.text('Done'), findsOneWidget);
  });
}
