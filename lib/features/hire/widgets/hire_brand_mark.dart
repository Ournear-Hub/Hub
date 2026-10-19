import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/theme/ournear_colors.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';

/// Compact OurNear Hire wordmark for auth and onboarding surfaces.
class HireBrandMark extends StatelessWidget {
  const HireBrandMark({super.key, this.compact = false});

  final bool compact;

  @override
  Widget build(BuildContext context) {
    final titleStyle = Theme.of(context).textTheme.headlineSmall?.copyWith(
      fontWeight: FontWeight.w700,
      letterSpacing: -0.3,
    );
    final hireStyle = titleStyle?.copyWith(color: OurNearColors.hireOrange);

    if (compact) {
      return Semantics(
        header: true,
        label: 'OurNear Hire',
        child: RichText(
          key: const Key('hire-brand-mark'),
          text: TextSpan(
            style: titleStyle,
            children: [
              const TextSpan(text: 'OurNear '),
              TextSpan(text: 'Hire', style: hireStyle),
            ],
          ),
        ),
      );
    }

    return Semantics(
      header: true,
      label: 'OurNear Hire',
      child: Column(
        key: const Key('hire-brand-mark'),
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          RichText(
            text: TextSpan(
              style: titleStyle,
              children: [
                const TextSpan(text: 'OurNear '),
                TextSpan(text: 'Hire', style: hireStyle),
              ],
            ),
          ),
          const SizedBox(height: OurNearTokens.space6),
          Text(
            'Candidate',
            style: Theme.of(context).textTheme.labelSmall,
          ),
        ],
      ),
    );
  }
}
