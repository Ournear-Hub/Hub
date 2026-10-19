import 'dart:async';

import 'package:http/http.dart' as http;
import 'package:ournear_mobile/core/api/hire_api_exception.dart';
import 'package:ournear_mobile/core/api/hire_api_service.dart';
import 'package:ournear_mobile/features/hire/applications/models/candidate_application.dart';

/// Where application rows originated. Distinguishes demo samples from future live API data.
enum ApplicationsDataSource {
  /// Deterministic local demo rows — not persisted on Laravel.
  mockDemo,

  /// Authorized Laravel API (`GET /api/v1/account/applications`, apply POST).
  liveApi,
}

class ApplicationsQueryResult {
  const ApplicationsQueryResult({
    required this.applications,
    required this.source,
  });

  final List<CandidateApplication> applications;
  final ApplicationsDataSource source;
}

enum ApplicationsFailureKind {
  offline,
  unavailable,
  notFound,
  sessionExpired,
  notAuthenticated,
  verificationRequired,
  unknown,
}

class ApplicationsRepositoryException implements Exception {
  const ApplicationsRepositoryException(this.kind, this.message);

  final ApplicationsFailureKind kind;
  final String message;

  factory ApplicationsRepositoryException.offline([String? message]) {
    return ApplicationsRepositoryException(
      ApplicationsFailureKind.offline,
      message ??
          'Could not reach OurNear. Check your connection and try again.',
    );
  }

  factory ApplicationsRepositoryException.unavailable([String? message]) {
    return ApplicationsRepositoryException(
      ApplicationsFailureKind.unavailable,
      message ?? 'Applications are temporarily unavailable.',
    );
  }

  factory ApplicationsRepositoryException.notFound([String? message]) {
    return ApplicationsRepositoryException(
      ApplicationsFailureKind.notFound,
      message ?? 'This application was not found.',
    );
  }

  factory ApplicationsRepositoryException.sessionExpired([String? message]) {
    return ApplicationsRepositoryException(
      ApplicationsFailureKind.sessionExpired,
      message ??
          'Your session has expired. Sign in again. Nothing was submitted.',
    );
  }

  factory ApplicationsRepositoryException.notAuthenticated([String? message]) {
    return ApplicationsRepositoryException(
      ApplicationsFailureKind.notAuthenticated,
      message ?? 'Sign in to view your applications.',
    );
  }

  factory ApplicationsRepositoryException.verificationRequired([String? message]) {
    return ApplicationsRepositoryException(
      ApplicationsFailureKind.verificationRequired,
      message ??
          'Verify your email to apply for jobs. You can still browse jobs and update your profile.',
    );
  }

  @override
  String toString() => message;
}

/// Fields the wizard can send. Only values the UI actually has.
class ApplicationDraft {
  const ApplicationDraft({
    required this.jobSlug,
    required this.jobTitle,
    required this.companyName,
    this.location,
  });

  final String jobSlug;
  final String jobTitle;
  final String companyName;
  final String? location;
}

/// Result of [ApplicationsRepository.submitApplication].
class ApplicationSubmission {
  const ApplicationSubmission({
    required this.application,
    required this.source,
    required this.confirmedByServer,
  });

  final CandidateApplication application;
  final ApplicationsDataSource source;

  /// True only when a verified applications endpoint accepted the submit.
  final bool confirmedByServer;
}

/// Integration seam for Laravel-backed applications once the contract is authorized.
abstract class ApplicationsRepository {
  Future<ApplicationsQueryResult> fetchApplications();

  Future<CandidateApplication> fetchApplication(String id);

  Future<ApplicationSubmission> submitApplication(ApplicationDraft draft);
}

/// Laravel `/api/v1/account/applications` and job apply endpoints.
class LiveApplicationsRepository implements ApplicationsRepository {
  LiveApplicationsRepository({HireApiService? api}) : _api = api ?? HireApiService();

  final HireApiService _api;

  static const historyUnavailable =
      'Application history is not connected to OurNear. No list was loaded.';
  static const detailUnavailable =
      'Application details are not connected to OurNear. Nothing was loaded.';
  static const submitUnavailable =
      'Application submission is not connected to OurNear. Nothing was sent.';

  @override
  Future<ApplicationsQueryResult> fetchApplications() async {
    if (!_api.isConfigured) {
      throw ApplicationsRepositoryException.unavailable(historyUnavailable);
    }
    return _guard(() async {
      var pageIndex = 1;
      final collected = <CandidateApplication>[];
      const maxPages = 50;
      while (pageIndex <= maxPages) {
        final page = await _api.fetchApplications(page: pageIndex);
        collected.addAll(page.applications);
        if (pageIndex >= page.lastPage) {
          break;
        }
        pageIndex += 1;
      }
      return ApplicationsQueryResult(
        applications: collected,
        source: ApplicationsDataSource.liveApi,
      );
    });
  }

  @override
  Future<CandidateApplication> fetchApplication(String id) async {
    if (!_api.isConfigured) {
      throw ApplicationsRepositoryException.unavailable(detailUnavailable);
    }
    return _guard(() => _api.fetchApplicationDetail(id));
  }

  Future<http.Response> downloadResume(String applicationId) async {
    return _guard(() => _api.downloadApplicationResume(applicationId));
  }

  @override
  Future<ApplicationSubmission> submitApplication(ApplicationDraft draft) async {
    if (!_api.isConfigured) {
      throw ApplicationsRepositoryException.unavailable(submitUnavailable);
    }
    return _guard(() async {
      final result = await _api.submitJobApplication(draft.jobSlug);
      final application = CandidateApplication(
        id: '${result.applicationId}',
        businessId: result.businessId,
        roleTitle: draft.jobTitle,
        companyName: draft.companyName,
        location: draft.location,
        statusLabel: result.statusLabel,
        submittedAt: DateTime.now().toUtc(),
        jobSlug: draft.jobSlug,
        timeline: CandidateApplication.timelineFromTimestamps(
          submittedAt: DateTime.now().toUtc(),
        ),
      );
      return ApplicationSubmission(
        application: application,
        source: ApplicationsDataSource.liveApi,
        confirmedByServer: result.applicationId > 0,
      );
    });
  }

  Future<T> _guard<T>(Future<T> Function() action) async {
    if (!_api.isConfigured) {
      throw ApplicationsRepositoryException.unavailable(
        'OURNEAR_API_BASE is not configured.',
      );
    }
    try {
      return await action();
    } on ApplicationsRepositoryException {
      rethrow;
    } on HireApiException catch (error) {
      if (error.statusCode == 401) {
        throw ApplicationsRepositoryException.sessionExpired(error.message);
      }
      if (error.statusCode == 403 &&
          error.errorCode == 'email_verification_required') {
        throw ApplicationsRepositoryException.verificationRequired(
          error.message,
        );
      }
      if (error.statusCode == 404) {
        throw ApplicationsRepositoryException.notFound(error.message);
      }
      if (error.statusCode == 429) {
        throw ApplicationsRepositoryException.unavailable(error.message);
      }
      throw ApplicationsRepositoryException.unavailable(error.message);
    } on TimeoutException {
      throw ApplicationsRepositoryException.offline();
    } on http.ClientException {
      throw ApplicationsRepositoryException.offline();
    } catch (error) {
      throw ApplicationsRepositoryException(
        ApplicationsFailureKind.unknown,
        'Could not complete the request. $error',
      );
    }
  }
}
