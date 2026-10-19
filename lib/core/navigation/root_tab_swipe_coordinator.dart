import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';

/// Decides whether a horizontal root-tab swipe may proceed and maps drag end to index delta.
///
/// Reduced motion does not cancel a tab change. Decorative page transitions and
/// the navigation indicator are reduced separately. Edge, focus, nested-scroll,
/// and pop-route guards still apply.
class RootTabSwipeCoordinator {
  RootTabSwipeCoordinator({required this.tabCount});

  final int tabCount;

  double? _dragStartGlobalX;
  bool _nestedHorizontalScrollActive = false;

  void onDragStart(DragStartDetails details, BuildContext context) {
    _dragStartGlobalX = details.globalPosition.dx;
    _nestedHorizontalScrollActive = false;
  }

  void onNestedHorizontalScroll() {
    _nestedHorizontalScrollActive = true;
  }

  void onDragEnd(DragEndDetails details, BuildContext context) {
    _dragStartGlobalX = null;
    _nestedHorizontalScrollActive = false;
  }

  void onDragCancel() {
    _dragStartGlobalX = null;
    _nestedHorizontalScrollActive = false;
  }

  /// Returns -1, 0, or +1 for previous tab, stay, or next tab. Never wraps.
  int resolveTabDelta(DragEndDetails details, BuildContext context) {
    if (tabCount <= 1) {
      return 0;
    }
    if (_shouldIgnoreSwipe(context)) {
      return 0;
    }

    final velocity = details.primaryVelocity ?? 0;
    if (velocity.abs() < OurNearTokens.swipeVelocityThreshold) {
      return 0;
    }
    if (velocity < 0) {
      return 1;
    }
    return -1;
  }

  bool _shouldIgnoreSwipe(BuildContext context) {
    if (_dragStartGlobalX == null) {
      return true;
    }

    final width = MediaQuery.sizeOf(context).width;
    final inset = OurNearTokens.swipeEdgeInset;
    if (_dragStartGlobalX! < inset || _dragStartGlobalX! > width - inset) {
      return true;
    }

    if (_nestedHorizontalScrollActive) {
      return true;
    }

    final focus = FocusManager.instance.primaryFocus;
    if (focus?.context != null &&
        focus!.context!.findAncestorWidgetOfExactType<EditableText>() != null) {
      return true;
    }

    final navigator = Navigator.maybeOf(context);
    if (navigator != null && navigator.canPop()) {
      return true;
    }

    return false;
  }

  int clampIndex(int current, int delta) {
    final next = current + delta;
    if (next < 0 || next >= tabCount) {
      return current;
    }
    return next;
  }
}
