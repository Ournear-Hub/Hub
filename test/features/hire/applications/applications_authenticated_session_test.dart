import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:ournear_mobile/app/hire_candidate_app.dart';
import 'package:ournear_mobile/core/api/hire_api_service.dart';
import 'package:ournear_mobile/core/auth/auth_service.dart';

import '../../../helpers/fake_http_client.dart';
import '../../../helpers/hire_candidate_shell_helpers.dart';
import '../../../helpers/hire_candidate_test_storage.dart';

/// Matches [AuthService] token storage key.
const _tokenStorageKey = 'ournear_mobile_bearer_token';

http.Response _accountResponse() {
  return http.Response(
    jsonEncode({
      'data': {
        'name': 'Asha',
        'username': 'asha_hire',
        'role': 'candidate',
        'email_verified': true,
        'must_change_password': false,
      },
    }),
    200,
  );
}

http.Response _applicationsResponse() {
  return http.Response(
    jsonEncode({
      'data': [
        {
          'id': 9,
          'status': 'In review',
          'applied_at': '2026-03-01 10:00:00',
          'job': {
            'title': 'Electrician',
            'slug': 'electrician',
            'company': 'Build Co',
          },
        },
      ],
      'meta': {'current_page': 1, 'last_page': 1, 'total': 1},
    }),
    200,
  );
}

FakeHttpClient _authedCandidateHttp() {
  return FakeHttpClient((request) async {
    final path = request.url.path;
    if (path == '/api/v1/account') {
      expect(request.headers['authorization'], 'Bearer session-token');
      return _accountResponse();
    }
    if (path == '/api/v1/account/applications') {
      expect(request.headers['authorization'], 'Bearer session-token');
      return _applicationsResponse();
    }
    if (path == '/api/v1/site/branding') {
      return http.Response(jsonEncode({'data': {}}), 200);
    }
    if (path.startsWith('/api/v1/jobs')) {
      return http.Response(
        jsonEncode({'data': [], 'meta': {'total': 0}}),
        200,
      );
    }
    return http.Response('Not found', 404);
  });
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  setUp(() {
    setHireCandidateTestStorage({_tokenStorageKey: 'session-token'});
  });

  testWidgets(
    'authenticated session loads applications without sign-in wall',
    (tester) async {
      await tester.binding.setSurfaceSize(const Size(420, 900));
      addTearDown(() => tester.binding.setSurfaceSize(null));

      final client = _authedCandidateHttp();
      final auth = AuthService(
        apiBaseOverride: 'http://localhost',
        client: client,
      );
      final api = HireApiService(
        apiBaseOverride: 'http://localhost',
        client: client,
        authService: auth,
      );

      await tester.pumpWidget(
        HireCandidateApp(authService: auth, apiService: api),
      );
      await tester.pumpAndSettle();
      await ensureCandidateShellVisible(tester);

      await tester.tap(candidateNavLabel('Applications'));
      await tester.pumpAndSettle();

      expect(find.byKey(const Key('applications-sign-in')), findsNothing);
      expect(find.text('Sign in to load application history'), findsNothing);
      expect(find.text('Electrician'), findsOneWidget);
      expect(find.text('In review'), findsWidgets);
    },
  );

  testWidgets('applications tab uses injected repository bearer token', (
    tester,
  ) async {
    await tester.binding.setSurfaceSize(const Size(420, 900));
    addTearDown(() => tester.binding.setSurfaceSize(null));

    var applicationCalls = 0;
    final client = FakeHttpClient((request) async {
      if (request.url.path == '/api/v1/account') {
        return _accountResponse();
      }
      if (request.url.path == '/api/v1/account/applications') {
        applicationCalls += 1;
        if (request.headers['authorization'] == 'Bearer session-token') {
          return _applicationsResponse();
        }
        return http.Response(jsonEncode({'message': 'Unauthenticated'}), 401);
      }
      if (request.url.path == '/api/v1/site/branding') {
        return http.Response(jsonEncode({'data': {}}), 200);
      }
      if (request.url.path.startsWith('/api/v1/jobs')) {
        return http.Response(
          jsonEncode({'data': [], 'meta': {'total': 0}}),
          200,
        );
      }
      return http.Response('Not found', 404);
    });

    final auth = AuthService(
      apiBaseOverride: 'http://localhost',
      client: client,
    );
    final api = HireApiService(
      apiBaseOverride: 'http://localhost',
      client: client,
      authService: auth,
    );

    await tester.pumpWidget(
      HireCandidateApp(authService: auth, apiService: api),
    );
    await tester.pumpAndSettle();
    await ensureCandidateShellVisible(tester);

    await tester.tap(candidateNavLabel('Applications'));
    await tester.pumpAndSettle();

    expect(applicationCalls, greaterThan(0));
    expect(find.text('Electrician'), findsOneWidget);
  });
}
