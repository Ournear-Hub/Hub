import 'package:ournear_mobile/core/api/api_config.dart';
import 'package:ournear_mobile/core/api/hire_api_exception.dart';
import 'package:ournear_mobile/core/api/hire_api_service.dart';

/// Server-driven Hire branding. Falls back when the API is unavailable.
class HireBrandingData {
  const HireBrandingData({
    required this.appName,
    required this.logoUrl,
    required this.source,
  });

  final String appName;
  final String? logoUrl;
  final HireBrandingSource source;
}

enum HireBrandingSource { fallback, liveApi }

class HireBrandingService {
  HireBrandingService({HireApiService? apiService}) : _apiService = apiService;

  final HireApiService? _apiService;

  static const fallback = HireBrandingData(
    appName: 'OurNear Hire',
    logoUrl: null,
    source: HireBrandingSource.fallback,
  );

  Future<HireBrandingData> load() async {
    final api = _apiService ?? HireApiService();
    if (!ApiConfig.hasApiBase && !api.isConfigured) {
      return fallback;
    }
    try {
      final payload = await api.fetchPublicBranding();
      final appName = payload['app_name'] ?? fallback.appName;
      return HireBrandingData(
        appName: appName,
        logoUrl: payload['logo_url'],
        source: HireBrandingSource.liveApi,
      );
    } on HireApiException {
      return fallback;
    } catch (_) {
      return fallback;
    }
  }
}
