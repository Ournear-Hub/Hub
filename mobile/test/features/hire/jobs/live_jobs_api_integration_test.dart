import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:ournear_mobile/core/api/hire_api_service.dart';
import 'package:ournear_mobile/core/auth/auth_service.dart';
import 'package:ournear_mobile/features/hire/jobs/data/jobs_repository.dart';
import '../../../helpers/fake_http_client.dart';

void main() {
  setUp(() {
    FlutterSecureStorage.setMockInitialValues({});
  });

  test('fetchJobs parses empty list', () async {
    final client = FakeHttpClient((request) async {
      expect(request.url.path, '/api/v1/jobs');
      return http.Response(
        jsonEncode({
          'data': [],
          'meta': {'current_page': 1, 'last_page': 1, 'total': 0},
          'links': {},
        }),
        200,
      );
    });
    final repository = LiveJobsRepository(
      service: HireApiService(
        apiBaseOverride: 'http://localhost',
        client: client,
      ),
    );

    final page = await repository.fetchJobs();
    expect(page.jobs, isEmpty);
    expect(page.source, JobsDataSource.liveApi);
  });

  test('fetchJobDetail includes is_saved when bearer present', () async {
    TestWidgetsFlutterBinding.ensureInitialized();
    final client = FakeHttpClient((request) async {
      if (request.url.path == '/api/v1/auth/login') {
        return http.Response(
          jsonEncode({
            'token': 'mobile-token',
            'user': {'username': 'test', 'role': 'candidate'},
          }),
          200,
        );
      }
      expect(request.headers['authorization'], 'Bearer mobile-token');
      return http.Response(
        jsonEncode({
          'data': {
            'title': 'Welder',
            'slug': 'welder',
            'is_saved': true,
          },
        }),
        200,
      );
    });
    final auth = AuthService(apiBaseOverride: 'http://localhost', client: client);
    await auth.login(login: 'test', password: 'secret');
    final api = HireApiService(
      apiBaseOverride: 'http://localhost',
      client: client,
      authService: auth,
    );
    final repository = LiveJobsRepository(service: api);

    final job = await repository.fetchJobDetail('welder');
    expect(job.isSaved, isTrue);
  });

  test('fetchSavedJobs loads authenticated collection', () async {
    TestWidgetsFlutterBinding.ensureInitialized();
    final client = FakeHttpClient((request) async {
      if (request.url.path == '/api/v1/auth/login') {
        return http.Response(
          jsonEncode({
            'token': 'mobile-token',
            'user': {'username': 'test', 'role': 'candidate'},
          }),
          200,
        );
      }
      expect(request.url.path, '/api/v1/account/saved-jobs');
      return http.Response(
        jsonEncode({
          'data': [
            {
              'title': 'Saved role',
              'slug': 'saved-role',
              'is_saved': true,
            },
          ],
          'meta': {'current_page': 1, 'last_page': 1, 'total': 1},
        }),
        200,
      );
    });
    final auth = AuthService(apiBaseOverride: 'http://localhost', client: client);
    await auth.login(login: 'test', password: 'secret');
    final repository = LiveJobsRepository(
      service: HireApiService(
        apiBaseOverride: 'http://localhost',
        client: client,
        authService: auth,
      ),
    );

    final page = await repository.fetchSavedJobs();
    expect(page.jobs.single.slug, 'saved-role');
    expect(page.source, JobsDataSource.liveApi);
  });

  test('setJobSaved posts to saved-jobs endpoint', () async {
    TestWidgetsFlutterBinding.ensureInitialized();
    var method = '';
    final client = FakeHttpClient((request) async {
      if (request.url.path == '/api/v1/auth/login') {
        return http.Response(
          jsonEncode({
            'token': 'mobile-token',
            'user': {'username': 'test', 'role': 'candidate'},
          }),
          200,
        );
      }
      method = request.method;
      return http.Response(
        jsonEncode({
          'data': {'is_saved': true, 'job_slug': 'welder'},
        }),
        201,
      );
    });
    final auth = AuthService(apiBaseOverride: 'http://localhost', client: client);
    await auth.login(login: 'test', password: 'secret');
    final api = HireApiService(
      apiBaseOverride: 'http://localhost',
      client: client,
      authService: auth,
    );
    final repository = LiveJobsRepository(service: api);

    final saved = await repository.setJobSaved('welder', save: true);
    expect(method, 'POST');
    expect(saved, isTrue);
  });
}
