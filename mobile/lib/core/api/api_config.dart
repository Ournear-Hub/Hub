/// Runtime configuration for the OurNear Laravel backend.
///
/// Pass at build/run time, e.g.:
/// `flutter run --dart-define=OURNEAR_WEB_BASE=https://ournear.example`
abstract final class ApiConfig {
  /// Public web origin for OurNear Hire (session/Inertia today — not a JSON API).
  static const webBase = String.fromEnvironment(
    'OURNEAR_WEB_BASE',
    defaultValue: '',
  );

  /// Laravel origin for JSON API (`/api/v1/...`). Usually the same host as web, without a path suffix.
  static const apiBase = String.fromEnvironment(
    'OURNEAR_API_BASE',
    defaultValue: '',
  );

  static bool get hasWebBase => webBase.trim().isNotEmpty;

  static bool get hasApiBase => apiBase.trim().isNotEmpty;

  static Uri? jobsIndexUri() {
    if (!hasWebBase) {
      return null;
    }
    final base = webBase.endsWith('/') ? webBase.substring(0, webBase.length - 1) : webBase;
    return Uri.parse('$base/jobs');
  }

  static Uri? hireLandingUri() {
    if (!hasWebBase) {
      return null;
    }
    final base = webBase.endsWith('/') ? webBase.substring(0, webBase.length - 1) : webBase;
    return Uri.parse('$base/hire');
  }
}
