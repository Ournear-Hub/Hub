import 'package:flutter/material.dart';
import 'package:flutter_tabler_icons/flutter_tabler_icons.dart';
import 'package:ournear_mobile/core/widgets/ournear_top_app_bar.dart';
import 'package:ournear_mobile/core/widgets/platform_empty_state.dart';

/// Non-product shell destination until a feature connects to Laravel.
class HireShellPlaceholderTab extends StatelessWidget {
  const HireShellPlaceholderTab({
    super.key,
    required this.title,
    required this.message,
    this.icon = TablerIcons.box,
    this.onMessagesTap,
    this.onNotificationsTap,
  });

  final String title;
  final String message;
  final IconData icon;
  final VoidCallback? onMessagesTap;
  final VoidCallback? onNotificationsTap;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: OurNearTopAppBar(
        title: title,
        onMessagesTap: onMessagesTap,
        onNotificationsTap: onNotificationsTap,
      ),
      body: Center(
        child: PlatformEmptyState(icon: icon, title: title, message: message),
      ),
    );
  }
}
