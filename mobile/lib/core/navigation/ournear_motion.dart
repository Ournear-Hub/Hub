import 'package:flutter/material.dart';

/// Accessibility motion for shells that opt in.
///
/// Reduced motion removes decorative route transitions and the navigation-bar
/// indicator animation. Root tab taps and one-step swipes still change tabs.
abstract final class OurNearMotion {
  static bool prefersReducedMotion(BuildContext context) {
    return MediaQuery.disableAnimationsOf(context);
  }

  static const PageTransitionsTheme reducedPageTransitions =
      PageTransitionsTheme(
        builders: <TargetPlatform, PageTransitionsBuilder>{
          TargetPlatform.android: InstantPageTransitionsBuilder(),
          TargetPlatform.iOS: InstantPageTransitionsBuilder(),
          TargetPlatform.macOS: InstantPageTransitionsBuilder(),
          TargetPlatform.windows: InstantPageTransitionsBuilder(),
          TargetPlatform.linux: InstantPageTransitionsBuilder(),
          TargetPlatform.fuchsia: InstantPageTransitionsBuilder(),
        },
      );

  /// Null keeps the framework default indicator duration.
  static Duration? navigationBarAnimationDuration(BuildContext context) {
    return prefersReducedMotion(context) ? Duration.zero : null;
  }
}

/// Shows the destination immediately, with no zoom, slide, or fade.
class InstantPageTransitionsBuilder extends PageTransitionsBuilder {
  const InstantPageTransitionsBuilder();

  @override
  Widget buildTransitions<T>(
    PageRoute<T> route,
    BuildContext context,
    Animation<double> animation,
    Animation<double> secondaryAnimation,
    Widget child,
  ) {
    return child;
  }
}
