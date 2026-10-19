import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:ournear_mobile/core/api/hire_api_exception.dart';
import 'package:ournear_mobile/core/api/hire_api_service.dart';
import 'package:ournear_mobile/core/auth/auth_service.dart';
import 'package:ournear_mobile/features/hire/applications/data/applications_repository.dart';
import 'package:ournear_mobile/features/hire/communication/data/messages_repository.dart';
import 'package:ournear_mobile/features/hire/communication/data/notifications_repository.dart';
import 'package:ournear_mobile/features/hire/jobs/data/jobs_repository.dart';
import 'package:ournear_mobile/features/hire/profile/data/documents_repository.dart';
import 'package:ournear_mobile/features/hire/profile/data/profile_repository.dart';
import 'package:ournear_mobile/features/hire/profile/models/candidate_profile.dart';

import '../../helpers/fake_http_client.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  setUp(() {
    FlutterSecureStorage.setMockInitialValues({});
  });

  test('job detail uses the verified slug path and mapped fields', () async {
    final repository = LiveJobsRepository(
      service: HireApiService(
        apiBaseOverride: 'http://localhost',
        client: FakeHttpClient((request) async {
          expect(request.method, 'GET');
          expect(request.url.path, '/api/v1/jobs/ward-nurse');
          expect(headerValue(request, 'authorization'), isNull);
          return http.Response(
            jsonEncode({
              'data': {
                'title': 'Ward Nurse',
                'slug': 'ward-nurse',
                'company_name': 'City Clinic',
                'description': 'Ward cover',
                'responsibilities': 'Medicines',
                'requirements': 'Registration',
                'what_we_offer': 'Shift rota',
                'accepts_applications': true,
              },
            }),
            200,
          );
        }),
      ),
    );

    final detail = await repository.fetchJobDetail('ward-nurse');
    expect(detail.title, 'Ward Nurse');
    expect(detail.companyName, 'City Clinic');
    expect(detail.description, 'Ward cover');
    expect(detail.responsibilities, 'Medicines');
    expect(detail.requirements, 'Registration');
    expect(detail.whatWeOffer, 'Shift rota');
    expect(detail.acceptsApplications, isTrue);
  });

  test('job detail maps 404 and 500 without inventing a job', () async {
    Future<LiveJobsRepository> repository(int status) {
      return Future.value(
        LiveJobsRepository(
          service: HireApiService(
            apiBaseOverride: 'http://localhost',
            client: FakeHttpClient((_) async => http.Response('no', status)),
          ),
        ),
      );
    }

    final missing = await repository(404);
    await expectLater(
      missing.fetchJobDetail('missing'),
      throwsA(
        isA<JobsRepositoryException>().having(
          (error) => error.kind,
          'kind',
          JobsFailureKind.notFound,
        ),
      ),
    );

    final failed = await repository(500);
    await expectLater(
      failed.fetchJobDetail('missing'),
      throwsA(
        isA<JobsRepositoryException>().having(
          (error) => error.kind,
          'kind',
          JobsFailureKind.unavailable,
        ),
      ),
    );
  });

  test('job list HTTP 422 stays a client error', () async {
    final service = HireApiService(
      apiBaseOverride: 'http://localhost',
      client: FakeHttpClient((request) async {
        expect(request.url.path, '/api/v1/jobs');
        return http.Response('no', 422);
      }),
    );

    await expectLater(
      service.fetchJobs(),
      throwsA(
        isA<HireApiException>().having(
          (error) => error.statusCode,
          'statusCode',
          422,
        ),
      ),
    );
  });

  test(
    'logout posts the verified path and still clears a failed request',
    () async {
      var logoutCalls = 0;
      final service = AuthService(
        apiBaseOverride: 'http://localhost',
        client: FakeHttpClient((request) async {
          if (request.url.path == '/api/v1/auth/login') {
            return http.Response(
              jsonEncode({
                'token': 'session-token',
                'user': {'name': 'Asha', 'username': 'asha'},
              }),
              200,
            );
          }
          logoutCalls += 1;
          expect(request.method, 'POST');
          expect(request.url.path, '/api/v1/auth/logout');
          expect(request.body, isEmpty);
          expect(headerValue(request, 'authorization'), 'Bearer session-token');
          return http.Response('no', 500);
        }),
      );

      await service.login(login: 'asha', password: 'secret');
      await service.logout();
      expect(logoutCalls, 1);
      expect(service.bearerToken, isNull);
    },
  );

  test('logout clears the session when the request throws', () async {
    final service = AuthService(
      apiBaseOverride: 'http://localhost',
      client: FakeHttpClient((request) async {
        if (request.url.path == '/api/v1/auth/login') {
          return http.Response(
            jsonEncode({'token': 'session-token', 'user': {}}),
            200,
          );
        }
        throw http.ClientException('offline');
      }),
    );

    await service.login(login: 'asha', password: 'secret');
    await service.logout();
    expect(service.bearerToken, isNull);
  });

  test('blocked live repositories do not report success', () async {
    final applications = LiveApplicationsRepository();
    final profiles = LiveProfileRepository();
    final documents = LiveDocumentsRepository();
    final messages = LiveMessagesRepository();
    final notifications = LiveNotificationsRepository();

    await expectLater(
      applications.fetchApplications(),
      throwsA(
        isA<ApplicationsRepositoryException>().having(
          (error) => error.message,
          'message',
          contains('not connected'),
        ),
      ),
    );
    await expectLater(
      applications.submitApplication(
        const ApplicationDraft(
          jobSlug: 'ward-nurse',
          jobTitle: 'Ward Nurse',
          companyName: 'City Clinic',
        ),
      ),
      throwsA(isA<ApplicationsRepositoryException>()),
    );
    await expectLater(
      profiles.fetchProfile(),
      throwsA(isA<ProfileRepositoryException>()),
    );
    await expectLater(
      profiles.save(
        ProfileSection.summary,
        const ProfileSectionDraft(text: 'Hello'),
      ),
      throwsA(
        isA<ProfileRepositoryException>().having(
          (error) => error.message,
          'message',
          contains('not connected'),
        ),
      ),
    );
    await expectLater(
      documents.replaceUploadedResume('cv.pdf'),
      throwsA(
        isA<DocumentRepositoryException>().having(
          (error) => error.message,
          'message',
          anyOf(
            contains('Choose a PDF'),
            contains('not connected'),
          ),
        ),
      ),
    );
    await expectLater(
      messages.fetchInbox(),
      throwsA(isA<MessageRepositoryException>()),
    );
    await expectLater(
      notifications.markRead('demo-notif-001'),
      throwsA(
        isA<NotificationRepositoryException>().having(
          (error) => error.message,
          'message',
          contains('Nothing was changed'),
        ),
      ),
    );
  });
}
