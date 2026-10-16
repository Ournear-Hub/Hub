import 'package:ournear_mobile/core/api/hire_api_exception.dart';
import 'package:ournear_mobile/core/auth/auth_service.dart';

/// Registration and password recovery via the mobile JSON API.
abstract class AccountAccessRepository {
  Future<void> register({
    required String username,
    required String displayName,
    required String mobile,
    required String email,
    required String password,
    required String passwordConfirmation,
  });

  Future<void> requestPasswordRecovery({required String email});
}

class AccountAccessException implements Exception {
  const AccountAccessException(this.message);

  final String message;

  static const registrationUnavailable =
      'Account registration is not connected. No account was created and no request was sent.';

  static const recoveryUnavailable =
      'Password recovery is not connected. No email was sent and no reset was started.';

  @override
  String toString() => message;
}

class LiveAccountAccessRepository implements AccountAccessRepository {
  LiveAccountAccessRepository({AuthService? authService})
      : _authService = authService ?? AuthService();

  final AuthService _authService;

  @override
  Future<void> register({
    required String username,
    required String displayName,
    required String mobile,
    required String email,
    required String password,
    required String passwordConfirmation,
  }) async {
    try {
      await _authService.register(
        username: username,
        name: displayName,
        mobile: mobile,
        email: email,
        password: password,
        passwordConfirmation: passwordConfirmation,
      );
    } on HireApiException catch (error) {
      throw AccountAccessException(error.message);
    }
  }

  @override
  Future<void> requestPasswordRecovery({required String email}) async {
    try {
      await _authService.requestPasswordRecovery(email: email);
    } on HireApiException catch (error) {
      throw AccountAccessException(error.message);
    }
  }
}
