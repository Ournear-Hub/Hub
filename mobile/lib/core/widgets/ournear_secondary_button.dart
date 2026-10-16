import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';

class OurNearSecondaryButton extends StatelessWidget {
  const OurNearSecondaryButton({
    super.key,
    required this.label,
    this.onPressed,
    this.loading = false,
    this.icon,
  });

  final String label;
  final VoidCallback? onPressed;
  final bool loading;
  final IconData? icon;

  @override
  Widget build(BuildContext context) {
    final busy = loading || onPressed == null;
    return Semantics(
      button: true,
      enabled: !busy,
      label: label,
      child: OutlinedButton(
        onPressed: busy ? null : onPressed,
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (loading)
              const SizedBox(
                width: OurNearTokens.iconSm,
                height: OurNearTokens.iconSm,
                child: CircularProgressIndicator(strokeWidth: 2),
              )
            else if (icon != null)
              Icon(icon, size: OurNearTokens.iconSm),
            if (loading || icon != null) const SizedBox(width: OurNearTokens.space8),
            Text(label),
          ],
        ),
      ),
    );
  }
}
