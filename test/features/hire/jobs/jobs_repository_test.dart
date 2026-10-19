import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:ournear_mobile/core/api/hire_api_service.dart';
import 'package:ournear_mobile/core/api/models/public_job.dart';
import 'package:ournear_mobile/features/hire/jobs/data/jobs_repository.dart';
import 'package:ournear_mobile/features/hire/jobs/data/mock_jobs_repository.dart';

import '../../../helpers/fake_http_client.dart';

void main() {
  test(
    'live repository fetches a keyword page and reports the total',
    () async {
      final repository = LiveJobsRepository(
        service: HireApiService(
          apiBaseOverride: 'http://localhost',
          client: FakeHttpClient((request) async {
            expect(request.url.path, '/api/v1/jobs');
            expect(request.url.queryParameters['keyword'], 'nurse');
            expect(request.url.queryParameters['page'], '1');
            expect(
              request.url.queryParameters.containsKey('industry'),
              isFalse,
            );
            return http.Response(
              jsonEncode({
                'data': [
                  {'title': 'Nurse', 'slug': 'nurse'},
                ],
                'meta': {'current_page': 1, 'last_page': 3, 'total': 21},
                'links': {'next': 'http://localhost/api/v1/jobs?page=2'},
              }),
              200,
            );
          }),
        ),
      );

      final page = await repository.fetchJobs(keyword: 'nurse');
      expect(page.jobs.single.title, 'Nurse');
      expect(page.total, 21);
      expect(page.hasMore, isTrue);
      expect(page.source, JobsDataSource.liveApi);
      expect(repository.supportedListParameters, contains('keyword'));
    },
  );

  test('live repository forwards catalogue filters and sort', () async {
    final repository = LiveJobsRepository(
      service: HireApiService(
        apiBaseOverride: 'http://localhost',
        client: FakeHttpClient((request) async {
          expect(request.url.path, '/api/v1/jobs');
          expect(request.url.queryParameters['location'], 'Chennai');
          expect(request.url.queryParameters['category'], 'Nursing');
          expect(request.url.queryParameters['employment_type'], 'Full-time');
          expect(request.url.queryParameters['experience'], '1-3 years');
          expect(request.url.queryParameters['sort'], 'oldest');
          return http.Response(
            jsonEncode({
              'data': [],
              'meta': {'current_page': 1, 'last_page': 1, 'total': 0},
              'links': {'next': null},
            }),
            200,
          );
        }),
      ),
    );

    await repository.fetchJobs(
      location: 'Chennai',
      category: 'Nursing',
      employmentType: 'Full-time',
      experience: '1-3 years',
      sort: 'oldest',
    );
  });

  test(
    'live repository returns an empty page without inventing jobs',
    () async {
      final repository = LiveJobsRepository(
        service: HireApiService(
          apiBaseOverride: 'http://localhost',
          client: FakeHttpClient(
            (_) async => http.Response(
              jsonEncode({
                'data': [],
                'meta': {'current_page': 1, 'last_page': 1, 'total': 0},
                'links': {'next': null},
              }),
              200,
            ),
          ),
        ),
      );

      final page = await repository.fetchJobs();
      expect(page.jobs, isEmpty);
      expect(page.total, 0);
    },
  );

  test(
    'live repository maps HTTP failure on jobs, saved jobs, and filter catalogue',
    () async {
      var calls = 0;
      final repository = LiveJobsRepository(
        service: HireApiService(
          apiBaseOverride: 'http://localhost',
          client: FakeHttpClient((request) async {
            calls += 1;
            return http.Response('no', 500);
          }),
        ),
      );

      await expectLater(
        repository.fetchJobs(),
        throwsA(
          isA<JobsRepositoryException>().having(
            (error) => error.kind,
            'kind',
            JobsFailureKind.unavailable,
          ),
        ),
      );
      await expectLater(
        repository.fetchSavedJobs(),
        throwsA(
          isA<JobsRepositoryException>().having(
            (error) => error.kind,
            'kind',
            JobsFailureKind.unavailable,
          ),
        ),
      );
      await expectLater(
        repository.fetchPublicFilters(),
        throwsA(
          isA<JobsRepositoryException>().having(
            (error) => error.kind,
            'kind',
            JobsFailureKind.unavailable,
          ),
        ),
      );
      expect(calls, 2);
    },
  );

  test('unconfigured repository does not call HTTP', () async {
    final repository = LiveJobsRepository(
      service: HireApiService(
        client: FakeHttpClient((_) async => throw StateError('network')),
      ),
    );

    expect(repository.isConfigured, isFalse);
    await expectLater(
      repository.fetchJobs(),
      throwsA(
        isA<JobsRepositoryException>().having(
          (error) => error.kind,
          'kind',
          JobsFailureKind.notConfigured,
        ),
      ),
    );
    await expectLater(
      repository.fetchJobDetail('nurse'),
      throwsA(
        isA<JobsRepositoryException>().having(
          (error) => error.kind,
          'kind',
          JobsFailureKind.notConfigured,
        ),
      ),
    );
  });

  test(
    'mock repository paginates and filters the supplied rows only',
    () async {
      final repository = MockJobsRepository(
        jobs: const [
          PublicJob(title: 'Nurse', slug: 'nurse', companyName: 'North Clinic'),
          PublicJob(title: 'Analyst', slug: 'analyst', location: 'Pune'),
          PublicJob(title: 'Nurse lead', slug: 'nurse-lead'),
        ],
      );

      final first = await repository.fetchJobs(
        page: 1,
        perPage: 1,
        keyword: 'nurse',
      );
      expect(first.jobs.single.slug, 'nurse');
      expect(first.total, 2);
      expect(first.hasMore, isTrue);
      expect(first.source, JobsDataSource.mockPreview);

      final second = await repository.fetchJobs(
        page: 2,
        perPage: 1,
        keyword: 'nurse',
      );
      expect(second.jobs.single.slug, 'nurse-lead');
      expect(second.total, 2);

      final detail = await repository.fetchJobDetail('analyst');
      expect(detail.title, 'Analyst');
      expect(detail.description, 'Description for Analyst');
    },
  );
}
