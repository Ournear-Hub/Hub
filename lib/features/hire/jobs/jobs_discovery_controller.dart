import 'dart:async';

import 'package:flutter/foundation.dart';

/// Shared keyword between candidate Home and the Jobs tab.
class JobsDiscoveryController extends ChangeNotifier {
  JobsDiscoveryController({Duration debounce = const Duration(milliseconds: 350)})
      : _debounceDuration = debounce;

  final Duration _debounceDuration;
  String _keyword = '';
  Timer? _debounce;

  String get keyword => _keyword;

  void search(String value) {
    _keyword = value.trim();
    notifyListeners();
  }

  void searchDebounced(String value) {
    _debounce?.cancel();
    _debounce = Timer(_debounceDuration, () => search(value));
  }

  void clear() {
    _debounce?.cancel();
    _keyword = '';
    notifyListeners();
  }

  @override
  void dispose() {
    _debounce?.cancel();
    super.dispose();
  }
}
