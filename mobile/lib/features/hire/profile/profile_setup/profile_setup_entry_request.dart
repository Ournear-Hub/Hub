/// One-shot session flag when the candidate chooses setup from onboarding.
class ProfileSetupEntryRequest {
  static var _pending = false;

  static void request() => _pending = true;

  static bool consume() {
    if (!_pending) {
      return false;
    }
    _pending = false;
    return true;
  }
}
