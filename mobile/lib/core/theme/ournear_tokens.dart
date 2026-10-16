import 'package:flutter/material.dart';

/// Canonical spacing, radii, typography scale, and layout constants for OurNear / ON Hire.
abstract final class OurNearTokens {
  // Spacing
  static const space2 = 2.0;
  static const space4 = 4.0;
  static const space6 = 6.0;
  static const space8 = 8.0;
  static const space10 = 10.0;
  static const space12 = 12.0;
  static const space14 = 14.0;
  static const space16 = 16.0;
  static const space20 = 20.0;
  static const space24 = 24.0;
  static const space32 = 32.0;

  static const pageHorizontal = space16;
  static const pageVertical = space16;
  static const listGap = space10;

  // Radii
  static const radiusSm = 6.0;
  static const radiusMd = 8.0;
  static const radiusLg = 10.0;
  static const radiusXl = 12.0;

  static BorderRadius get borderRadiusSm => BorderRadius.circular(radiusSm);
  static BorderRadius get borderRadiusMd => BorderRadius.circular(radiusMd);
  static BorderRadius get borderRadiusLg => BorderRadius.circular(radiusLg);
  static BorderRadius get borderRadiusXl => BorderRadius.circular(radiusXl);

  // Borders & elevation
  static const borderWidth = 1.0;
  static const cardElevation = 0.0;
  static const appBarElevation = 0.0;

  // Icons
  static const iconSm = 18.0;
  static const iconMd = 22.0;
  static const iconLg = 40.0;

  // Navigation
  static const navBarHeight = 64.0;
  static const appBarHeight = kToolbarHeight;

  // Layout (wide web / tablet previews)
  static const maxContentWidth = 720.0;
  static const minTouchTarget = 44.0;

  // Root tab swipe
  static const swipeVelocityThreshold = 300.0;
  static const swipeEdgeInset = 24.0;

  // Typography (base sizes; scaled via MediaQuery.textScaler in widgets)
  static const fontSize11 = 11.0;
  static const fontSize12 = 12.0;
  static const fontSize13 = 13.0;
  static const fontSize14 = 14.0;
  static const fontSize16 = 16.0;
  static const fontSize18 = 18.0;
  static const fontSize20 = 20.0;

  static const lineHeightTight = 1.25;
  static const lineHeightBody = 1.45;
  static const lineHeightSmall = 1.4;

  static EdgeInsets pagePadding(BuildContext context) {
    final bottom = MediaQuery.viewInsetsOf(context).bottom;
    return EdgeInsets.fromLTRB(
      pageHorizontal,
      pageVertical,
      pageHorizontal,
      pageVertical + bottom,
    );
  }
}
