import 'package:flutter/material.dart';

/// Brand and semantic colours for OurNear platform and ON Hire apps.
abstract final class OurNearColors {
  // ON Hire brand (canonical for Hire apps)
  static const hireTeal = Color(0xFF214449);
  static const hireTealDark = Color(0xFF1A3639);
  static const hireOrange = Color(0xFFF15A24);
  static const hireOrangeDark = Color(0xFFD94E1F);

  // Platform shell accent (legacy unified client)
  static const brand = Color(0xFF2563EB);
  static const brandDark = Color(0xFF1D4ED8);

  // Neutrals & surfaces
  static const ink = Color(0xFF0F172A);
  static const muted = Color(0xFF64748B);
  static const line = Color(0xFFE2E8F0);

  /// Unified shell page background.
  static const canvas = Color(0xFFF8FAFC);

  /// Candidate and Hire shell page background.
  static const hireCanvas = Color(0xFFF5F7F8);
  static const surface = Color(0xFFF1F5F9);
  static const white = Color(0xFFFFFFFF);

  // Semantic states
  static const success = Color(0xFF059669);
  static const warning = Color(0xFFD97706);
  static const error = Color(0xFFDC2626);
  static const info = Color(0xFF0284C7);
  static const disabled = Color(0xFF94A3B8);

  static const onHirePrimary = white;
  static const onHireSecondary = hireTeal;
}
