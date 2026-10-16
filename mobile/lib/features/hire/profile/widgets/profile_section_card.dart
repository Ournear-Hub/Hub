import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/theme/hire_theme_context.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';

/// Social-style profile section: title, body, card surface (presentation only).
class ProfileSectionCard extends StatelessWidget {
  const ProfileSectionCard({
    super.key,
    required this.title,
    required this.body,
    this.sectionKey,
  });

  final String title;
  final String body;
  final Key? sectionKey;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: OurNearTokens.space12),
      child: Card(
        color: context.hireCardSurface,
        margin: EdgeInsets.zero,
        child: Padding(
          padding: const EdgeInsets.all(OurNearTokens.space16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: Theme.of(context).textTheme.titleMedium),
              const SizedBox(height: OurNearTokens.space8),
              Divider(color: context.hireBorder, height: 1),
              const SizedBox(height: OurNearTokens.space8),
              Text(
                body,
                key: sectionKey,
                style: Theme.of(context).textTheme.bodyMedium,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
