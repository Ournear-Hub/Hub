import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:ournear_mobile/core/api/hire_api_service.dart';
import 'package:ournear_mobile/core/auth/auth_service.dart';
import 'package:ournear_mobile/features/hire/applications/data/applications_repository.dart';
import '../../../helpers/fake_http_client.dart';

Future<AuthService> _authedClient(FakeHttpClient client) async {
  final auth = AuthService(apiBaseOverride: 'http://localhost', client: client);
  await auth.login(login: 'test', password: 'secret');
  return auth;
}

http.Response _loginResponse() {
  return http.Response(
    jsonEncode({
      'token': 'test-token',
      'user': {'username': 'test', 'role': 'candidate'},
    }),
    200,
  );
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  setUp(() {
    FlutterSecureStorage.setMockInitialValues({});
  });

  test('fetchApplications maps Laravel list payload', () async {
    final client = FakeHttpClient((request) async {
      if (request.url.path == '/api/v1/auth/login') {
        return _loginResponse();
      }
      if (request.url.path == '/api/v1/account/applications') {
        expect(request.headers['authorization'], 'Bearer test-token');
        return http.Response(
          jsonEncode({
            'data': [
              {
                'id': 9,
                'business_id': 'ONT-A-000009',
                'status': 'In review',
                'applied_at': '2026-03-01 10:00:00',
                'has_resume': true,
                'job': {
                  'title': 'Electrician',
                  'slug': 'electrician',
                  'company': 'Build Co',
                  'location': 'Pune',
                },
              },
            ],
            'meta': {'current_page': 1, 'last_page': 1, 'total': 1},
          }),
          200,
        );
      }
      return http.Response('Not found', 404);
    });

    final auth = await _authedClient(client);
    final api = HireApiService(
      apiBaseOverride: 'http://localhost',
      client: client,
      authService: auth,
    );
    final repository = LiveApplicationsRepository(api: api);

    final result = await repository.fetchApplications();
    expect(result.source, ApplicationsDataSource.liveApi);
    expect(result.applications.single.roleTitle, 'Electrician');
    expect(result.applications.single.statusLabel, 'In review');
    expect(result.applications.single.hasResume, isTrue);
  });

  test('fetchApplications loads additional pages from meta', () async {
    var applicationRequests = 0;
    final client = FakeHttpClient((request) async {
      if (request.url.path == '/api/v1/auth/login') {
        return _loginResponse();
      }
      if (request.url.path == '/api/v1/account/applications') {
        applicationRequests += 1;
        final page = int.parse(request.url.queryParameters['page'] ?? '1');
        if (page == 1) {
          return http.Response(
            jsonEncode({
              'data': [
                {
                  'id': 1,
                  'status': 'Submitted',
                  'applied_at': '2026-03-01 10:00:00',
                  'job': {'title': 'Role A', 'slug': 'role-a'},
                },
              ],
              'meta': {'current_page': 1, 'last_page': 2, 'total': 2},
            }),
            200,
          );
        }
        return http.Response(
          jsonEncode({
            'data': [
              {
                'id': 2,
                'status': 'In review',
                'applied_at': '2026-03-02 10:00:00',
                'job': {'title': 'Role B', 'slug': 'role-b'},
              },
            ],
            'meta': {'current_page': 2, 'last_page': 2, 'total': 2},
          }),
          200,
        );
      }
      return http.Response('Not found', 404);
    });

    final auth = await _authedClient(client);
    final repository = LiveApplicationsRepository(
      api: HireApiService(
        apiBaseOverride: 'http://localhost',
        client: client,
        authService: auth,
      ),
    );

    final result = await repository.fetchApplications();
    expect(applicationRequests, 2);
    expect(result.applications, hasLength(2));
    expect(result.applications.map((a) => a.roleTitle), ['Role A', 'Role B']);
  });

  test('fetchApplication loads detail by id', () async {
    final client = FakeHttpClient((request) async {
      if (request.url.path == '/api/v1/auth/login') {
        return _loginResponse();
      }
      if (request.url.path == '/api/v1/account/applications/42') {
        return http.Response(
          jsonEncode({
            'data': {
              'id': 42,
              'business_id': 'ONT-A-000042',
              'status': 'Shortlisted',
              'applied_at': '2026-04-01 09:00:00',
              'has_resume': true,
              'job': {
                'title': 'Plumber',
                'slug': 'plumber',
                'company': 'Pipe Ltd',
              },
            },
          }),
          200,
        );
      }
      return http.Response('Not found', 404);
    });

    final auth = await _authedClient(client);
    final repository = LiveApplicationsRepository(
      api: HireApiService(
        apiBaseOverride: 'http://localhost',
        client: client,
        authService: auth,
      ),
    );

    final application = await repository.fetchApplication('42');
    expect(application.statusLabel, 'Shortlisted');
    expect(application.hasResume, isTrue);
    expect(application.jobSlug, 'plumber');
  });

  test('submitApplication confirms server application id', () async {
    final client = FakeHttpClient((request) async {
      if (request.url.path == '/api/v1/auth/login') {
        return _loginResponse();
      }
      if (request.url.path == '/api/v1/jobs/plumber/apply') {
        expect(request.method, 'POST');
        return http.Response(
          jsonEncode({
            'data': {
              'application_id': 77,
              'status': 'Submitted',
              'business_id': 'ONT-A-000077',
            },
          }),
          200,
        );
      }
      return http.Response('Not found', 404);
    });

    final auth = await _authedClient(client);
    final repository = LiveApplicationsRepository(
      api: HireApiService(
        apiBaseOverride: 'http://localhost',
        client: client,
        authService: auth,
      ),
    );

    final result = await repository.submitApplication(
      const ApplicationDraft(
        jobSlug: 'plumber',
        jobTitle: 'Plumber',
        companyName: 'Pipe Ltd',
      ),
    );
    expect(result.confirmedByServer, isTrue);
    expect(result.source, ApplicationsDataSource.liveApi);
    expect(result.application.id, '77');
  });

  test('downloadResume returns bytes from authorized endpoint', () async {
    final client = FakeHttpClient((request) async {
      if (request.url.path == '/api/v1/auth/login') {
        return _loginResponse();
      }
      if (request.url.path == '/api/v1/account/applications/9/resume') {
        return http.Response.bytes([1, 2, 3, 4], 200);
      }
      return http.Response('Not found', 404);
    });

    final auth = await _authedClient(client);
    final repository = LiveApplicationsRepository(
      api: HireApiService(
        apiBaseOverride: 'http://localhost',
        client: client,
        authService: auth,
      ),
    );

    final response = await repository.downloadResume('9');
    expect(response.bodyBytes, [1, 2, 3, 4]);
  });

  test('submitApplication surfaces email verification requirement', () async {
    final client = FakeHttpClient((request) async {
      if (request.url.path == '/api/v1/auth/login') {
        return _loginResponse();
      }
      return http.Response(
        jsonEncode({
          'message': 'Verify your email to apply for jobs.',
          'error_code': 'email_verification_required',
        }),
        403,
      );
    });
    final auth = await _authedClient(client);
    final api = HireApiService(
      apiBaseOverride: 'http://localhost',
      client: client,
      authService: auth,
    );
    final repository = LiveApplicationsRepository(api: api);

    expect(
      () => repository.submitApplication(
        const ApplicationDraft(
          jobSlug: 'electrician',
          jobTitle: 'Electrician',
          companyName: 'Build Co',
        ),
      ),
      throwsA(
        isA<ApplicationsRepositoryException>().having(
          (error) => error.kind,
          'kind',
          ApplicationsFailureKind.verificationRequired,
        ),
      ),
    );
  });
}
