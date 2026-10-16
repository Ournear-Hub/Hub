import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:ournear_mobile/core/theme/ournear_theme.dart';
import 'package:ournear_mobile/features/auth/login_screen.dart';
import 'package:ournear_mobile/features/hire/widgets/hire_brand_mark.dart';
import 'package:ournear_mobile/features/hire/widgets/hire_notice_banner.dart';
import 'package:ournear_mobile/features/hire/widgets/hire_welcome_banner.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/auth/auth_service.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;
import 'package:provider/provider.dart';

import '../../../helpers/fake_http_client.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  setUp(() {
    FlutterSecureStorage.setMockInitialValues({});
  });

  testWidgets('HireNoticeBanner exposes demo styling', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: OurNearTheme.hireLight(),
        home: const Scaffold(
          body: HireNoticeBanner(message: 'Demo notice'),
        ),
      ),
    );
    expect(find.byKey(const Key('hire-notice-banner')), findsOneWidget);
    expect(find.text('Demo notice'), findsOneWidget);
  });

  testWidgets('HireBrandMark renders keyed wordmark', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: OurNearTheme.hireLight(),
        home: const Scaffold(body: HireBrandMark()),
      ),
    );
    expect(find.byKey(const Key('hire-brand-mark')), findsOneWidget);
  });

  testWidgets('HireWelcomeBanner keeps home key', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: OurNearTheme.hireLight(),
        home: const Scaffold(
          body: HireWelcomeBanner(message: 'Subtitle copy'),
        ),
      ),
    );
    expect(find.byKey(const Key('home-welcome-banner')), findsOneWidget);
  });

  testWidgets('LoginScreen shows hire brand mark', (tester) async {
    final auth = AuthController(
      AuthService(
        apiBaseOverride: 'http://localhost',
        client: FakeHttpClient((_) async => http.Response('', 404)),
      ),
    );
    await auth.bootstrap();
    await tester.pumpWidget(
      ChangeNotifierProvider.value(
        value: auth,
        child: MaterialApp(
          theme: OurNearTheme.hireLight(),
          home: const LoginScreen(),
        ),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.byKey(const Key('hire-brand-mark')), findsOneWidget);
    expect(find.byKey(const Key('login-field')), findsOneWidget);
  });
}
