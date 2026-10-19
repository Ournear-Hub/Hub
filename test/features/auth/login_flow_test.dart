import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/auth/auth_service.dart';
import 'package:ournear_mobile/features/auth/login_screen.dart';
import '../../helpers/fake_http_client.dart';
import 'package:provider/provider.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  setUp(() {
    FlutterSecureStorage.setMockInitialValues({});
  });

  testWidgets('login success pops screen', (tester) async {
    final auth = AuthController(
      AuthService(
        apiBaseOverride: 'http://localhost',
        client: FakeHttpClient((request) async {
          if (request.url.path.endsWith('/auth/login')) {
            return http.Response(
              jsonEncode({
                'token': 'tok',
                'user': {
                  'name': 'Test User',
                  'username': 'testuser',
                  'role': 'candidate',
                  'email_verified': true,
                  'must_change_password': false,
                },
              }),
              200,
            );
          }
          return http.Response('', 404);
        }),
      ),
    );
    await auth.bootstrap();

    await tester.pumpWidget(
      ChangeNotifierProvider.value(
        value: auth,
        child: MaterialApp(
          home: Scaffold(
            body: Builder(
              builder: (context) {
                return FilledButton(
                  onPressed: () {
                    Navigator.of(context).push(
                      MaterialPageRoute<void>(builder: (_) => const LoginScreen()),
                    );
                  },
                  child: const Text('Open login'),
                );
              },
            ),
          ),
        ),
      ),
    );

    await tester.tap(find.text('Open login'));
    await tester.pumpAndSettle();

    await tester.enterText(find.byKey(const Key('login-field')), 'testuser');
    await tester.enterText(find.byKey(const Key('login-password')), 'secret');
    await tester.tap(find.byKey(const ValueKey('login-submit')));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 300));

    expect(auth.status, AuthStatus.authenticated);
    await tester.pumpAndSettle();
    expect(find.byType(LoginScreen), findsNothing);
    expect(auth.status, AuthStatus.authenticated);
  });

  testWidgets('login failure shows error', (tester) async {
    final auth = AuthController(
      AuthService(
        apiBaseOverride: 'http://localhost',
        client: FakeHttpClient((_) async {
          return http.Response(jsonEncode({'errors': {'login': ['Wrong password']}}), 422);
        }),
      ),
    );
    await auth.bootstrap();

    await tester.pumpWidget(
      ChangeNotifierProvider.value(
        value: auth,
        child: const MaterialApp(home: LoginScreen()),
      ),
    );
    await tester.pumpAndSettle();

    await tester.enterText(find.byKey(const Key('login-field')), 'u');
    await tester.enterText(find.byKey(const Key('login-password')), 'p');
    await tester.tap(find.byKey(const ValueKey('login-submit')));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 300));

    expect(find.byKey(const Key('login-error')), findsOneWidget);
    expect(find.text('Wrong password'), findsOneWidget);
    expect(auth.status, AuthStatus.unauthenticated);
  });

  testWidgets('login ignores duplicate submit while busy', (tester) async {
    var loginCalls = 0;
    final auth = AuthController(
      AuthService(
        apiBaseOverride: 'http://localhost',
        client: FakeHttpClient((request) async {
          if (request.url.path.endsWith('/auth/login')) {
            loginCalls += 1;
            await Future<void>.delayed(const Duration(milliseconds: 200));
            return http.Response(
              jsonEncode({
                'token': 'tok',
                'user': {
                  'name': 'Test User',
                  'username': 'testuser',
                  'role': 'candidate',
                  'email_verified': true,
                  'must_change_password': false,
                },
              }),
              200,
            );
          }
          return http.Response('', 404);
        }),
      ),
    );
    await auth.bootstrap();

    await tester.pumpWidget(
      ChangeNotifierProvider.value(
        value: auth,
        child: const MaterialApp(home: LoginScreen()),
      ),
    );
    await tester.pumpAndSettle();

    await tester.enterText(find.byKey(const Key('login-field')), 'testuser');
    await tester.enterText(find.byKey(const Key('login-password')), 'secret');
    await tester.tap(find.byKey(const ValueKey('login-submit')));
    await tester.tap(find.byKey(const ValueKey('login-submit')));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));
    expect(loginCalls, 1);
  });
}
