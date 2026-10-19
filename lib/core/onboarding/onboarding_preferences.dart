import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Local first-run flag only. It is not server data and does not create a candidate record.
class OnboardingPreferences {
  OnboardingPreferences({FlutterSecureStorage? storage})
    : _storage = storage ?? const FlutterSecureStorage();

  static const storageKey = 'ournear_hire_candidate_onboarding_complete';

  final FlutterSecureStorage _storage;

  Future<bool> isComplete() async {
    final value = await _storage.read(key: storageKey);
    return value == '1';
  }

  Future<void> markComplete() async {
    await _storage.write(key: storageKey, value: '1');
  }
}
