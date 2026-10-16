import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/layout/ournear_responsive_frame.dart';
import 'package:ournear_mobile/core/navigation/ournear_motion.dart';
import 'package:ournear_mobile/core/navigation/root_tab_swipe_coordinator.dart';
import 'package:ournear_mobile/core/navigation/shell_destination.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';

/// Shared root shell: bottom navigation, indexed children, optional one-tab horizontal swipes.
class RootTabShell extends StatefulWidget {
  const RootTabShell({
    super.key,
    required this.destinations,
    this.initialIndex = 0,
    this.enableRootSwipe = true,
    this.onIndexChanged,
  });

  final List<ShellDestination> destinations;
  final int initialIndex;
  final bool enableRootSwipe;
  final ValueChanged<int>? onIndexChanged;

  @override
  State<RootTabShell> createState() => RootTabShellState();
}

class RootTabShellState extends State<RootTabShell> {
  late int _index = widget.initialIndex.clamp(
    0,
    widget.destinations.length - 1,
  );
  late final RootTabSwipeCoordinator _swipeCoordinator =
      RootTabSwipeCoordinator(tabCount: widget.destinations.length);

  int get currentIndex => _index;

  void goToIndex(int index) {
    if (index < 0 || index >= widget.destinations.length || index == _index) {
      return;
    }
    setState(() => _index = index);
    widget.onIndexChanged?.call(_index);
  }

  void goToId(String id) {
    final idx = widget.destinations.indexWhere((d) => d.id == id);
    if (idx >= 0) {
      goToIndex(idx);
    }
  }

  @override
  Widget build(BuildContext context) {
    assert(
      widget.destinations.isNotEmpty,
      'RootTabShell requires at least one destination.',
    );

    final body = IndexedStack(
      index: _index,
      children: [
        for (final destination in widget.destinations)
          KeyedSubtree(
            key: ValueKey('shell-tab-${destination.id}'),
            child: destination.builder(context),
          ),
      ],
    );

    Widget shellBody = SafeArea(child: OurNearResponsiveFrame(child: body));
    if (widget.enableRootSwipe && widget.destinations.length > 1) {
      shellBody = NotificationListener<ScrollNotification>(
        onNotification: (notification) {
          if (notification.metrics.axis == Axis.horizontal &&
              notification is ScrollUpdateNotification) {
            _swipeCoordinator.onNestedHorizontalScroll();
          }
          return false;
        },
        child: GestureDetector(
          behavior: HitTestBehavior.deferToChild,
          onHorizontalDragStart: (details) =>
              _swipeCoordinator.onDragStart(details, context),
          onHorizontalDragEnd: (details) {
            final delta = _swipeCoordinator.resolveTabDelta(details, context);
            final next = _swipeCoordinator.clampIndex(_index, delta);
            if (next != _index) {
              goToIndex(next);
            }
            _swipeCoordinator.onDragEnd(details, context);
          },
          onHorizontalDragCancel: _swipeCoordinator.onDragCancel,
          child: shellBody,
        ),
      );
    }

    return Scaffold(
      body: shellBody,
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        animationDuration: OurNearMotion.navigationBarAnimationDuration(
          context,
        ),
        onDestinationSelected: goToIndex,
        destinations: [
          for (final destination in widget.destinations)
            NavigationDestination(
              icon: Icon(destination.icon, size: OurNearTokens.iconMd),
              selectedIcon: Icon(
                destination.selectedIcon,
                size: OurNearTokens.iconMd,
              ),
              label: destination.label,
            ),
        ],
      ),
    );
  }
}
