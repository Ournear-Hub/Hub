import 'package:flutter/material.dart';

/// Theme-derived colours for Hire candidate UI. Prefer these over hardcoded light tokens.
extension HireThemeContext on BuildContext {
  Color get hireCanvas => Theme.of(this).scaffoldBackgroundColor;

  Color get hireCardSurface =>
      Theme.of(this).cardTheme.color ?? Theme.of(this).colorScheme.surface;

  Color get hirePrimaryText => Theme.of(this).colorScheme.onSurface;

  Color get hireSecondaryText =>
      Theme.of(this).textTheme.bodySmall?.color ??
      Theme.of(this).colorScheme.onSurfaceVariant;

  Color get hireBorder =>
      Theme.of(this).dividerTheme.color ?? Theme.of(this).colorScheme.outline;

  Color get hireInputFill =>
      Theme.of(this).inputDecorationTheme.fillColor ??
      Theme.of(this).colorScheme.surfaceContainerHighest;
}
