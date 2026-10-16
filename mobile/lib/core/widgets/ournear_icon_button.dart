import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';

/// Icon action with a minimum touch target for accessibility.
class OurNearIconButton extends StatelessWidget {
  const OurNearIconButton({
    super.key,
    required this.icon,
    required this.tooltip,
    this.onPressed,
  });

  final IconData icon;
  final String tooltip;
  final VoidCallback? onPressed;

  @override
  Widget build(BuildContext context) {
    return Semantics(
      button: true,
      enabled: onPressed != null,
      label: tooltip,
      child: IconButton(
        tooltip: tooltip,
        onPressed: onPressed,
        icon: Icon(icon, size: OurNearTokens.iconMd),
        constraints: const BoxConstraints(
          minWidth: OurNearTokens.minTouchTarget,
          minHeight: OurNearTokens.minTouchTarget,
        ),
      ),
    );
  }
}
