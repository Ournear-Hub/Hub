import 'package:flutter/material.dart';
import 'package:flutter_tabler_icons/flutter_tabler_icons.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';
import 'package:ournear_mobile/core/widgets/ournear_primary_button.dart';

class PlatformOfflineState extends StatelessWidget {
  const PlatformOfflineState({
    super.key,
    this.message = 'You appear to be offline. Check your connection and try again.',
    this.onRetry,
  });

  final String message;
  final VoidCallback? onRetry;

  @override
  Widget build(BuildContext context) {
    return Semantics(
      container: true,
      label: message,
      child: Padding(
        padding: const EdgeInsets.symmetric(
          horizontal: OurNearTokens.space24,
          vertical: OurNearTokens.space32,
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              TablerIcons.wifi_off,
              size: OurNearTokens.iconLg,
              color: Theme.of(context).colorScheme.primary.withValues(alpha: 0.7),
            ),
            const SizedBox(height: OurNearTokens.space16),
            Text('Offline', textAlign: TextAlign.center, style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: OurNearTokens.space8),
            Text(message, textAlign: TextAlign.center, style: Theme.of(context).textTheme.bodySmall),
            if (onRetry != null) ...[
              const SizedBox(height: OurNearTokens.space20),
              OurNearPrimaryButton(label: 'Try again', onPressed: onRetry),
            ],
          ],
        ),
      ),
    );
  }
}
