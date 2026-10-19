import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';

class HireWelcomeBanner extends StatelessWidget {
  const HireWelcomeBanner({
    super.key,
    this.title = 'Welcome to OurNear Hire',
    required this.message,
  });

  final String title;
  final String message;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Container(
      key: const Key('home-welcome-banner'),
      width: double.infinity,
      padding: const EdgeInsets.all(OurNearTokens.space16),
      decoration: BoxDecoration(
        color: scheme.primary,
        borderRadius: OurNearTokens.borderRadiusXl,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
              color: scheme.onPrimary,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: OurNearTokens.space6),
          Text(
            message,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
              color: scheme.onPrimary.withValues(alpha: 0.92),
            ),
          ),
        ],
      ),
    );
  }
}
