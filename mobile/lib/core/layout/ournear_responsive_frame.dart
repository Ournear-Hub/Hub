import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';

/// Centers page content and caps width on wide viewports (web/tablet preview).
class OurNearResponsiveFrame extends StatelessWidget {
  const OurNearResponsiveFrame({
    super.key,
    required this.child,
    this.maxWidth = OurNearTokens.maxContentWidth,
    this.alignment = Alignment.topCenter,
  });

  final Widget child;
  final double maxWidth;
  final Alignment alignment;

  @override
  Widget build(BuildContext context) {
    return Align(
      alignment: alignment,
      child: ConstrainedBox(
        constraints: BoxConstraints(maxWidth: maxWidth),
        child: child,
      ),
    );
  }
}
