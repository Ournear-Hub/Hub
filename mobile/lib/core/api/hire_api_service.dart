import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:ournear_mobile/core/api/api_config.dart';
import 'package:ournear_mobile/core/api/hire_api_exception.dart';
import 'package:ournear_mobile/core/api/models/public_job.dart';
import 'package:ournear_mobile/core/auth/auth_service.dart';
import 'package:ournear_mobile/features/hire/applications/models/candidate_application.dart';

enum HireIntegrationState { apiNotConfigured, apiUnreachable, jobsApiReady }

class HireIntegrationStatus {
  const HireIntegrationStatus({
    required this.state,
    required this.message,
    this.healthCheckOk = false,
  });

  final HireIntegrationState state;
  final String message;
  final bool healthCheckOk;
}

class PaginatedApplications {
  const PaginatedApplications({
    required this.applications,
    required this.currentPage,
    required this.lastPage,
    required this.total,
  });

  final List<CandidateApplication> applications;
  final int currentPage;
  final int lastPage;
  final int total;
}

class JobApplicationResult {
  const JobApplicationResult({
    required this.applicationId,
    required this.statusLabel,
    this.businessId,
  });

  final int applicationId;
  final String statusLabel;
  final String? businessId;
}

class HireApiService {
  HireApiService({
    http.Client? client,
    String? apiBaseOverride,
    AuthService? authService,
  }) : _client = client ?? http.Client(),
       _apiBaseOverride = apiBaseOverride,
       _authService = authService;

  final http.Client _client;
  final String? _apiBaseOverride;
  final AuthService? _authService;

  bool get isConfigured {
    final base = _resolvedApiBase;
    return base != null && base.isNotEmpty;
  }

  String? get _resolvedApiBase {
    final override = _apiBaseOverride?.trim();
    if (override != null && override.isNotEmpty) {
      return override;
    }
    if (ApiConfig.hasApiBase) {
      return ApiConfig.apiBase.trim();
    }
    return null;
  }

  Uri? _apiUri(String path, [Map<String, String>? query]) {
    final base = _resolvedApiBase;
    if (base == null || base.isEmpty) {
      return null;
    }
    final normalized = base.replaceAll(RegExp(r'/+$'), '');
    return Uri.parse('$normalized$path').replace(queryParameters: query);
  }

  Future<HireIntegrationStatus> checkIntegration() async {
    if (!ApiConfig.hasApiBase) {
      return const HireIntegrationStatus(
        state: HireIntegrationState.apiNotConfigured,
        message:
            'Set OURNEAR_API_BASE to your Laravel host (e.g. http://10.0.2.2:8000 for Android emulator).',
      );
    }

    try {
      final jobsProbe = await fetchJobs(page: 1, perPage: 1);
      return HireIntegrationStatus(
        state: HireIntegrationState.jobsApiReady,
        message:
            'Connected to OurNear Hire JSON API (${jobsProbe.total} public job${jobsProbe.total == 1 ? '' : 's'}).',
        healthCheckOk: true,
      );
    } on HireApiException catch (error) {
      return HireIntegrationStatus(
        state: HireIntegrationState.apiUnreachable,
        message: error.message,
      );
    } catch (error) {
      return HireIntegrationStatus(
        state: HireIntegrationState.apiUnreachable,
        message: 'Could not reach OurNear API: $error',
      );
    }
  }

  Future<PaginatedPublicJobs> fetchJobs({
    int page = 1,
    int perPage = 15,
    String? keyword,
    String? industry,
    String? location,
    String? category,
    String? employmentType,
    String? experience,
    String? sort,
    bool includeAuthWhenAvailable = true,
  }) async {
    final uri = _apiUri('/api/v1/jobs', {
      'page': '$page',
      'per_page': '$perPage',
      if (keyword != null && keyword.trim().isNotEmpty)
        'keyword': keyword.trim(),
      if (industry != null && industry.trim().isNotEmpty)
        'industry': industry.trim(),
      if (location != null && location.trim().isNotEmpty)
        'location': location.trim(),
      if (category != null && category.trim().isNotEmpty)
        'category': category.trim(),
      if (employmentType != null && employmentType.trim().isNotEmpty)
        'employment_type': employmentType.trim(),
      if (experience != null && experience.trim().isNotEmpty)
        'experience': experience.trim(),
      if (sort != null && sort.trim().isNotEmpty) 'sort': sort.trim(),
    });

    if (uri == null) {
      throw HireApiException('OURNEAR_API_BASE is not configured.');
    }

    final response = await _client
        .get(
          uri,
          headers: _headers(includeAuth: includeAuthWhenAvailable),
        )
        .timeout(const Duration(seconds: 12));

    return _decodeJobsList(response);
  }

  Future<PublicJobDetail> fetchJobDetail(
    String slug, {
    bool includeAuthWhenAvailable = true,
  }) async {
    final uri = _apiUri('/api/v1/jobs/$slug');
    if (uri == null) {
      throw HireApiException('OURNEAR_API_BASE is not configured.');
    }

    final response = await _client
        .get(
          uri,
          headers: _headers(includeAuth: includeAuthWhenAvailable),
        )
        .timeout(const Duration(seconds: 12));

    if (response.statusCode == 404) {
      throw HireApiException(
        'This job is not publicly available.',
        statusCode: 404,
      );
    }

    if (response.statusCode >= 200 && response.statusCode < 300) {
      final decoded = jsonDecode(response.body) as Map<String, dynamic>;
      final data = decoded['data'] as Map<String, dynamic>? ?? {};
      return PublicJobDetail.fromJson(data);
    }

    throw _failure('Job detail request failed', response);
  }

  Future<PaginatedApplications> fetchApplications({int page = 1}) async {
    final uri = _apiUri('/api/v1/account/applications', {'page': '$page'});
    if (uri == null) {
      throw HireApiException('OURNEAR_API_BASE is not configured.');
    }

    final response = await _client
        .get(uri, headers: _headers(requireAuth: true))
        .timeout(const Duration(seconds: 12));

    if (response.statusCode == 401) {
      await _authService?.clearSession();
      throw HireApiException('Session expired. Sign in again.', statusCode: 401);
    }

    if (response.statusCode >= 200 && response.statusCode < 300) {
      final decoded = jsonDecode(response.body) as Map<String, dynamic>;
      final data = decoded['data'] as List<dynamic>? ?? [];
      final meta = decoded['meta'] as Map<String, dynamic>? ?? {};
      return PaginatedApplications(
        applications: data
            .map(
              (item) => CandidateApplication.fromApiJson(
                item as Map<String, dynamic>,
              ),
            )
            .toList(),
        currentPage: meta['current_page'] as int? ?? page,
        lastPage: meta['last_page'] as int? ?? 1,
        total: meta['total'] as int? ?? data.length,
      );
    }

    throw _failure('Applications request failed', response);
  }

  Future<CandidateApplication> fetchApplicationDetail(String id) async {
    final uri = _apiUri('/api/v1/account/applications/$id');
    if (uri == null) {
      throw HireApiException('OURNEAR_API_BASE is not configured.');
    }

    final response = await _client
        .get(uri, headers: _headers(requireAuth: true))
        .timeout(const Duration(seconds: 12));

    if (response.statusCode == 401) {
      await _authService?.clearSession();
      throw HireApiException('Session expired. Sign in again.', statusCode: 401);
    }
    if (response.statusCode == 404) {
      throw HireApiException(
        'This application was not found.',
        statusCode: 404,
      );
    }

    if (response.statusCode >= 200 && response.statusCode < 300) {
      final decoded = jsonDecode(response.body) as Map<String, dynamic>;
      final data = decoded['data'] as Map<String, dynamic>? ?? {};
      return CandidateApplication.fromApiJson(data);
    }

    throw _failure('Application detail request failed', response);
  }

  Future<http.Response> downloadApplicationResume(String applicationId) async {
    final uri = _apiUri('/api/v1/account/applications/$applicationId/resume');
    if (uri == null) {
      throw HireApiException('OURNEAR_API_BASE is not configured.');
    }

    final response = await _client
        .get(uri, headers: _headers(requireAuth: true))
        .timeout(const Duration(seconds: 30));

    if (response.statusCode == 401) {
      await _authService?.clearSession();
      throw HireApiException('Session expired. Sign in again.', statusCode: 401);
    }
    if (response.statusCode == 403 || response.statusCode == 404) {
      throw HireApiException(
        'Resume is not available for this application.',
        statusCode: response.statusCode,
      );
    }
    if (response.statusCode >= 200 && response.statusCode < 300) {
      return response;
    }
    throw _failure('Resume download failed', response);
  }

  Future<JobApplicationResult> submitJobApplication(String slug) async {
    final uri = _apiUri('/api/v1/jobs/$slug/apply');
    if (uri == null) {
      throw HireApiException('OURNEAR_API_BASE is not configured.');
    }

    final response = await _client
        .post(
          uri,
          headers: {
            ..._headers(requireAuth: true),
            'Content-Type': 'application/json',
          },
          body: jsonEncode(<String, dynamic>{}),
        )
        .timeout(const Duration(seconds: 20));

    if (response.statusCode == 401) {
      await _authService?.clearSession();
      throw HireApiException('Session expired. Sign in again.', statusCode: 401);
    }

    if (response.statusCode == 403) {
      final decoded = _tryDecodeMap(response.body);
      final code = decoded?['error_code'] as String?;
      if (code == 'email_verification_required') {
        throw HireApiException(
          decoded?['message'] as String? ??
              'Verify your email to apply for jobs.',
          statusCode: 403,
          errorCode: code,
        );
      }
      throw HireApiException(
        decoded?['message'] as String? ?? 'You cannot apply to this job.',
        statusCode: 403,
      );
    }

    if (response.statusCode == 422) {
      final decoded = _tryDecodeMap(response.body);
      throw HireApiException(
        _firstErrorMessage(decoded) ?? 'Unable to submit application.',
        statusCode: 422,
      );
    }

    if (response.statusCode >= 200 && response.statusCode < 300) {
      final decoded = jsonDecode(response.body) as Map<String, dynamic>;
      final data = decoded['data'] as Map<String, dynamic>? ?? {};
      return JobApplicationResult(
        applicationId: data['application_id'] as int? ?? 0,
        statusLabel: data['status'] as String? ?? 'Submitted',
        businessId: data['business_id'] as String?,
      );
    }

    throw _failure('Application submission failed', response);
  }

  Future<PaginatedPublicJobs> fetchSavedJobs({int page = 1}) async {
    final uri = _apiUri('/api/v1/account/saved-jobs', {'page': '$page'});
    if (uri == null) {
      throw HireApiException('OURNEAR_API_BASE is not configured.');
    }

    final response = await _client
        .get(uri, headers: _headers(requireAuth: true))
        .timeout(const Duration(seconds: 12));

    if (response.statusCode == 401) {
      await _authService?.clearSession();
      throw HireApiException('Session expired. Sign in again.', statusCode: 401);
    }

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return _decodeJobsList(response);
    }

    throw _failure('Saved jobs request failed', response);
  }

  Future<Map<String, String?>> fetchPublicBranding() async {
    final uri = _apiUri('/api/v1/site/branding');
    if (uri == null) {
      throw HireApiException('OURNEAR_API_BASE is not configured.');
    }

    final response = await _client
        .get(uri, headers: _jsonHeaders)
        .timeout(const Duration(seconds: 8));

    if (response.statusCode >= 200 && response.statusCode < 300) {
      final decoded = jsonDecode(response.body) as Map<String, dynamic>;
      final data = decoded['data'] as Map<String, dynamic>? ?? {};
      final appName = data['app_name'] as String? ?? '';
      final logoUrl = data['logo_url'] as String?;
      final trimmedLogo = logoUrl?.trim();
      return {
        'app_name': appName.trim().isEmpty ? null : appName.trim(),
        'logo_url': (trimmedLogo == null || trimmedLogo.isEmpty)
            ? null
            : trimmedLogo,
      };
    }

    throw _failure('Branding request failed', response);
  }

  Future<bool> setJobSaved(String slug, {required bool save}) async {
    final path = save
        ? '/api/v1/account/saved-jobs/$slug'
        : '/api/v1/account/saved-jobs/$slug';
    final uri = _apiUri(path);
    if (uri == null) {
      throw HireApiException('OURNEAR_API_BASE is not configured.');
    }

    final response = await _client
        .send(
          save
              ? (http.Request('POST', uri)
                  ..headers.addAll(_headers(requireAuth: true)))
              : (http.Request('DELETE', uri)
                  ..headers.addAll(_headers(requireAuth: true))),
        )
        .then(http.Response.fromStream)
        .timeout(const Duration(seconds: 12));

    if (response.statusCode == 401) {
      await _authService?.clearSession();
      throw HireApiException('Session expired. Sign in again.', statusCode: 401);
    }

    if (response.statusCode == 422) {
      final decoded = _tryDecodeMap(response.body);
      throw HireApiException(
        _firstErrorMessage(decoded) ?? 'Could not update saved job.',
        statusCode: 422,
      );
    }

    if (response.statusCode >= 200 && response.statusCode < 300) {
      final decoded = _tryDecodeMap(response.body);
      final data = decoded?['data'] as Map<String, dynamic>? ?? {};
      return data['is_saved'] as bool? ?? save;
    }

    throw _failure('Saved job request failed', response);
  }

  PaginatedPublicJobs _decodeJobsList(http.Response response) {
    if (response.statusCode == 422) {
      throw HireApiException(
        'Invalid job list parameters (HTTP 422).',
        statusCode: 422,
      );
    }

    if (response.statusCode >= 200 && response.statusCode < 300) {
      final decoded = jsonDecode(response.body) as Map<String, dynamic>;
      return PaginatedPublicJobs.fromJson(decoded);
    }

    throw _failure('Job list request failed', response);
  }

  Map<String, String> _headers({
    bool requireAuth = false,
    bool includeAuth = false,
  }) {
    final headers = Map<String, String>.from(_jsonHeaders);
    final token = _authService?.bearerToken;
    if (requireAuth && (token == null || token.isEmpty)) {
      throw HireApiException('Not authenticated.', statusCode: 401);
    }
    if ((requireAuth || includeAuth) && token != null && token.isNotEmpty) {
      headers['Authorization'] = 'Bearer $token';
    }
    return headers;
  }

  HireApiException _failure(String fallback, http.Response response) {
    if (response.statusCode == 429) {
      return HireApiException(
        'Too many requests. Try again shortly.',
        statusCode: 429,
      );
    }
    final decoded = _tryDecodeMap(response.body);
    return HireApiException(
      decoded?['message'] as String? ??
          '$fallback (HTTP ${response.statusCode}).',
      statusCode: response.statusCode,
    );
  }

  Map<String, dynamic>? _tryDecodeMap(String body) {
    try {
      return jsonDecode(body) as Map<String, dynamic>;
    } catch (_) {
      return null;
    }
  }

  String? _firstErrorMessage(Map<String, dynamic>? decoded) {
    if (decoded == null) {
      return null;
    }
    final errors = decoded['errors'];
    if (errors is Map) {
      for (final value in errors.values) {
        if (value is List && value.isNotEmpty) {
          return value.first.toString();
        }
        if (value is String && value.isNotEmpty) {
          return value;
        }
      }
    }
    return decoded['message'] as String?;
  }

  Map<String, String> get _jsonHeaders => const {'Accept': 'application/json'};

  Future<Map<String, dynamic>> fetchProfilePersonal() async {
    return _authorizedData('GET', '/api/v1/account/profile/personal');
  }

  Future<Map<String, dynamic>> fetchProfileProfessional() async {
    return _authorizedData('GET', '/api/v1/account/profile/professional');
  }

  Future<Map<String, dynamic>> patchProfilePersonal(
    Map<String, dynamic> body,
  ) async {
    return _authorizedData(
      'PATCH',
      '/api/v1/account/profile/personal',
      body: body,
    );
  }

  /// Multipart PATCH for profile photo; [fields] must include required personal keys.
  Future<Map<String, dynamic>> patchProfilePersonalWithPhoto({
    required Map<String, String> fields,
    required List<int> photoBytes,
    required String photoFilename,
  }) async {
    final uri = _apiUri('/api/v1/account/profile/personal');
    if (uri == null) {
      throw HireApiException('OURNEAR_API_BASE is not configured.');
    }
    final token = _authService?.bearerToken;
    if (token == null || token.isEmpty) {
      throw HireApiException('Not authenticated.', statusCode: 401);
    }

    final request = http.MultipartRequest('PATCH', uri)
      ..headers['Accept'] = 'application/json'
      ..headers['Authorization'] = 'Bearer $token'
      ..fields.addAll(fields)
      ..files.add(
        http.MultipartFile.fromBytes(
          'photo',
          photoBytes,
          filename: photoFilename,
        ),
      );

    final streamed = await _client.send(request).timeout(const Duration(seconds: 45));
    final response = await http.Response.fromStream(streamed);
    if (response.statusCode == 401) {
      await _authService?.clearSession();
      throw HireApiException('Session expired. Sign in again.', statusCode: 401);
    }
    if (response.statusCode == 422) {
      throw HireApiException(
        _firstErrorMessage(_tryDecodeMap(response.body)) ??
            'The profile photo could not be uploaded.',
        statusCode: 422,
      );
    }
    if (response.statusCode >= 200 && response.statusCode < 300) {
      final decoded = jsonDecode(response.body) as Map<String, dynamic>;
      return Map<String, dynamic>.from(
        decoded['data'] as Map<String, dynamic>? ?? {},
      );
    }
    throw _failure('Profile photo upload failed', response);
  }

  Future<Map<String, dynamic>> addProfileEducation({
    required String institution,
    String? qualification,
  }) async {
    return _authorizedData(
      'POST',
      '/api/v1/account/profile/professional/educations',
      body: {
        'institution': institution,
        if (qualification != null && qualification.trim().isNotEmpty)
          'qualification': qualification.trim(),
      },
    );
  }

  Future<Map<String, dynamic>> addProfileCertification({
    required String name,
    String? issuer,
  }) async {
    return _authorizedData(
      'POST',
      '/api/v1/account/profile/professional/certifications',
      body: {
        'name': name,
        if (issuer != null && issuer.trim().isNotEmpty) 'issuer': issuer.trim(),
      },
    );
  }

  Future<Map<String, dynamic>> patchProfileProfessional(
    Map<String, dynamic> body,
  ) async {
    return _authorizedData(
      'PATCH',
      '/api/v1/account/profile/professional',
      body: body,
    );
  }

  Future<Map<String, dynamic>> addProfileSkill(String name) async {
    return _authorizedData(
      'POST',
      '/api/v1/account/profile/professional/skills',
      body: {'name': name},
    );
  }

  Future<Map<String, dynamic>> addEmploymentHistory({
    required String designation,
    required String company,
  }) async {
    return _authorizedData(
      'POST',
      '/api/v1/account/profile/professional/employment-histories',
      body: {
        'designation': designation,
        'company': company,
        'is_current': true,
      },
    );
  }

  Future<Map<String, dynamic>> updateEmploymentHistory(
    int id, {
    required String designation,
    required String company,
  }) async {
    return _authorizedData(
      'PATCH',
      '/api/v1/account/profile/professional/employment-histories/$id',
      body: {
        'designation': designation,
        'company': company,
        'is_current': true,
      },
    );
  }

  Future<List<Map<String, dynamic>>> fetchResumeLibrary() async {
    final data = await _authorizedData('GET', '/api/v1/account/resumes');
    final rows = data['data'];
    if (rows is! List) {
      return const [];
    }
    return rows
        .whereType<Map>()
        .map((row) => Map<String, dynamic>.from(row))
        .toList();
  }

  Future<String?> fetchResumeBuilderPreviewText() async {
    final data = await _authorizedData('GET', '/api/v1/account/resumes/builder');
    final nested = data['data'];
    if (nested is! Map) {
      return null;
    }
    final preview = nested['preview'];
    if (preview is Map) {
      final text = preview['text'] as String? ?? preview['plain_text'] as String?;
      if (text != null && text.trim().isNotEmpty) {
        return text;
      }
    }
    if (preview is String && preview.trim().isNotEmpty) {
      return preview;
    }
    return null;
  }

  Future<Map<String, dynamic>> uploadResumeFile({
    required List<int> bytes,
    required String filename,
    String? title,
  }) async {
    final uri = _apiUri('/api/v1/account/resumes');
    if (uri == null) {
      throw HireApiException('OURNEAR_API_BASE is not configured.');
    }
    final token = _authService?.bearerToken;
    if (token == null || token.isEmpty) {
      throw HireApiException('Not authenticated.', statusCode: 401);
    }

    final request = http.MultipartRequest('POST', uri)
      ..headers['Accept'] = 'application/json'
      ..headers['Authorization'] = 'Bearer $token'
      ..files.add(
        http.MultipartFile.fromBytes(
          'file',
          bytes,
          filename: filename,
        ),
      );
    if (title != null && title.trim().isNotEmpty) {
      request.fields['title'] = title.trim();
    }
    request.fields['is_primary'] = 'true';

    final streamed = await _client.send(request).timeout(const Duration(seconds: 30));
    final response = await http.Response.fromStream(streamed);
    if (response.statusCode == 401) {
      await _authService?.clearSession();
      throw HireApiException('Session expired. Sign in again.', statusCode: 401);
    }
    if (response.statusCode == 422) {
      throw HireApiException(
        _firstErrorMessage(_tryDecodeMap(response.body)) ??
            'The resume file could not be uploaded.',
        statusCode: 422,
      );
    }
    if (response.statusCode >= 200 && response.statusCode < 300) {
      final decoded = jsonDecode(response.body) as Map<String, dynamic>;
      return decoded['data'] as Map<String, dynamic>? ?? {};
    }
    throw _failure('Resume upload failed', response);
  }

  Future<Map<String, List<String>>> fetchJobFilterOptions() async {
    final uri = _apiUri('/api/v1/jobs/filters');
    if (uri == null) {
      throw HireApiException('OURNEAR_API_BASE is not configured.');
    }
    final response = await _client
        .get(uri, headers: _jsonHeaders)
        .timeout(const Duration(seconds: 12));
    if (response.statusCode >= 200 && response.statusCode < 300) {
      final decoded = jsonDecode(response.body) as Map<String, dynamic>;
      final data = decoded['data'] as Map<String, dynamic>? ?? {};
      List<String> readList(String key) {
        final raw = data[key];
        if (raw is! List) {
          return const [];
        }
        return raw.map((e) => e.toString()).where((e) => e.isNotEmpty).toList();
      }

      return {
        'industries': readList('industries'),
        'locations': readList('locations'),
        'categories': readList('categories'),
        'employment_types': readList('employment_types'),
        'experiences': readList('experiences'),
      };
    }
    throw _failure('Job filters request failed', response);
  }

  Future<Map<String, dynamic>> fetchSiteContact() async {
    final uri = _apiUri('/api/v1/site/contact');
    if (uri == null) {
      throw HireApiException('OURNEAR_API_BASE is not configured.');
    }
    final response = await _client
        .get(uri, headers: _jsonHeaders)
        .timeout(const Duration(seconds: 12));
    if (response.statusCode >= 200 && response.statusCode < 300) {
      final decoded = jsonDecode(response.body) as Map<String, dynamic>;
      return Map<String, dynamic>.from(
        decoded['data'] as Map<String, dynamic>? ?? {},
      );
    }
    throw _failure('Site contact request failed', response);
  }

  Future<Map<String, dynamic>> updateProfileEducation(
    int id, {
    required String institution,
    String? qualification,
  }) async {
    return _authorizedData(
      'PATCH',
      '/api/v1/account/profile/professional/educations/$id',
      body: {
        'institution': institution,
        if (qualification != null && qualification.trim().isNotEmpty)
          'qualification': qualification.trim(),
      },
    );
  }

  Future<void> deleteProfileEducation(int id) async {
    await _authorizedData(
      'DELETE',
      '/api/v1/account/profile/professional/educations/$id',
    );
  }

  Future<Map<String, dynamic>> updateProfileCertification(
    int id, {
    required String name,
    String? issuer,
  }) async {
    return _authorizedData(
      'PATCH',
      '/api/v1/account/profile/professional/certifications/$id',
      body: {
        'name': name,
        if (issuer != null && issuer.trim().isNotEmpty) 'issuer': issuer.trim(),
      },
    );
  }

  Future<void> deleteProfileCertification(int id) async {
    await _authorizedData(
      'DELETE',
      '/api/v1/account/profile/professional/certifications/$id',
    );
  }

  Future<void> deleteEmploymentHistory(int id) async {
    await _authorizedData(
      'DELETE',
      '/api/v1/account/profile/professional/employment-histories/$id',
    );
  }

  Future<void> deleteResume(int resumeId) async {
    await _authorizedData('DELETE', '/api/v1/account/resumes/$resumeId');
  }

  Future<void> makeResumePrimary(int resumeId) async {
    await _authorizedData(
      'POST',
      '/api/v1/account/resumes/$resumeId/primary',
    );
  }

  Future<List<Map<String, dynamic>>> fetchNotifications({int page = 1}) async {
    final uri = _apiUri('/api/v1/account/notifications', {'page': '$page'});
    if (uri == null) {
      throw HireApiException('OURNEAR_API_BASE is not configured.');
    }
    final response = await _client
        .get(uri, headers: _headers(requireAuth: true))
        .timeout(const Duration(seconds: 12));
    if (response.statusCode == 401) {
      await _authService?.clearSession();
      throw HireApiException('Session expired. Sign in again.', statusCode: 401);
    }
    if (response.statusCode >= 200 && response.statusCode < 300) {
      final decoded = jsonDecode(response.body) as Map<String, dynamic>;
      final rows = decoded['data'] as List<dynamic>? ?? [];
      return rows
          .whereType<Map>()
          .map((row) => Map<String, dynamic>.from(row))
          .toList();
    }
    throw _failure('Notifications request failed', response);
  }

  Future<void> markNotificationRead(String id) async {
    await _authorizedData('POST', '/api/v1/account/notifications/$id');
  }

  Future<List<Map<String, dynamic>>> fetchMessagesInbox() async {
    final data = await _authorizedList('GET', '/api/v1/account/messages/inbox');
    return data;
  }

  Future<Map<String, dynamic>> fetchApplicationMessages(int applicationId) async {
    return _authorizedData(
      'GET',
      '/api/v1/account/applications/$applicationId/messages',
    );
  }

  Future<Map<String, dynamic>> sendApplicationMessage(
    int applicationId,
    String body,
  ) async {
    return _authorizedData(
      'POST',
      '/api/v1/account/applications/$applicationId/messages',
      body: {'body': body.trim()},
    );
  }

  Future<List<Map<String, dynamic>>> _authorizedList(
    String method,
    String path,
  ) async {
    final uri = _apiUri(path);
    if (uri == null) {
      throw HireApiException('OURNEAR_API_BASE is not configured.');
    }
    final response = await _client
        .get(uri, headers: _headers(requireAuth: true))
        .timeout(const Duration(seconds: 12));
    if (response.statusCode == 401) {
      await _authService?.clearSession();
      throw HireApiException('Session expired. Sign in again.', statusCode: 401);
    }
    if (response.statusCode >= 200 && response.statusCode < 300) {
      final decoded = jsonDecode(response.body) as Map<String, dynamic>;
      final rows = decoded['data'] as List<dynamic>? ?? [];
      return rows
          .whereType<Map>()
          .map((row) => Map<String, dynamic>.from(row))
          .toList();
    }
    throw _failure('Request failed', response);
  }

  Future<Map<String, dynamic>> fetchEmailVerificationStatus() async {
    return _authorizedData('GET', '/api/v1/account/verification/email');
  }

  Future<void> resendEmailVerification() async {
    await _authorizedData(
      'POST',
      '/api/v1/account/verification/email/resend',
    );
  }

  Future<Map<String, dynamic>> _authorizedData(
    String method,
    String path, {
    Map<String, dynamic>? body,
  }) async {
    final uri = _apiUri(path);
    if (uri == null) {
      throw HireApiException('OURNEAR_API_BASE is not configured.');
    }

    late http.Response response;
    if (method == 'GET') {
      response = await _client
          .get(uri, headers: _headers(requireAuth: true))
          .timeout(const Duration(seconds: 12));
    } else if (method == 'POST') {
      response = await _client
          .post(
            uri,
            headers: {
              ..._headers(requireAuth: true),
              'Content-Type': 'application/json',
            },
            body: body == null ? null : jsonEncode(body),
          )
          .timeout(const Duration(seconds: 12));
    } else {
      response = await _client
          .send(
            http.Request(method, uri)
              ..headers.addAll({
                ..._headers(requireAuth: true),
                'Content-Type': 'application/json',
              })
              ..body = body == null ? '' : jsonEncode(body),
          )
          .then(http.Response.fromStream)
          .timeout(const Duration(seconds: 12));
    }

    if (response.statusCode == 401) {
      await _authService?.clearSession();
      throw HireApiException('Session expired. Sign in again.', statusCode: 401);
    }
    if (response.statusCode == 422) {
      throw HireApiException(
        _firstErrorMessage(_tryDecodeMap(response.body)) ??
            'Request could not be completed.',
        statusCode: 422,
      );
    }
    if (response.statusCode == 403) {
      throw HireApiException(
        _tryDecodeMap(response.body)?['message'] as String? ??
            'You do not have permission for this action.',
        statusCode: 403,
      );
    }
    if (response.statusCode >= 200 && response.statusCode < 300) {
      final decoded = _tryDecodeMap(response.body) ?? {};
      final data = decoded['data'];
      if (data is Map<String, dynamic>) {
        return data;
      }
      if (data is Map) {
        return Map<String, dynamic>.from(data);
      }
      return decoded;
    }
    throw _failure('Request failed', response);
  }
}
