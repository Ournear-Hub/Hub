import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/theme/hire_dark_tokens.dart';
import 'package:ournear_mobile/core/theme/ournear_colors.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';

abstract final class OurNearTheme {
  static ThemeData light() => _build(
    seed: OurNearColors.brand,
    primary: OurNearColors.brand,
    canvas: OurNearColors.canvas,
  );

  /// ON Hire candidate / employer apps.
  static ThemeData hireLight() => _build(
    seed: OurNearColors.hireTeal,
    primary: OurNearColors.hireTeal,
    canvas: OurNearColors.hireCanvas,
    brightness: Brightness.light,
  );

  static ThemeData hireDark() => _build(
    seed: OurNearColors.hireTeal,
    primary: OurNearColors.hireTeal,
    canvas: HireDarkTokens.canvas,
    brightness: Brightness.dark,
    surface: HireDarkTokens.surface,
    surfaceElevated: HireDarkTokens.surfaceElevated,
    onSurface: HireDarkTokens.primaryText,
    onSurfaceVariant: HireDarkTokens.secondaryText,
    muted: HireDarkTokens.secondaryText,
    line: HireDarkTokens.border,
    inputFill: HireDarkTokens.inputFill,
    disabled: HireDarkTokens.disabledText,
  );

  static ThemeData _build({
    required Color seed,
    required Color primary,
    required Color canvas,
    Brightness brightness = Brightness.light,
    Color surface = OurNearColors.white,
    Color surfaceElevated = OurNearColors.white,
    Color onSurface = OurNearColors.ink,
    Color onSurfaceVariant = OurNearColors.muted,
    Color muted = OurNearColors.muted,
    Color line = OurNearColors.line,
    Color inputFill = OurNearColors.white,
    Color disabled = OurNearColors.disabled,
  }) {
    final isDark = brightness == Brightness.dark;
    final onPrimary = isDark ? HireDarkTokens.primaryText : OurNearColors.white;

    final colorScheme = ColorScheme.fromSeed(
      seedColor: seed,
      brightness: brightness,
      surface: surface,
      onSurface: onSurface,
      onSurfaceVariant: onSurfaceVariant,
      primary: primary,
      onPrimary: onPrimary,
      secondary: OurNearColors.hireOrange,
      onSecondary: OurNearColors.white,
      error: OurNearColors.error,
      outline: line,
      surfaceContainerHighest: surfaceElevated,
    );

    final inputBorder = OutlineInputBorder(
      borderRadius: OurNearTokens.borderRadiusMd,
      borderSide: BorderSide(
        color: line,
        width: OurNearTokens.borderWidth,
      ),
    );

    return ThemeData(
      useMaterial3: true,
      brightness: brightness,
      colorScheme: colorScheme,
      scaffoldBackgroundColor: canvas,
      iconTheme: IconThemeData(color: onSurface, size: OurNearTokens.iconMd),
      primaryIconTheme: IconThemeData(color: onPrimary),
      appBarTheme: AppBarTheme(
        centerTitle: false,
        elevation: OurNearTokens.appBarElevation,
        scrolledUnderElevation: OurNearTokens.appBarElevation,
        backgroundColor: canvas,
        foregroundColor: onSurface,
        iconTheme: IconThemeData(color: onSurface),
        titleTextStyle: TextStyle(
          fontSize: OurNearTokens.fontSize18,
          fontWeight: FontWeight.w600,
          color: onSurface,
        ),
      ),
      navigationBarTheme: NavigationBarThemeData(
        elevation: OurNearTokens.cardElevation,
        height: OurNearTokens.navBarHeight,
        backgroundColor: surface,
        indicatorColor: primary.withValues(alpha: isDark ? 0.28 : 0.12),
        labelTextStyle: WidgetStateProperty.resolveWith((states) {
          final selected = states.contains(WidgetState.selected);
          return TextStyle(
            fontSize: OurNearTokens.fontSize12,
            fontWeight: selected ? FontWeight.w600 : FontWeight.w500,
            color: selected ? primary : muted,
          );
        }),
        iconTheme: WidgetStateProperty.resolveWith((states) {
          final selected = states.contains(WidgetState.selected);
          return IconThemeData(
            color: selected ? OurNearColors.hireOrange : muted,
            size: OurNearTokens.iconMd,
          );
        }),
      ),
      drawerTheme: DrawerThemeData(
        backgroundColor: surface,
        surfaceTintColor: Colors.transparent,
      ),
      listTileTheme: ListTileThemeData(
        iconColor: onSurface,
        textColor: onSurface,
      ),
      dividerTheme: DividerThemeData(color: line, space: 1),
      cardTheme: CardThemeData(
        elevation: OurNearTokens.cardElevation,
        color: surface,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(
          borderRadius: OurNearTokens.borderRadiusXl,
          side: BorderSide(
            color: line,
            width: OurNearTokens.borderWidth,
          ),
        ),
        margin: EdgeInsets.zero,
      ),
      dialogTheme: DialogThemeData(
        backgroundColor: surfaceElevated,
        surfaceTintColor: Colors.transparent,
        titleTextStyle: TextStyle(
          fontSize: OurNearTokens.fontSize18,
          fontWeight: FontWeight.w600,
          color: onSurface,
        ),
        contentTextStyle: TextStyle(
          fontSize: OurNearTokens.fontSize14,
          color: onSurfaceVariant,
        ),
      ),
      snackBarTheme: SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        backgroundColor: surfaceElevated,
        contentTextStyle: TextStyle(color: onSurface),
        shape: RoundedRectangleBorder(
          borderRadius: OurNearTokens.borderRadiusMd,
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          foregroundColor: onPrimary,
          backgroundColor: primary,
          minimumSize: const Size(64, OurNearTokens.minTouchTarget),
          padding: const EdgeInsets.symmetric(
            horizontal: OurNearTokens.space16,
            vertical: OurNearTokens.space12,
          ),
          shape: RoundedRectangleBorder(
            borderRadius: OurNearTokens.borderRadiusMd,
          ),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: onSurface,
          minimumSize: const Size(64, OurNearTokens.minTouchTarget),
          padding: const EdgeInsets.symmetric(
            horizontal: OurNearTokens.space16,
            vertical: OurNearTokens.space12,
          ),
          shape: RoundedRectangleBorder(
            borderRadius: OurNearTokens.borderRadiusMd,
          ),
          side: BorderSide(color: line),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: primary,
          minimumSize: const Size(64, OurNearTokens.minTouchTarget),
          padding: const EdgeInsets.symmetric(
            horizontal: OurNearTokens.space12,
            vertical: OurNearTokens.space8,
          ),
        ),
      ),
      iconButtonTheme: IconButtonThemeData(
        style: ButtonStyle(
          foregroundColor: WidgetStatePropertyAll(onSurface),
          minimumSize: const WidgetStatePropertyAll(
            Size(OurNearTokens.minTouchTarget, OurNearTokens.minTouchTarget),
          ),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: inputFill,
        contentPadding: const EdgeInsets.symmetric(
          horizontal: OurNearTokens.space14,
          vertical: OurNearTokens.space12,
        ),
        border: inputBorder,
        enabledBorder: inputBorder,
        focusedBorder: inputBorder.copyWith(
          borderSide: BorderSide(color: primary, width: 1.5),
        ),
        errorBorder: inputBorder.copyWith(
          borderSide: const BorderSide(color: OurNearColors.error),
        ),
        focusedErrorBorder: inputBorder.copyWith(
          borderSide: const BorderSide(color: OurNearColors.error, width: 1.5),
        ),
        labelStyle: TextStyle(
          color: muted,
          fontSize: OurNearTokens.fontSize14,
        ),
        hintStyle: TextStyle(
          color: disabled,
          fontSize: OurNearTokens.fontSize14,
        ),
        errorStyle: const TextStyle(
          color: OurNearColors.error,
          fontSize: OurNearTokens.fontSize12,
        ),
      ),
      chipTheme: ChipThemeData(
        backgroundColor: surfaceElevated,
        disabledColor: surfaceElevated.withValues(alpha: 0.5),
        selectedColor: primary.withValues(alpha: isDark ? 0.35 : 0.15),
        labelStyle: TextStyle(color: onSurface),
        secondaryLabelStyle: TextStyle(color: onSurfaceVariant),
        side: BorderSide(color: line),
        checkmarkColor: isDark ? OurNearColors.hireOrange : primary,
      ),
      switchTheme: SwitchThemeData(
        thumbColor: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) {
            return OurNearColors.hireOrange;
          }
          return isDark ? HireDarkTokens.secondaryText : OurNearColors.line;
        }),
        trackColor: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) {
            return primary.withValues(alpha: 0.45);
          }
          return isDark ? HireDarkTokens.border : OurNearColors.line;
        }),
      ),
      textTheme: TextTheme(
        headlineSmall: TextStyle(
          fontSize: OurNearTokens.fontSize20,
          fontWeight: FontWeight.w600,
          color: onSurface,
          height: OurNearTokens.lineHeightTight,
        ),
        titleMedium: TextStyle(
          fontSize: OurNearTokens.fontSize16,
          fontWeight: FontWeight.w600,
          color: onSurface,
        ),
        bodyMedium: TextStyle(
          fontSize: OurNearTokens.fontSize14,
          height: OurNearTokens.lineHeightBody,
          color: onSurface,
        ),
        bodySmall: TextStyle(
          fontSize: OurNearTokens.fontSize13,
          height: OurNearTokens.lineHeightSmall,
          color: onSurfaceVariant,
        ),
        labelSmall: TextStyle(
          fontSize: OurNearTokens.fontSize11,
          fontWeight: FontWeight.w600,
          letterSpacing: 0.4,
          color: muted,
        ),
      ),
    );
  }
}
