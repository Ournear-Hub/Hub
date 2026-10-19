import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:ournear_mobile/core/api/hire_api_exception.dart';
import 'package:ournear_mobile/core/auth/auth_service.dart';
import 'package:ournear_mobile/core/auth/auth_user.dart';

enum AuthStatus { unknown, unauthenticated, authenticated }

enum BootstrapFailureKind { none, network, server }

class AuthController extends ChangeNotifier {
  AuthController(this._authService);

  final AuthService _authService;

  AuthStatus status = AuthStatus.unknown;
  AuthUser? user;
  String? errorMessage;
  bool busy = false;
  bool bootstrapSettled = false;
  BootstrapFailureKind bootstrapFailure = BootstrapFailureKind.none;
  String? bootstrapFailureMessage;

  Future<void>? _bootstrapFuture;
  var _bootstrapCallCount = 0;

  int get bootstrapCallCount => _bootstrapCallCount;

  bool get hasStoredSessionToken => _authService.bearerToken != null;

  Future<void> bootstrap() {
    _bootstrapFuture ??= _runBootstrap();
    return _bootstrapFuture!;
  }

  Future<void> retryBootstrap() async {
    bootstrapFailure = BootstrapFailureKind.none;
    bootstrapFailureMessage = null;
    status = AuthStatus.unknown;
    bootstrapSettled = false;
    _bootstrapFuture = null;
    await bootstrap();
  }

  void dismissBootstrapFailureForPublicBrowsing() {
    bootstrapFailure = BootstrapFailureKind.none;
    bootstrapFailureMessage = null;
    status = AuthStatus.unauthenticated;
    user = null;
    notifyListeners();
  }

  Future<void> _runBootstrap() async {
    _bootstrapCallCount += 1;
    busy = true;
    bootstrapFailure = BootstrapFailureKind.none;
    bootstrapFailureMessage = null;
    notifyListeners();
    await _authService.restoreSession();
    if (_authService.bearerToken == null) {
      status = AuthStatus.unauthenticated;
      user = null;
      busy = false;
      bootstrapSettled = true;
      notifyListeners();
      return;
    }
    try {
      user = await _authService.fetchAccount();
      status = AuthStatus.authenticated;
      errorMessage = null;
      bootstrapFailure = BootstrapFailureKind.none;
    } on HireApiException catch (error) {
      user = null;
      if (error.statusCode == 401) {
        status = AuthStatus.unauthenticated;
        bootstrapFailure = BootstrapFailureKind.none;
      } else if (_authService.bearerToken != null) {
        status = AuthStatus.unauthenticated;
        bootstrapFailure = BootstrapFailureKind.server;
        bootstrapFailureMessage = error.message;
      } else {
        status = AuthStatus.unauthenticated;
      }
    } catch (_) {
      user = null;
      if (_authService.bearerToken != null) {
        status = AuthStatus.unauthenticated;
        bootstrapFailure = BootstrapFailureKind.network;
        bootstrapFailureMessage =
            'Could not reach OurNear. Your sign-in may still be stored on this device.';
      } else {
        status = AuthStatus.unauthenticated;
      }
    } finally {
      busy = false;
      bootstrapSettled = true;
      notifyListeners();
    }
  }

  Future<bool> register({
    required String username,
    required String displayName,
    required String mobile,
    required String email,
    required String password,
    required String passwordConfirmation,
  }) async {
    busy = true;
    errorMessage = null;
    notifyListeners();
    try {
      user = await _authService.register(
        username: username,
        name: displayName,
        mobile: mobile,
        email: email,
        password: password,
        passwordConfirmation: passwordConfirmation,
      );
      status = AuthStatus.authenticated;
      return true;
    } on HireApiException catch (error) {
      errorMessage = error.message;
      status = AuthStatus.unauthenticated;
      user = null;
      return false;
    } finally {
      busy = false;
      notifyListeners();
    }
  }

  Future<bool> login(String login, String password) async {
    busy = true;
    errorMessage = null;
    notifyListeners();
    try {
      user = await _authService.login(login: login, password: password);
      status = AuthStatus.authenticated;
      return true;
    } on HireApiException catch (error) {
      errorMessage = error.message;
      status = AuthStatus.unauthenticated;
      user = null;
      return false;
    } finally {
      busy = false;
      notifyListeners();
    }
  }

  Future<void> refreshAccount() async {
    if (_authService.bearerToken == null) {
      return;
    }
    try {
      user = await _authService.fetchAccount();
      notifyListeners();
    } on HireApiException {
      // Keep the last known user snapshot when refresh fails.
    }
  }

  Future<void> logout() async {
    busy = true;
    notifyListeners();
    await _authService.logout();
    user = null;
    status = AuthStatus.unauthenticated;
    busy = false;
    notifyListeners();
  }
}
