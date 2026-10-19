import '../../../helpers/hire_candidate_shell_helpers.dart';
import '../../../helpers/hire_candidate_test_storage.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:ournear_mobile/app/hire_candidate_app.dart';
import 'package:ournear_mobile/core/theme/candidate_theme_controller.dart';
import 'package:ournear_mobile/core/theme/hire_dark_tokens.dart';
import 'package:ournear_mobile/core/theme/ournear_theme.dart';
import 'package:provider/provider.dart';

void main() {
  setUp(() {
    setHireCandidateTestStorage();
  });

  testWidgets('dark theme applies readable tokens across Home and Profile', (
    tester,
  ) async {
    await tester.binding.setSurfaceSize(const Size(360, 640));
    addTearDown(() => tester.binding.setSurfaceSize(null));

    await tester.pumpWidget(const HireCandidateApp());
    await tester.pumpAndSettle();
    await ensureCandidateShellVisible(tester);

    await openCandidateMenu(tester);
    await scrollMenuUntilVisible(tester, const Key('menu-dark-mode'));
    await tester.tap(find.byKey(const Key('menu-dark-mode')));
    await tester.pumpAndSettle();
    await closeCandidateMenuIfOpen(tester);

    var app = tester.widget<MaterialApp>(find.byType(MaterialApp));
    expect(app.theme?.brightness, Brightness.dark);
    expect(app.theme?.scaffoldBackgroundColor, HireDarkTokens.canvas);
    expect(app.theme?.textTheme.bodyMedium?.color, HireDarkTokens.primaryText);
    expect(app.theme?.inputDecorationTheme.fillColor, HireDarkTokens.inputFill);
    expect(app.theme?.iconTheme.color, HireDarkTokens.primaryText);
    expect(app.theme?.dividerTheme.color, HireDarkTokens.border);

    await tester.tap(candidateNavLabel('Home'));
    await tester.pumpAndSettle();
    expect(find.text('OurNear Hire'), findsOneWidget);
    expect(app.theme?.brightness, Brightness.dark);
    app = tester.widget<MaterialApp>(find.byType(MaterialApp));
    expect(app.theme?.brightness, Brightness.dark);
    expect(app.theme?.textTheme.bodyMedium?.color, HireDarkTokens.primaryText);

    await tester.tap(candidateNavLabel('Profile'));
    await tester.pumpAndSettle();
    expect(
      find.textContaining('Sign in to view and edit'),
      findsOneWidget,
    );

    await openCandidateMenu(tester);
    await scrollMenuUntilVisible(tester, const Key('menu-dark-mode'));
    await tester.tap(find.byKey(const Key('menu-dark-mode')));
    await tester.pumpAndSettle();
    await closeCandidateMenuIfOpen(tester);
    app = tester.widget<MaterialApp>(find.byType(MaterialApp));
    expect(app.theme?.brightness, Brightness.light);
    expect(app.theme?.scaffoldBackgroundColor, isNot(HireDarkTokens.canvas));
  });

  testWidgets('candidate dark controller does not change unified light theme', (
    tester,
  ) async {
    await tester.pumpWidget(
      ChangeNotifierProvider(
        create: (_) => CandidateThemeController()..load(),
        child: MaterialApp(
          theme: OurNearTheme.light(),
          home: const Scaffold(body: Text('Unified')),
        ),
      ),
    );
    await tester.pumpAndSettle();
    expect(
      tester.widget<MaterialApp>(find.byType(MaterialApp)).theme?.brightness,
      Brightness.light,
    );
  });
}
