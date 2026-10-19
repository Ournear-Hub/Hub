import 'package:ournear_mobile/core/api/models/public_job.dart';
import 'package:ournear_mobile/features/hire/jobs/data/jobs_repository.dart';

/// Deterministic jobs data for tests and an explicit preview injection.
///
/// The candidate shell does not construct this when the API is unset.
class MockJobsRepository implements JobsRepository {
  MockJobsRepository({
    this.jobs = const [],
    this.details = const {},
    this.industries = const [],
    this.configured = true,
    this.failure,
    this.industryFailure,
    this.savedFailure,
  });

  final List<PublicJob> jobs;
  final Map<String, PublicJobDetail> details;
  final List<JobCatalogueShortcut> industries;
  final bool configured;
  final JobsRepositoryException? failure;
  final JobsRepositoryException? industryFailure;
  final JobsRepositoryException? savedFailure;

  @override
  bool get isConfigured => configured;

  @override
  List<String> get supportedListParameters => const ['keyword'];

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
  }) async {
    if (!configured) {
      throw JobsRepositoryException.notConfigured();
    }
    if (failure != null) {
      throw failure!;
    }

    final query = keyword?.trim().toLowerCase() ?? '';
    final matched = jobs.where((job) {
      if (query.isEmpty) {
        return true;
      }
      final haystack = [
        job.title,
        job.companyName,
        job.location,
        job.experience,
        job.employmentType,
        job.industry,
        job.category,
      ].whereType<String>().join(' ').toLowerCase();
      return haystack.contains(query);
    }).toList();

    final lastPage = matched.isEmpty
        ? 1
        : ((matched.length + perPage - 1) ~/ perPage);
    final start = (page - 1) * perPage;
    final slice = start >= matched.length
        ? <PublicJob>[]
        : matched.skip(start).take(perPage).toList();

    return JobsPage(
      jobs: slice,
      currentPage: page,
      lastPage: lastPage,
      total: matched.length,
      source: JobsDataSource.mockPreview,
    );
  }

  @override
  Future<PublicJobDetail> fetchJobDetail(String slug) async {
    if (!configured) {
      throw JobsRepositoryException.notConfigured();
    }
    if (failure != null) {
      throw failure!;
    }
    final supplied = details[slug];
    if (supplied != null) {
      return supplied;
    }
    for (final job in jobs) {
      if (job.slug == slug) {
        return PublicJobDetail(
          title: job.title,
          slug: job.slug,
          project: job.project,
          companyName: job.companyName,
          companyLogoUrl: job.companyLogoUrl,
          industry: job.industry,
          category: job.category,
          location: job.location,
          employmentType: job.employmentType,
          experience: job.experience,
          salary: job.salary,
          postedLabel: job.postedLabel,
          closingLabel: job.closingLabel,
          featured: job.featured,
          urgent: job.urgent,
          isClosed: job.isClosed,
          description: 'Description for ${job.title}',
        );
      }
    }
    throw JobsRepositoryException.notFound();
  }

  @override
  Future<List<JobCatalogueShortcut>> fetchIndustryShortcuts() async {
    if (industryFailure != null) {
      throw industryFailure!;
    }
    return industries;
  }

  @override
  Future<Map<String, List<String>>> fetchPublicFilters() async {
    if (industryFailure != null) {
      throw industryFailure!;
    }
    return {
      'industries': [for (final row in industries) row.label],
    };
  }

  @override
  Future<JobsPage> fetchSavedJobs() async {
    throw savedFailure ??
        JobsRepositoryException.unavailable(
          LiveJobsRepository.savedJobsUnavailable,
        );
  }
}
