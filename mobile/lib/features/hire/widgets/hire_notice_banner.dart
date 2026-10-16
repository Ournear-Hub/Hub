import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/theme/hire_theme_context.dart';
import 'package:ournear_mobile/core/theme/ournear_colors.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';

/// Standard inline notice for demo data, blocked features, or neutral info.
class HireNoticeBanner extends StatelessWidget {
  const HireNoticeBanner({
    super.key,
    required this.message,
    this.semanticsLabel,
    this.tone = HireNoticeTone.demo,
  });

  final String message;
  final String? semanticsLabel;
  final HireNoticeTone tone;

  @override
  Widget build(BuildContext context) {
    final (background, border) = switch (tone) {
      HireNoticeTone.demo => (
        OurNearColors.hireOrange.withValues(alpha: 0.1),
        OurNearColors.hireOrange.withValues(alpha: 0.35),
      ),
      HireNoticeTone.info => (
        Theme.of(context).colorScheme.primary.withValues(alpha: 0.08),
        Theme.of(context).colorScheme.primary.withValues(alpha: 0.25),
      ),
      HireNoticeTone.neutral => (
        context.hireCardSurface,
        context.hireBorder,
      ),
    };

    return Semantics(
      label: semanticsLabel ?? message,
      child: Container(
        key: const Key('hire-notice-banner'),
        width: double.infinity,
        padding: const EdgeInsets.all(OurNearTokens.space12),
        decoration: BoxDecoration(
          color: background,
          borderRadius: OurNearTokens.borderRadiusMd,
          border: Border.all(color: border),
        ),
        child: Text(
          message,
          style: Theme.of(context).textTheme.bodySmall?.copyWith(
            color: context.hireSecondaryText,
          ),
        ),
      ),
    );
  }
}

enum HireNoticeTone { demo, info, neutral }
