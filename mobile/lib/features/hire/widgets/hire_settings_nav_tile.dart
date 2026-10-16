import 'package:flutter/material.dart';
import 'package:flutter_tabler_icons/flutter_tabler_icons.dart';
import 'package:ournear_mobile/core/theme/hire_theme_context.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';

/// Settings row: bordered card + chevron, consistent with Hire list patterns.
class HireSettingsNavTile extends StatelessWidget {
  const HireSettingsNavTile({
    super.key,
    required this.label,
    required this.destination,
    this.icon,
  });

  final String label;
  final Widget destination;
  final IconData? icon;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: OurNearTokens.space8),
      child: Card(
        color: context.hireCardSurface,
        child: ListTile(
          key: Key('settings-$label'),
          minVerticalPadding: OurNearTokens.space12,
          leading: icon != null
              ? Icon(icon, color: Theme.of(context).colorScheme.primary)
              : null,
          title: Text(label),
          trailing: Icon(
            TablerIcons.chevron_right,
            size: OurNearTokens.iconSm,
            color: context.hireSecondaryText,
          ),
          onTap: () {
            Navigator.of(context).push(
              MaterialPageRoute<void>(builder: (_) => destination),
            );
          },
        ),
      ),
    );
  }
}
