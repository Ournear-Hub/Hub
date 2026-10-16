import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:ournear_mobile/core/api/hire_api_exception.dart';
import 'package:ournear_mobile/core/auth/auth_service.dart';
import '../../helpers/fake_http_client.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  setUp(() {
    FlutterSecureStorage.setMockInitialValues({});
  });

  test('register stores token and parses user', () async {
    final service = AuthService(
      apiBaseOverride: 'http://localhost',
      client: FakeHttpClient((request) async {
        expect(request.url.path, '/api/v1/auth/register');
        final body = jsonDecode(request.body) as Map<String, dynamic>;
        expect(body['username'], 'new_hire');
        expect(body['accept_terms'], true);
        expect(body['accept_privacy'], true);
        return http.Response(
          jsonEncode({
            'token': 'register-token',
            'verification_email_sent': true,
            'user': {
              'name': 'New Hire',
              'username': 'new_hire',
              'role': 'candidate',
              'email_verified': false,
              'must_change_password': false,
            },
          }),
          201,
        );
      }),
    );

    final user = await service.register(
      username: 'new_hire',
      name: 'New Hire',
      mobile: '+971501234567',
      email: 'new@example.test',
      password: 'Password1!',
      passwordConfirmation: 'Password1!',
    );
    expect(user.username, 'new_hire');
    expect(service.bearerToken, 'register-token');
  });

  test('login stores token and parses user', () async {
    final service = AuthService(
      apiBaseOverride: 'http://localhost',
      client: FakeHttpClient((request) async {
        expect(request.url.path, '/api/v1/auth/login');
        final body = jsonDecode(request.body) as Map<String, dynamic>;
        expect(body['login'], 'aisha');
        expect(body['device_name'], 'ournear-mobile');
        return http.Response(
          jsonEncode({
            'token': 'test-token',
            'user': {
              'name': 'Aisha',
              'username': 'aisha',
              'role': 'candidate',
              'email_verified': true,
              'must_change_password': false,
            },
          }),
          200,
        );
      }),
    );

    final user = await service.login(login: 'aisha', password: 'Password1!');
    expect(user.username, 'aisha');
    expect(service.bearerToken, 'test-token');

    await service.restoreSession();
    expect(service.bearerToken, 'test-token');
  });

  test('login failure surfaces API message', () async {
    final service = AuthService(
      apiBaseOverride: 'http://localhost',
      client: FakeHttpClient((_) async {
        return http.Response(
          jsonEncode({'errors': {'login': ['Invalid credentials']}}),
          422,
        );
      }),
    );

    expect(
      () => service.login(login: 'x', password: 'y'),
      throwsA(
        predicate<HireApiException>((e) => e.message.contains('Invalid credentials')),
      ),
    );
  });

  test('fetchAccount clears session on 401', () async {
    var callCount = 0;
    final service = AuthService(
      apiBaseOverride: 'http://localhost',
      client: FakeHttpClient((request) async {
        callCount++;
        if (request.url.path.endsWith('/auth/login')) {
          return http.Response(jsonEncode({'token': 'expired-token', 'user': {}}), 200);
        }
        expect(request.url.path, '/api/v1/account');
        return http.Response('Unauthorized', 401);
      }),
    );

    await service.login(login: 'a', password: 'b');
    expect(service.bearerToken, 'expired-token');

    await expectLater(
      service.fetchAccount(),
      throwsA(isA<HireApiException>()),
    );
    expect(service.bearerToken, isNull);
    expect(callCount, 2);
  });
}
