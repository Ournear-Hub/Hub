import 'dart:async';

import 'package:http/http.dart' as http;
import 'package:ournear_mobile/core/api/hire_api_exception.dart';
import 'package:ournear_mobile/core/api/hire_api_service.dart';
import 'package:ournear_mobile/core/api/models/public_job.dart';

/// Distinguishes server rows from deterministic preview rows used by tests.
enum JobsDataSource { liveApi, mockPreview }

class JobsPage {
  const JobsPage({
    required this.jobs,
    required this.currentPage,
    required this.lastPage,
    required this.total,
    required this.source,
  });

  final List<PublicJob> jobs;
  final int currentPage;
  final int lastPage;
  final int total;
  final JobsDataSource source;

  bool get hasMore => currentPage < lastPage;

  factory JobsPage.fromPaginated(
    PaginatedPublicJobs page, {
    required JobsDataSource source,
  }) {
    return JobsPage(
      jobs: page.jobs,
      currentPage: page.currentPage,
      lastPage: page.lastPage,
      total: page.total,
      source: source,
    );
  }
}

/// A catalogue label the repository explicitly allows the UI to send as the
/// existing jobs `keyword` parameter. This is not an industry filter.
class JobCatalogueShortcut {
  const JobCatalogueShortcut({required this.label, required this.keyword});

  final String label;
  final String keyword;
}

enum JobsFailureKind { notConfigured, offline, notFound, unavailable, unknown }

class JobsRepositoryException implements Exception {
  const JobsRepositoryException(this.kind, this.message);

  final JobsFailureKind kind;
  final String message;

  factory JobsRepositoryException.notConfigured() {
    return const JobsRepositoryException(
      JobsFailureKind.notConfigured,
      'OURNEAR_API_BASE is not configured. Set it to your Laravel host before jobs can load.',
    );
  }

  factory JobsRepositoryException.offline([String? message]) {
    return JobsRepositoryException(
      JobsFailureKind.offline,
      message ??
          'Could not reach OurNear. Check your connection and try again.',
    );
  }

  factory JobsRepositoryException.notFound([String? message]) {
    return JobsRepositoryException(
      JobsFailureKind.notFound,
      message ?? 'This job is not publicly available.',
    );
  }

  factory JobsRepositoryException.unavailable(String message) {
    return JobsRepositoryException(JobsFailureKind.unavailable, message);
  }

  @override
  String toString() => message;
}

/// UI-facing jobs access. Widgets use this instead of calling HTTP themselves.
abstract class JobsRepository {
  bool get isConfigured;

  /// Query keys the list endpoint is known to accept, besides `page` and
  /// `per_page`. Live jobs currently accept only `keyword`.
  List<String> get supportedListParameters;

  Future<JobsPage> fetchJobs({
    int page = 1,
    int perPage = 15,
    String? keyword,
    String? industry,
    String? location,
    String? category,
    String? employmentType,
    String? experience,
    String? sort,
  });

  Future<PublicJobDetail> fetchJobDetail(String slug);

  /// Platform industry/category shortcuts. Throws [JobsFailureKind.unavailable]
  /// when no master-data contract exists.
  Future<List<JobCatalogueShortcut>> fetchIndustryShortcuts();

  /// Saved jobs. Throws [JobsFailureKind.unavailable] when no saved-jobs
  /// contract exists. Must not invent a URL.
  Future<JobsPage> fetchSavedJobs();

  /// Public filter values from the jobs catalogue. Empty when unsupported.
  Future<Map<String, List<String>>> fetchPublicFilters() async {
    return const {};
  }
}

class LiveJobsRepository implements JobsRepository {
  LiveJobsRepository({HireApiService? service, HireApiService? api})
    : _service = service ?? api ?? HireApiService();

  final HireApiService _service;

  Future<bool> setJobSaved(String slug, {required bool save}) async {
    return _guard(() => _service.setJobSaved(slug, save: save));
  }

  static const industriesUnavailable =
      'Industry and category shortcuts are not available. No master-data catalogue is loaded, and none is hard-coded.';
  static const savedJobsUnavailable =
      'Saved jobs are not stored on this device and are not synced with OurNear. No saved-jobs list was loaded.';

  @override
  bool get isConfigured => _service.isConfigured;

  @override
  List<String> get supportedListParameters => const [
    'keyword',
    'industry',
    'location',
    'category',
    'employment_type',
    'experience',
    'sort',
  ];

  @override
  Future<JobsPage> fetchJobs({
    int page = 1,
    int perPage = 15,
    String? keyword,
    String? industry,
    String? location,
    String? category,
    String? employmentType,
    String? experience,
    String? sort,
  }) {
    return _guard(() async {
      final result = await _service.fetchJobs(
        page: page,
        perPage: perPage,
        keyword: keyword,
        industry: industry,
        location: location,
        category: category,
        employmentType: employmentType,
        experience: experience,
        sort: sort,
      );
      return JobsPage.fromPaginated(result, source: JobsDataSource.liveApi);
    });
  }

  @override
  Future<PublicJobDetail> fetchJobDetail(String slug) {
    return _guard(() => _service.fetchJobDetail(slug));
  }

  @override
  Future<List<JobCatalogueShortcut>> fetchIndustryShortcuts() async {
    return _guard(() async {
      final options = await fetchPublicFilters();
      final industries = options['industries'] ?? const <String>[];
      return industries
          .map(
            (label) => JobCatalogueShortcut(label: label, keyword: label),
          )
          .toList();
    });
  }

  @override
  Future<Map<String, List<String>>> fetchPublicFilters() {
    return _guard(() => _service.fetchJobFilterOptions());
  }

  @override
  Future<JobsPage> fetchSavedJobs() async {
    return _guard(() async {
      final merged = <PublicJob>[];
      var page = 1;
      var lastPage = 1;
      do {
        final result = await _service.fetchSavedJobs(page: page);
        merged.addAll(result.jobs);
        lastPage = result.lastPage;
        page += 1;
      } while (page <= lastPage && page <= 50);

      return JobsPage(
        jobs: merged,
        currentPage: 1,
        lastPage: 1,
        total: merged.length,
        source: JobsDataSource.liveApi,
      );
    });
  }

  Future<T> _guard<T>(Future<T> Function() action) async {
    if (!_service.isConfigured) {
      throw JobsRepositoryException.notConfigured();
    }
    try {
      return await action();
    } on JobsRepositoryException {
      rethrow;
    } on HireApiException catch (error) {
      if (error.statusCode == 404) {
        throw JobsRepositoryException.notFound(error.message);
      }
      if (error.message.contains('not configured')) {
        throw JobsRepositoryException.notConfigured();
      }
      throw JobsRepositoryException.unavailable(error.message);
    } on TimeoutException {
      throw JobsRepositoryException.offline();
    } on http.ClientException {
      throw JobsRepositoryException.offline();
    } catch (error) {
      throw JobsRepositoryException(
        JobsFailureKind.unknown,
        'Could not load jobs. $error',
      );
    }
  }
}
