import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/branding/hire_branding_controller.dart';
import 'package:ournear_mobile/core/branding/hire_branding_service.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';
import 'package:ournear_mobile/features/hire/widgets/hire_brand_mark.dart';
import 'package:provider/provider.dart';

/// Platform + Hire branding for authentication screens.
class OurNearAuthHeader extends StatelessWidget {
  const OurNearAuthHeader({super.key});

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    HireBrandingData branding;
    try {
      branding = context.watch<HireBrandingController>().data;
    } on ProviderNotFoundException {
      branding = HireBrandingService.fallback;
    }
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            Semantics(
              label: '${branding.appName} logo',
              child: _AuthLogoMark(
                branding: branding,
                scheme: scheme,
              ),
            ),
            const SizedBox(width: OurNearTokens.space12),
            const Expanded(child: HireBrandMark(compact: true)),
          ],
        ),
        const SizedBox(height: OurNearTokens.space8),
        Text(
          'One OurNear account for every app — including OurNear Hire.',
          style: Theme.of(context).textTheme.bodySmall,
        ),
      ],
    );
  }
}

class _AuthLogoMark extends StatelessWidget {
  const _AuthLogoMark({required this.branding, required this.scheme});

  final HireBrandingData branding;
  final ColorScheme scheme;

  @override
  Widget build(BuildContext context) {
    final logoUrl = branding.logoUrl;
    if (logoUrl != null && logoUrl.trim().isNotEmpty) {
      return ClipRRect(
        key: const Key('ournear-auth-logo'),
        borderRadius: OurNearTokens.borderRadiusMd,
        child: Image.network(
          logoUrl,
          width: 48,
          height: 48,
          fit: BoxFit.cover,
          errorBuilder: (context, error, stackTrace) => _letterMark(context),
        ),
      );
    }
    return _letterMark(context);
  }

  Widget _letterMark(BuildContext context) {
    return Container(
      key: const Key('ournear-auth-logo'),
      width: 48,
      height: 48,
      decoration: BoxDecoration(
        color: scheme.primary,
        borderRadius: OurNearTokens.borderRadiusMd,
      ),
      alignment: Alignment.center,
      child: Text(
        branding.appName.isNotEmpty ? branding.appName.characters.first : 'O',
        style: Theme.of(context).textTheme.titleLarge?.copyWith(
          color: scheme.onPrimary,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }
}
