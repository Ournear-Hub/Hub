import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/auth/auth_service.dart';
import 'package:ournear_mobile/features/auth/candidate_session_gate.dart';
import 'package:provider/provider.dart';
import '../../helpers/fake_http_client.dart';
import 'package:http/http.dart' as http;

void main() {
  testWidgets('checking gate shows branded session message', (tester) async {
    final auth = AuthController(AuthService(apiBaseOverride: 'http://localhost'));
    await tester.pumpWidget(
      MaterialApp(
        home: ChangeNotifierProvider.value(
          value: auth,
          child: CandidateSessionGate(
            auth: auth,
            onRetry: () {},
            onBrowseJobs: () {},
            child: const Text('Shell'),
          ),
        ),
      ),
    );
    expect(find.byKey(const Key('session-gate-checking')), findsOneWidget);
    expect(find.text('Checking your session'), findsOneWidget);
  });

  testWidgets('recoverable bootstrap error offers retry and browse jobs', (
    tester,
  ) async {
    FlutterSecureStorage.setMockInitialValues({
      'ournear_mobile_bearer_token': 'saved',
    });
    final auth = AuthController(
      AuthService(
        apiBaseOverride: 'http://localhost',
        client: FakeHttpClient((_) async => http.Response('down', 503)),
      ),
    );
    await auth.bootstrap();
    expect(auth.bootstrapFailure, BootstrapFailureKind.server);
    expect(auth.hasStoredSessionToken, isTrue);

    await tester.pumpWidget(
      MaterialApp(
        home: ChangeNotifierProvider.value(
          value: auth,
          child: CandidateSessionGate(
            auth: auth,
            onRetry: auth.retryBootstrap,
            onBrowseJobs: auth.dismissBootstrapFailureForPublicBrowsing,
            child: const Text('Shell'),
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.byKey(const Key('session-gate-recoverable-error')), findsOneWidget);
    await tester.tap(find.byKey(const Key('session-gate-browse-jobs')));
    await tester.pump();
    expect(find.text('Shell'), findsOneWidget);
  });

  testWidgets('authenticated settled gate shows candidate shell child', (
    tester,
  ) async {
    FlutterSecureStorage.setMockInitialValues({
      'ournear_mobile_bearer_token': 'saved',
    });
    final auth = AuthController(
      AuthService(
        apiBaseOverride: 'http://localhost',
        client: FakeHttpClient((request) async {
          if (request.url.path.endsWith('/account')) {
            return http.Response(
              jsonEncode({
                'data': {
                  'name': 'Sam',
                  'username': 'sam',
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
      MaterialApp(
        home: ChangeNotifierProvider.value(
          value: auth,
          child: CandidateSessionGate(
            auth: auth,
            onRetry: () {},
            onBrowseJobs: () {},
            child: const Text('Candidate shell'),
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.text('Candidate shell'), findsOneWidget);
    expect(find.byKey(const Key('session-gate-checking')), findsNothing);
  });

  test('bootstrap runs once when awaited concurrently', () async {
    FlutterSecureStorage.setMockInitialValues({});
    final auth = AuthController(
      AuthService(apiBaseOverride: 'http://localhost', client: FakeHttpClient((_) async => http.Response('', 500))),
    );
    await Future.wait([auth.bootstrap(), auth.bootstrap()]);
    expect(auth.bootstrapCallCount, 1);
  });

  test('network bootstrap failure keeps stored token', () async {
    FlutterSecureStorage.setMockInitialValues({
      'ournear_mobile_bearer_token': 'saved',
    });
    final auth = AuthController(
      AuthService(
        apiBaseOverride: 'http://localhost',
        client: FakeHttpClient((_) async {
          throw Exception('socket');
        }),
      ),
    );
    await auth.bootstrap();
    expect(auth.bootstrapFailure, BootstrapFailureKind.network);
    expect(auth.hasStoredSessionToken, isTrue);
    expect(auth.status, AuthStatus.unauthenticated);
  });

  test('401 clears session on bootstrap', () async {
    FlutterSecureStorage.setMockInitialValues({
      'ournear_mobile_bearer_token': 'saved',
    });
    final auth = AuthController(
      AuthService(
        apiBaseOverride: 'http://localhost',
        client: FakeHttpClient((_) async => http.Response('expired', 401)),
      ),
    );
    await auth.bootstrap();
    expect(auth.bootstrapFailure, BootstrapFailureKind.none);
    expect(auth.hasStoredSessionToken, isFalse);
  });
}
