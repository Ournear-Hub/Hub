import 'package:flutter/material.dart';
import 'package:flutter_tabler_icons/flutter_tabler_icons.dart';
import 'package:ournear_mobile/core/theme/ournear_colors.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';

class PlatformSuccessState extends StatelessWidget {
  const PlatformSuccessState({
    super.key,
    required this.title,
    required this.message,
    this.action,
  });

  final String title;
  final String message;
  final Widget? action;

  @override
  Widget build(BuildContext context) {
    return Semantics(
      container: true,
      label: '$title. $message',
      child: Padding(
        padding: const EdgeInsets.symmetric(
          horizontal: OurNearTokens.space24,
          vertical: OurNearTokens.space32,
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(TablerIcons.circle_check, size: OurNearTokens.iconLg, color: OurNearColors.success),
            const SizedBox(height: OurNearTokens.space16),
            Text(title, textAlign: TextAlign.center, style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: OurNearTokens.space8),
            Text(message, textAlign: TextAlign.center, style: Theme.of(context).textTheme.bodySmall),
            if (action != null) ...[
              const SizedBox(height: OurNearTokens.space20),
              action!,
            ],
          ],
        ),
      ),
    );
  }
}
