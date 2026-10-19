import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';

/// Login / Sign up switcher for the candidate auth experience.
class AuthTabSelector extends StatelessWidget {
  const AuthTabSelector({
    super.key,
    required this.index,
    required this.onChanged,
    this.enabled = true,
  });

  final int index;
  final ValueChanged<int> onChanged;
  final bool enabled;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Semantics(
      label: index == 0 ? 'Login tab selected' : 'Sign up tab selected',
      child: Container(
        key: const Key('auth-tab-selector'),
        decoration: BoxDecoration(
          color: scheme.surfaceContainerHighest,
          borderRadius: OurNearTokens.borderRadiusMd,
          border: Border.all(color: scheme.outline),
        ),
        padding: const EdgeInsets.all(OurNearTokens.space4),
        child: Row(
          children: [
            Expanded(
              child: _Segment(
                key: const Key('auth-tab-login'),
                label: 'Login',
                selected: index == 0,
                onTap: enabled ? () => onChanged(0) : null,
              ),
            ),
            Expanded(
              child: _Segment(
                key: const Key('auth-tab-signup'),
                label: 'Sign up',
                selected: index == 1,
                onTap: enabled ? () => onChanged(1) : null,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _Segment extends StatelessWidget {
  const _Segment({
    super.key,
    required this.label,
    required this.selected,
    this.onTap,
  });

  final String label;
  final bool selected;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Material(
      color: selected ? scheme.primary : Colors.transparent,
      borderRadius: OurNearTokens.borderRadiusSm,
      child: InkWell(
        onTap: onTap,
        borderRadius: OurNearTokens.borderRadiusSm,
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: OurNearTokens.space10),
          child: Text(
            label,
            textAlign: TextAlign.center,
            style: Theme.of(context).textTheme.titleSmall?.copyWith(
              color: selected ? scheme.onPrimary : scheme.onSurface,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
      ),
    );
  }
}
