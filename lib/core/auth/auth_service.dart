import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;
import 'package:ournear_mobile/core/api/api_config.dart';
import 'package:ournear_mobile/core/api/hire_api_exception.dart';
import 'package:ournear_mobile/core/auth/auth_user.dart';

const _tokenStorageKey = 'ournear_mobile_bearer_token';

class AuthService {
  AuthService({
    http.Client? client,
    FlutterSecureStorage? secureStorage,
    String? apiBaseOverride,
  })  : _client = client ?? http.Client(),
        _secureStorage = secureStorage ?? const FlutterSecureStorage(),
        _apiBaseOverride = apiBaseOverride;

  final http.Client _client;
  final FlutterSecureStorage _secureStorage;
  final String? _apiBaseOverride;

  String? _memoryToken;

  String? get bearerToken => _memoryToken;

  String? get _apiBase {
    final override = _apiBaseOverride?.trim();
    if (override != null && override.isNotEmpty) {
      return override.replaceAll(RegExp(r'/+$'), '');
    }
    if (ApiConfig.hasApiBase) {
      return ApiConfig.apiBase.trim().replaceAll(RegExp(r'/+$'), '');
    }
    return null;
  }

  Future<void> restoreSession() async {
    _memoryToken = await _secureStorage.read(key: _tokenStorageKey);
  }

  Future<AuthUser> register({
    required String username,
    required String name,
    required String mobile,
    required String email,
    required String password,
    required String passwordConfirmation,
  }) async {
    final base = _apiBase;
    if (base == null) {
      throw HireApiException('OURNEAR_API_BASE is not configured.');
    }

    final uri = Uri.parse('$base/api/v1/auth/register');
    final response = await _client
        .post(
          uri,
          headers: const {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
          body: jsonEncode({
            'username': username.trim(),
            'name': name.trim(),
            'mobile': mobile.trim(),
            'email': email.trim(),
            'password': password,
            'password_confirmation': passwordConfirmation,
            'accept_terms': true,
            'accept_privacy': true,
            'device_name': 'ournear-mobile',
          }),
        )
        .timeout(const Duration(seconds: 12));

    if (response.statusCode == 422) {
      throw HireApiException(
        _validationMessage(response.body) ?? 'Registration could not be completed.',
        statusCode: 422,
      );
    }

    if (response.statusCode >= 200 && response.statusCode < 300) {
      final decoded = jsonDecode(response.body) as Map<String, dynamic>;
      final token = decoded['token'] as String?;
      if (token == null || token.isEmpty) {
        throw HireApiException('Registration succeeded but no token was returned.');
      }
      await _persistToken(token);
      return AuthUser.fromJson(decoded['user'] as Map<String, dynamic>? ?? {});
    }

    throw HireApiException(
      'Registration failed (HTTP ${response.statusCode}).',
      statusCode: response.statusCode,
    );
  }

  Future<void> requestPasswordRecovery({required String email}) async {
    final base = _apiBase;
    if (base == null) {
      throw HireApiException('OURNEAR_API_BASE is not configured.');
    }

    final response = await _client
        .post(
          Uri.parse('$base/api/v1/auth/forgot-password'),
          headers: const {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
          body: jsonEncode({'email': email.trim()}),
        )
        .timeout(const Duration(seconds: 12));

    if (response.statusCode == 422) {
      throw HireApiException(
        _validationMessage(response.body) ?? 'Enter a valid email address.',
        statusCode: 422,
      );
    }

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return;
    }

    throw HireApiException(
      'Password recovery failed (HTTP ${response.statusCode}).',
      statusCode: response.statusCode,
    );
  }

  Future<AuthUser> login({required String login, required String password}) async {
    final base = _apiBase;
    if (base == null) {
      throw HireApiException('OURNEAR_API_BASE is not configured.');
    }

    final uri = Uri.parse('$base/api/v1/auth/login');
    final response = await _client
        .post(
          uri,
          headers: const {'Accept': 'application/json', 'Content-Type': 'application/json'},
          body: jsonEncode({
            'login': login.trim(),
            'password': password,
            'device_name': 'ournear-mobile',
          }),
        )
        .timeout(const Duration(seconds: 12));

    if (response.statusCode == 422) {
      final decoded = jsonDecode(response.body) as Map<String, dynamic>;
      final errors = decoded['errors'] as Map<String, dynamic>? ?? {};
      final loginError = errors['login'];
      final message = loginError is List && loginError.isNotEmpty
          ? loginError.first.toString()
          : 'These credentials do not match our records.';
      throw HireApiException(message, statusCode: 422);
    }

    if (response.statusCode >= 200 && response.statusCode < 300) {
      final decoded = jsonDecode(response.body) as Map<String, dynamic>;
      final token = decoded['token'] as String?;
      if (token == null || token.isEmpty) {
        throw HireApiException('Login succeeded but no token was returned.');
      }
      await _persistToken(token);
      return AuthUser.fromJson(decoded['user'] as Map<String, dynamic>? ?? {});
    }

    throw HireApiException('Login failed (HTTP ${response.statusCode}).', statusCode: response.statusCode);
  }

  Future<AuthUser> fetchAccount() async {
    final base = _apiBase;
    final token = _memoryToken;
    if (base == null || token == null) {
      throw HireApiException('Not authenticated.');
    }

    final response = await _client
        .get(
          Uri.parse('$base/api/v1/account'),
          headers: _authHeaders(token),
        )
        .timeout(const Duration(seconds: 12));

    if (response.statusCode == 401) {
      await clearSession();
      throw HireApiException('Session expired. Sign in again.', statusCode: 401);
    }

    if (response.statusCode >= 200 && response.statusCode < 300) {
      final decoded = jsonDecode(response.body) as Map<String, dynamic>;
      return AuthUser.fromJson(decoded['data'] as Map<String, dynamic>? ?? {});
    }

    throw HireApiException('Could not load account (HTTP ${response.statusCode}).', statusCode: response.statusCode);
  }

  Future<void> logout() async {
    final base = _apiBase;
    final token = _memoryToken;
    if (base != null && token != null) {
      try {
        await _client
            .post(
              Uri.parse('$base/api/v1/auth/logout'),
              headers: _authHeaders(token),
            )
            .timeout(const Duration(seconds: 8));
      } catch (_) {
        // Clear local session even if network logout fails.
      }
    }
    await clearSession();
  }

  Future<void> clearSession() async {
    _memoryToken = null;
    await _secureStorage.delete(key: _tokenStorageKey);
  }

  Future<void> _persistToken(String token) async {
    _memoryToken = token;
    await _secureStorage.write(key: _tokenStorageKey, value: token);
  }

  Map<String, String> _authHeaders(String token) {
    return {
      'Accept': 'application/json',
      'Authorization': 'Bearer $token',
    };
  }

  String? _validationMessage(String body) {
    try {
      final decoded = jsonDecode(body) as Map<String, dynamic>;
      final errors = decoded['errors'] as Map<String, dynamic>? ?? {};
      for (final value in errors.values) {
        if (value is List && value.isNotEmpty) {
          return value.first.toString();
        }
        if (value is String && value.isNotEmpty) {
          return value;
        }
      }
      return decoded['message'] as String?;
    } catch (_) {
      return null;
    }
  }
}
