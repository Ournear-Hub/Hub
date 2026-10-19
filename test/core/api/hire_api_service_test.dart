import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:ournear_mobile/core/api/hire_api_exception.dart';
import 'package:ournear_mobile/core/api/hire_api_service.dart';

class _FakeClient extends http.BaseClient {
  _FakeClient(this.handler);

  final Future<http.Response> Function(http.Request request) handler;

  @override
  Future<http.StreamedResponse> send(http.BaseRequest request) async {
    final response = await handler(http.Request(request.method, request.url));
    return http.StreamedResponse(
      Stream.value(response.bodyBytes),
      response.statusCode,
      headers: response.headers,
      request: request,
    );
  }
}

void main() {
  test('fetchJobs parses successful response', () async {
    final service = HireApiService(
      apiBaseOverride: 'http://localhost',
      client: _FakeClient((request) async {
        expect(request.url.path, '/api/v1/jobs');
        return http.Response(
          jsonEncode({
            'data': [
              {'title': 'Electrician', 'slug': 'electrician'},
            ],
            'meta': {'current_page': 1, 'last_page': 1, 'total': 1},
            'links': {'next': null},
          }),
          200,
          headers: {'content-type': 'application/json'},
        );
      }),
    );

    final page = await service.fetchJobs();
    expect(page.jobs.single.title, 'Electrician');
  });

  test('fetchJobs handles empty list', () async {
    final service = HireApiService(
      apiBaseOverride: 'http://localhost',
      client: _FakeClient((_) async {
        return http.Response(
          jsonEncode({
            'data': [],
            'meta': {'current_page': 1, 'last_page': 1, 'total': 0},
            'links': {'next': null},
          }),
          200,
        );
      }),
    );

    final page = await service.fetchJobs();
    expect(page.jobs, isEmpty);
    expect(page.total, 0);
  });

  test('fetchJobs throws on server error', () async {
    final service = HireApiService(
      apiBaseOverride: 'http://localhost',
      client: _FakeClient((_) async => http.Response('Server error', 500)),
    );

    expect(() => service.fetchJobs(), throwsA(isA<HireApiException>()));
  });
}
