import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:ournear_mobile/core/auth/auth_controller.dart' show AuthController, AuthStatus, BootstrapFailureKind;
import 'package:ournear_mobile/core/auth/auth_service.dart';
import '../../helpers/fake_http_client.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  setUp(() {
    FlutterSecureStorage.setMockInitialValues({});
  });

  test('bootstrap unauthenticated when no stored token', () async {
    final controller = AuthController(
      AuthService(apiBaseOverride: 'http://localhost', client: FakeHttpClient((_) async => http.Response('', 500))),
    );
    await controller.bootstrap();
    expect(controller.status, AuthStatus.unauthenticated);
    expect(controller.user, isNull);
  });

  test('bootstrap restores authenticated session from stored token', () async {
    FlutterSecureStorage.setMockInitialValues({'ournear_mobile_bearer_token': 'saved-token'});

    final controller = AuthController(
      AuthService(
        apiBaseOverride: 'http://localhost',
        client: FakeHttpClient((request) async {
          expect(headerValue(request, 'Authorization'), 'Bearer saved-token');
          return http.Response(
            jsonEncode({
              'data': {
                'name': 'Sam',
                'username': 'sam',
                'role': 'employer',
                'email_verified': true,
                'must_change_password': false,
              },
            }),
            200,
          );
        }),
      ),
    );
    await controller.bootstrap();
    expect(controller.status, AuthStatus.authenticated);
    expect(controller.user?.username, 'sam');
  });

  test('login failure keeps unauthenticated state', () async {
    final controller = AuthController(
      AuthService(
        apiBaseOverride: 'http://localhost',
        client: FakeHttpClient((_) async {
          return http.Response(jsonEncode({'errors': {'login': ['Nope']}}), 422);
        }),
      ),
    );
    await controller.bootstrap();
    final ok = await controller.login('bad', 'bad');
    expect(ok, isFalse);
    expect(controller.status, AuthStatus.unauthenticated);
    expect(controller.errorMessage, isNotNull);
  });

  test('bootstrap keeps token on server error when verifying account', () async {
    FlutterSecureStorage.setMockInitialValues({'ournear_mobile_bearer_token': 'saved-token'});

    final service = AuthService(
      apiBaseOverride: 'http://localhost',
      client: FakeHttpClient((_) async => http.Response('fail', 503)),
    );
    final controller = AuthController(service);
    await controller.bootstrap();
    expect(controller.status, AuthStatus.unauthenticated);
    expect(controller.bootstrapFailure, BootstrapFailureKind.server);
    expect(service.bearerToken, 'saved-token');
  });

  test('logout clears authenticated state', () async {
    final controller = AuthController(
      AuthService(
        apiBaseOverride: 'http://localhost',
        client: FakeHttpClient((request) async {
          if (request.url.path.endsWith('/auth/login')) {
            return http.Response(
              jsonEncode({
                'token': 't',
                'user': {'name': 'A', 'username': 'a', 'role': 'candidate', 'email_verified': true, 'must_change_password': false},
              }),
              200,
            );
          }
          if (request.url.path.endsWith('/auth/logout')) {
            return http.Response('', 204);
          }
          return http.Response('', 404);
        }),
      ),
    );
    await controller.login('a', 'b');
    expect(controller.status, AuthStatus.authenticated);
    await controller.logout();
    expect(controller.status, AuthStatus.unauthenticated);
    expect(controller.user, isNull);
  });
}
