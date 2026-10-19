import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:ournear_mobile/core/api/hire_api_service.dart';
import 'package:ournear_mobile/core/auth/auth_service.dart';
import 'package:ournear_mobile/features/hire/profile/data/profile_repository.dart';
import 'package:ournear_mobile/features/hire/profile/models/candidate_profile.dart';

import '../../../helpers/fake_http_client.dart';

Future<HireApiService> _authedApi(FakeHttpClient client) async {
  final auth = AuthService(apiBaseOverride: 'http://localhost', client: client);
  await auth.login(login: 'test', password: 'secret');
  return HireApiService(
    apiBaseOverride: 'http://localhost',
    client: client,
    authService: auth,
  );
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

  test('fetchProfile maps personal and professional payloads', () async {
    final client = FakeHttpClient((request) async {
      if (request.url.path == '/api/v1/auth/login') {
        return _loginResponse();
      }
      if (request.url.path.endsWith('/personal')) {
        return http.Response(
          jsonEncode({
            'data': {
              'bio': 'Builder',
              'location': 'Dubai',
              'professional_headline': 'Foreman',
            },
          }),
          200,
        );
      }
      return http.Response(
        jsonEncode({
          'data': {
            'employment_preference': 'Site roles',
            'skills': [
              {'name': 'Safety'},
            ],
            'employment_histories': [
              {
                'designation': 'Supervisor',
                'company': 'Build Co',
                'is_current': true,
              },
            ],
            'qualification': 'Diploma',
            'educations': [],
            'certifications': [],
          },
        }),
        200,
      );
    });

    final api = await _authedApi(client);
    final repository = LiveProfileRepository(api: api);

    final snapshot = await repository.fetchProfile();
    expect(snapshot.source, ProfileDataSource.liveApi);
    expect(snapshot.profile.summary, 'Builder');
    expect(snapshot.profile.skills, ['Safety']);
    expect(snapshot.profile.experience.single.organization, 'Build Co');
  });

  test('save summary patches personal bio', () async {
    var patched = false;
    final client = FakeHttpClient((request) async {
      if (request.url.path == '/api/v1/auth/login') {
        return _loginResponse();
      }
      if (request.method == 'GET' &&
          request.url.path.endsWith('/personal')) {
        return http.Response(
          jsonEncode({
            'data': {
              'name': 'Asha',
              'username': 'asha',
              'preferred_language': 'en',
            },
          }),
          200,
        );
      }
      if (request.method == 'PATCH' &&
          request.url.path.endsWith('/personal')) {
        patched = true;
        final body = jsonDecode(request.body) as Map<String, dynamic>;
        expect(body['bio'], 'Updated summary');
        return http.Response(
          jsonEncode({'data': {'bio': 'Updated summary'}}),
          200,
        );
      }
      return http.Response(
        jsonEncode({
          'data': {
            'bio': 'Updated summary',
            'employment_preference': '',
            'skills': [],
            'employment_histories': [],
          },
        }),
        200,
      );
    });

    final api = await _authedApi(client);
    final repository = LiveProfileRepository(api: api);

    final result = await repository.save(
      ProfileSection.summary,
      const ProfileSectionDraft(text: 'Updated summary'),
    );
    expect(patched, isTrue);
    expect(result.persisted, isTrue);
  });

  test('save personal patches location and headline', () async {
    Map<String, dynamic>? patchBody;
    final client = FakeHttpClient((request) async {
      if (request.url.path == '/api/v1/auth/login') {
        return _loginResponse();
      }
      if (request.method == 'GET' &&
          request.url.path.endsWith('/personal')) {
        return http.Response(
          jsonEncode({
            'data': {
              'name': 'Asha',
              'username': 'asha',
              'preferred_language': 'en',
              'location': 'Old city',
              'professional_headline': 'Old title',
            },
          }),
          200,
        );
      }
      if (request.method == 'PATCH' &&
          request.url.path.endsWith('/personal')) {
        patchBody = jsonDecode(request.body) as Map<String, dynamic>;
        return http.Response(jsonEncode({'data': patchBody}), 200);
      }
      return http.Response(
        jsonEncode({
          'data': {
            'employment_preference': '',
            'skills': [],
            'employment_histories': [],
          },
        }),
        200,
      );
    });

    final api = await _authedApi(client);
    final repository = LiveProfileRepository(api: api);

    final result = await repository.save(
      ProfileSection.personal,
      const ProfileSectionDraft(
        text: 'Dubai',
        secondaryText: 'Site supervisor',
      ),
    );
    expect(result.persisted, isTrue);
    expect(patchBody?['location'], 'Dubai');
    expect(patchBody?['professional_headline'], 'Site supervisor');
  });

  test('save education posts institution and qualification', () async {
    var posted = false;
    final client = FakeHttpClient((request) async {
      if (request.url.path == '/api/v1/auth/login') {
        return _loginResponse();
      }
      if (request.method == 'POST' &&
          request.url.path.endsWith('/educations')) {
        posted = true;
        final body = jsonDecode(request.body) as Map<String, dynamic>;
        expect(body['institution'], 'Trade School');
        expect(body['qualification'], 'Diploma');
        return http.Response(jsonEncode({'data': {}}), 201);
      }
      return http.Response(
        jsonEncode({
          'data': {
            'bio': '',
            'educations': [
              {'institution': 'Trade School', 'qualification': 'Diploma'},
            ],
          },
        }),
        200,
      );
    });

    final api = await _authedApi(client);
    final repository = LiveProfileRepository(api: api);

    final result = await repository.save(
      ProfileSection.education,
      const ProfileSectionDraft(
        education: [
          ProfileEducation(credential: 'Diploma', institution: 'Trade School'),
        ],
      ),
    );
    expect(posted, isTrue);
    expect(result.persisted, isTrue);
    expect(result.profile.education.single.institution, 'Trade School');
  });

  test('uploadProfilePhoto sends multipart patch with personal fields', () async {
    var multipart = false;
    var personalReads = 0;
    final client = FakeHttpClient((request) async {
      if (request.url.path == '/api/v1/auth/login') {
        return _loginResponse();
      }
      if (request.method == 'GET' &&
          request.url.path.endsWith('/personal')) {
        personalReads++;
        final photoUrl = personalReads > 1
            ? 'https://cdn.test/new-photo.jpg'
            : 'https://cdn.test/photo.jpg';
        return http.Response(
          jsonEncode({
            'data': {
              'name': 'Asha',
              'username': 'asha',
              'preferred_language': 'en',
              'photo_url': photoUrl,
            },
          }),
          200,
        );
      }
      if (request.method == 'PATCH' &&
          request.url.path.endsWith('/personal') &&
          request.headers['content-type']?.contains('multipart') == true) {
        multipart = true;
        return http.Response(
          jsonEncode({
            'data': {
              'photo_url': 'https://cdn.test/new-photo.jpg',
            },
          }),
          200,
        );
      }
      return http.Response(
        jsonEncode({
          'data': {
            'employment_preference': '',
            'skills': [],
            'employment_histories': [],
            'educations': [],
            'certifications': [],
          },
        }),
        200,
      );
    });

    final api = await _authedApi(client);
    final repository = LiveProfileRepository(api: api);

    final result = await repository.uploadProfilePhoto(
      bytes: [0xFF, 0xD8, 0xFF],
      filename: 'avatar.jpg',
    );
    expect(multipart, isTrue);
    expect(result.persisted, isTrue);
    expect(result.profile.photoUrl, 'https://cdn.test/new-photo.jpg');
  });
}
