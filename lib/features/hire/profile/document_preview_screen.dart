import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/theme/hire_theme_context.dart';
import 'package:ournear_mobile/features/hire/profile/profile_demo_banner.dart';

/// Text preview only. It does not open a private file URL.
class DocumentPreviewScreen extends StatelessWidget {
  const DocumentPreviewScreen({
    super.key,
    required this.title,
    required this.body,
    required this.demo,
  });

  final String title;
  final String? body;
  final bool demo;

  @override
  Widget build(BuildContext context) {
    final text = body?.trim() ?? '';
    return Scaffold(
      backgroundColor: context.hireCanvas,
      appBar: AppBar(title: Text(title)),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (demo) ...[const ProfileDemoBanner(), const SizedBox(height: 12)],
          Text(
            text.isEmpty
                ? 'Preview is not available. No generated document or authorized file was provided.'
                : text,
          ),
        ],
      ),
    );
  }
}
