import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';

class OurNearCard extends StatelessWidget {
  const OurNearCard({
    super.key,
    required this.child,
    this.onTap,
    this.padding = const EdgeInsets.all(OurNearTokens.space16),
    this.margin,
  });

  final Widget child;
  final VoidCallback? onTap;
  final EdgeInsetsGeometry padding;
  final EdgeInsetsGeometry? margin;

  @override
  Widget build(BuildContext context) {
    final card = Card(
      margin: margin,
      child: InkWell(
        onTap: onTap,
        borderRadius: OurNearTokens.borderRadiusXl,
        child: Padding(padding: padding, child: child),
      ),
    );

    if (onTap == null) {
      return Card(margin: margin, child: Padding(padding: padding, child: child));
    }
    return card;
  }
}
