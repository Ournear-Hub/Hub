import 'package:flutter/material.dart';
import 'package:flutter_tabler_icons/flutter_tabler_icons.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';
import 'package:ournear_mobile/core/widgets/ournear_icon_button.dart';

/// App bar with optional contextual message / notification actions (never root tabs).
class OurNearTopAppBar extends StatelessWidget implements PreferredSizeWidget {
  const OurNearTopAppBar({
    super.key,
    required this.title,
    this.onMessagesTap,
    this.onNotificationsTap,
    this.actions,
  });

  final String title;
  final VoidCallback? onMessagesTap;
  final VoidCallback? onNotificationsTap;
  final List<Widget>? actions;

  @override
  Size get preferredSize => const Size.fromHeight(OurNearTokens.appBarHeight);

  @override
  Widget build(BuildContext context) {
    final trailing = <Widget>[
      if (onMessagesTap != null)
        OurNearIconButton(
          tooltip: 'Messages',
          onPressed: onMessagesTap,
          icon: TablerIcons.message,
        ),
      if (onNotificationsTap != null)
        OurNearIconButton(
          tooltip: 'Notifications',
          onPressed: onNotificationsTap,
          icon: TablerIcons.bell,
        ),
      ...?actions,
    ];

    return AppBar(
      title: Text(title),
      actions: trailing.isEmpty ? null : trailing,
    );
  }
}
