import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Candidate-app dark mode only. Does not affect the unified OurNear shell.
class CandidateThemeController extends ChangeNotifier {
  CandidateThemeController({FlutterSecureStorage? storage})
    : _storage = storage ?? const FlutterSecureStorage();

  static const storageKey = 'ournear_hire_candidate_dark_mode';

  final FlutterSecureStorage _storage;
  var _dark = false;
  var _loaded = false;

  bool get isDark => _dark;
  bool get loaded => _loaded;

  Future<void> load() async {
    final value = await _storage.read(key: storageKey);
    _dark = value == '1';
    _loaded = true;
    notifyListeners();
  }

  Future<void> setDark(bool enabled) async {
    _dark = enabled;
    await _storage.write(key: storageKey, value: enabled ? '1' : '0');
    notifyListeners();
  }

  Future<void> toggle() => setDark(!_dark);
}
