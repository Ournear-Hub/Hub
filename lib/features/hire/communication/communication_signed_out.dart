import 'package:flutter/material.dart';
import 'package:flutter_tabler_icons/flutter_tabler_icons.dart';
import 'package:ournear_mobile/core/theme/hire_theme_context.dart';
import 'package:ournear_mobile/features/auth/login_screen.dart';

class CommunicationSignedOut extends StatelessWidget {
  const CommunicationSignedOut({super.key, required this.detail});

  final String detail;

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Card(
          color: context.hireCardSurface,
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Sign in to OurNear',
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                const SizedBox(height: 8),
                Text(detail),
                const SizedBox(height: 16),
                FilledButton.icon(
                  onPressed: () {
                    Navigator.of(context).push(
                      MaterialPageRoute<void>(
                        builder: (_) => const LoginScreen(),
                      ),
                    );
                  },
                  icon: const Icon(TablerIcons.login),
                  label: const Text('Sign in'),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}
