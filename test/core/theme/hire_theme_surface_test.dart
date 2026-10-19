import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:ournear_mobile/core/theme/ournear_colors.dart';
import 'package:ournear_mobile/core/theme/ournear_theme.dart';

void main() {
  test('candidate theme uses the approved neutral surface and palette', () {
    final theme = OurNearTheme.hireLight();

    expect(theme.scaffoldBackgroundColor, OurNearColors.hireCanvas);
    expect(theme.scaffoldBackgroundColor, const Color(0xFFF5F7F8));
    expect(theme.appBarTheme.backgroundColor, const Color(0xFFF5F7F8));
    expect(theme.colorScheme.primary, const Color(0xFF214449));
    expect(theme.colorScheme.secondary, const Color(0xFFF15A24));
    expect(theme.cardTheme.color, const Color(0xFFFFFFFF));
    expect(theme.navigationBarTheme.backgroundColor, const Color(0xFFFFFFFF));
  });

  test('unified theme keeps its existing canvas and brand colour', () {
    final theme = OurNearTheme.light();

    expect(theme.scaffoldBackgroundColor, const Color(0xFFF8FAFC));
    expect(theme.appBarTheme.backgroundColor, const Color(0xFFF8FAFC));
    expect(theme.colorScheme.primary, OurNearColors.brand);
  });
}
