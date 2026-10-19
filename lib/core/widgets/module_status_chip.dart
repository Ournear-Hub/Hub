import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/widgets/status_badge.dart';

enum ModuleAvailability { activeShell, comingSoon, webOnly }

class ModuleStatusChip extends StatelessWidget {
  const ModuleStatusChip({super.key, required this.availability});

  final ModuleAvailability availability;

  @override
  Widget build(BuildContext context) {
    final (label, tone) = switch (availability) {
      ModuleAvailability.activeShell => ('Shell ready', StatusBadgeTone.success),
      ModuleAvailability.comingSoon => ('Coming soon', StatusBadgeTone.warning),
      ModuleAvailability.webOnly => ('Web today', StatusBadgeTone.brand),
    };

    return StatusBadge(label: label, tone: tone);
  }
}
