import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:ournear_mobile/core/onboarding/onboarding_preferences.dart';

/// Marks onboarding complete so candidate shell tests reach the four tabs.
void setHireCandidateTestStorage([Map<String, String> extra = const {}]) {
  FlutterSecureStorage.setMockInitialValues(Map<String, String>.from({
    OnboardingPreferences.storageKey: '1',
    ...extra,
  }));
}

void setHireCandidateFirstLaunchStorage([
  Map<String, String> extra = const {},
]) {
  FlutterSecureStorage.setMockInitialValues(Map<String, String>.from(extra));
}
