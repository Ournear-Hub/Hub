import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/theme/ournear_colors.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';

enum StatusBadgeTone { neutral, success, warning, error, info, brand }

class StatusBadge extends StatelessWidget {
  const StatusBadge({
    super.key,
    required this.label,
    this.tone = StatusBadgeTone.neutral,
  });

  final String label;
  final StatusBadgeTone tone;

  @override
  Widget build(BuildContext context) {
    final color = switch (tone) {
      StatusBadgeTone.neutral => OurNearColors.muted,
      StatusBadgeTone.success => OurNearColors.success,
      StatusBadgeTone.warning => OurNearColors.warning,
      StatusBadgeTone.error => OurNearColors.error,
      StatusBadgeTone.info => OurNearColors.info,
      StatusBadgeTone.brand => Theme.of(context).colorScheme.primary,
    };

    return Semantics(
      label: label,
      child: Container(
        padding: const EdgeInsets.symmetric(
          horizontal: OurNearTokens.space8,
          vertical: OurNearTokens.space4,
        ),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.12),
          borderRadius: OurNearTokens.borderRadiusSm,
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: OurNearTokens.fontSize11,
            fontWeight: FontWeight.w600,
            color: color,
          ),
        ),
      ),
    );
  }
}
