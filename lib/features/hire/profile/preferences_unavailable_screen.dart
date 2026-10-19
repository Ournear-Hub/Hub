import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/theme/hire_theme_context.dart';

class PreferencesUnavailableScreen extends StatelessWidget {
  const PreferencesUnavailableScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: context.hireCanvas,
      appBar: AppBar(title: const Text('Preferences')),
      body: const Padding(
        padding: EdgeInsets.all(16),
        child: Text(
          'Preferences and settings are not connected. This screen does not change alerts, privacy, or account security.',
        ),
      ),
    );
  }
}
