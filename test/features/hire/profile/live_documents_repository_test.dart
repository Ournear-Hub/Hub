import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:ournear_mobile/core/api/hire_api_service.dart';
import 'package:ournear_mobile/core/auth/auth_service.dart';
import 'package:ournear_mobile/features/hire/profile/data/documents_repository.dart';

import '../../../helpers/fake_http_client.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  setUp(() {
    FlutterSecureStorage.setMockInitialValues({});
  });

  test('upload sends multipart resume file', () async {
    var uploaded = false;
    final client = FakeHttpClient((request) async {
      if (request.url.path == '/api/v1/auth/login') {
        return http.Response(
          jsonEncode({'token': 't', 'user': {'username': 'u'}}),
          200,
        );
      }
      if (request.method == 'POST' && request.url.path.endsWith('/resumes')) {
        uploaded = true;
        return http.Response(
          jsonEncode({
            'data': {'original_filename': 'cv.pdf', 'title': 'cv.pdf'},
          }),
          201,
        );
      }
      if (request.url.path.endsWith('/resumes/builder')) {
        return http.Response(
          jsonEncode({'data': {'preview': {'text': 'CV'}}}),
          200,
        );
      }
      if (request.url.path.endsWith('/resumes') && request.method == 'GET') {
        return http.Response(
          jsonEncode({
            'data': [
              {'original_filename': 'cv.pdf', 'title': 'cv.pdf'},
            ],
          }),
          200,
        );
      }
      return http.Response('{}', 404);
    });
    final auth = AuthService(apiBaseOverride: 'http://localhost', client: client);
    await auth.login(login: 'u', password: 'x');
    final repository = LiveDocumentsRepository(
      api: HireApiService(
        apiBaseOverride: 'http://localhost',
        client: client,
        authService: auth,
      ),
    );

    final snapshot = await repository.replaceUploadedResume(
      'cv.pdf',
      bytes: [1, 2, 3],
    );
    expect(uploaded, isTrue);
    expect(snapshot.persisted, isTrue);
    expect(snapshot.uploadedResume?.fileName, 'cv.pdf');
  });

  test('delete and make primary call the resume id routes', () async {
    final calls = <String>[];
    final client = FakeHttpClient((request) async {
      if (request.url.path == '/api/v1/auth/login') {
        return http.Response(
          jsonEncode({'token': 't', 'user': {'username': 'u'}}),
          200,
        );
      }
      if (request.url.path.endsWith('/resumes/builder')) {
        return http.Response(jsonEncode({'data': {'preview': {'text': 'CV'}}}), 200);
      }
      if (request.method == 'DELETE' && request.url.path.endsWith('/resumes/4')) {
        calls.add('delete');
        return http.Response('', 204);
      }
      if (request.method == 'POST' &&
          request.url.path.endsWith('/resumes/4/primary')) {
        calls.add('primary');
        return http.Response(jsonEncode({'data': {'id': 4, 'is_primary': true}}), 200);
      }
      if (request.method == 'GET' && request.url.path.endsWith('/resumes')) {
        return http.Response(
          jsonEncode({
            'data': [
              {
                'id': 4,
                'original_filename': 'cv.pdf',
                'title': 'cv.pdf',
                'is_primary': calls.contains('primary'),
              },
            ],
          }),
          200,
        );
      }
      return http.Response('{}', 404);
    });
    final auth = AuthService(apiBaseOverride: 'http://localhost', client: client);
    await auth.login(login: 'u', password: 'x');
    final repository = LiveDocumentsRepository(
      api: HireApiService(
        apiBaseOverride: 'http://localhost',
        client: client,
        authService: auth,
      ),
    );

    final listed = await repository.fetchDocuments();
    expect(listed.library.single.id, 4);
    expect(listed.library.single.isPrimary, isFalse);
    final primary = await repository.makeResumePrimary(4);
    expect(primary.persisted, isTrue);
    expect(primary.library.single.isPrimary, isTrue);
    await repository.deleteResume(4);
    expect(calls, ['primary', 'delete']);
  });
}
