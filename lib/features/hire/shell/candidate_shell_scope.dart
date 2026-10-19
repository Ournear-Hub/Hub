import 'package:flutter/material.dart';

class CandidateShellScope extends InheritedWidget {
  const CandidateShellScope({
    super.key,
    required this.openMenu,
    required this.menuEnabled,
    required super.child,
  });

  final VoidCallback openMenu;

  /// Menu drawer is only available from the Profile root tab.
  final bool menuEnabled;

  static CandidateShellScope? _scopeOf(BuildContext context) {
    return context.dependOnInheritedWidgetOfExactType<CandidateShellScope>();
  }

  static VoidCallback? maybeOpenMenu(BuildContext context) {
    final scope = _scopeOf(context);
    if (scope == null || !scope.menuEnabled) {
      return null;
    }
    return scope.openMenu;
  }

  static bool menuEnabledFor(BuildContext context) {
    return _scopeOf(context)?.menuEnabled ?? false;
  }

  @override
  bool updateShouldNotify(CandidateShellScope oldWidget) =>
      openMenu != oldWidget.openMenu || menuEnabled != oldWidget.menuEnabled;
}
