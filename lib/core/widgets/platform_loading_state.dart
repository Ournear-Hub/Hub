import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';

class PlatformLoadingState extends StatelessWidget {
  const PlatformLoadingState({super.key, this.message});

  final String? message;

  @override
  Widget build(BuildContext context) {
    return Semantics(
      label: message ?? 'Loading',
      child: Padding(
        padding: const EdgeInsets.symmetric(
          horizontal: OurNearTokens.space24,
          vertical: OurNearTokens.space32,
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const CircularProgressIndicator(),
            if (message != null) ...[
              const SizedBox(height: OurNearTokens.space16),
              Text(message!, textAlign: TextAlign.center, style: Theme.of(context).textTheme.bodySmall),
            ],
          ],
        ),
      ),
    );
  }
}
