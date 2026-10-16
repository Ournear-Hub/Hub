import 'package:flutter/foundation.dart';
import 'package:ournear_mobile/core/branding/hire_branding_service.dart';

class HireBrandingController extends ChangeNotifier {
  HireBrandingController({HireBrandingService? service})
    : _service = service ?? HireBrandingService(),
      _data = HireBrandingService.fallback;

  final HireBrandingService _service;
  HireBrandingData _data;

  HireBrandingData get data => _data;

  Future<void> load() async {
    _data = await _service.load();
    notifyListeners();
  }
}
