import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';

/// Company initial or logo placeholder for job list/detail (HireMe-style hierarchy).
class JobCompanyAvatar extends StatelessWidget {
  const JobCompanyAvatar({
    super.key,
    this.companyName,
    this.logoUrl,
    this.size = 44,
  });

  final String? companyName;
  final String? logoUrl;
  final double size;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final initial = _initial(companyName);
    final url = logoUrl?.trim();
    if (url != null && url.isNotEmpty) {
      return ClipRRect(
        borderRadius: OurNearTokens.borderRadiusMd,
        child: Image.network(
          url,
          width: size,
          height: size,
          fit: BoxFit.cover,
          errorBuilder: (context, error, stackTrace) =>
              _fallback(context, scheme, initial),
        ),
      );
    }
    return _fallback(context, scheme, initial);
  }

  Widget _fallback(BuildContext context, ColorScheme scheme, String initial) {
    return Container(
      width: size,
      height: size,
      alignment: Alignment.center,
      decoration: BoxDecoration(
        color: scheme.primary.withValues(alpha: 0.12),
        borderRadius: OurNearTokens.borderRadiusMd,
      ),
      child: Text(
        initial,
        style: Theme.of(context).textTheme.titleMedium?.copyWith(
          color: scheme.primary,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }

  String _initial(String? name) {
    final trimmed = name?.trim();
    if (trimmed == null || trimmed.isEmpty) {
      return '?';
    }
    return trimmed.characters.first.toUpperCase();
  }
}
