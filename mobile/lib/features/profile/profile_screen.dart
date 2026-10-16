import 'package:flutter/material.dart';
import 'package:flutter_tabler_icons/flutter_tabler_icons.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/features/auth/login_screen.dart';
import 'package:provider/provider.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key, this.showMemoryShortcut = true});

  /// Unified and employer shells keep the Memory row. The candidate shell passes false.
  final bool showMemoryShortcut;

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthController>();
    final primary = Theme.of(context).colorScheme.primary;

    return Scaffold(
      appBar: AppBar(title: const Text('Profile')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (auth.status != AuthStatus.authenticated) ...[
            Card(
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
                    Text(
                      'Public job browsing works without an account. Sign in to access protected account features.',
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                    const SizedBox(height: 16),
                    FilledButton.icon(
                      onPressed: auth.busy
                          ? null
                          : () {
                              Navigator.of(context).push(
                                MaterialPageRoute<bool>(
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
          ] else ...[
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    CircleAvatar(
                      radius: 28,
                      backgroundColor: primary.withValues(alpha: 0.15),
                      child: Icon(TablerIcons.user, color: primary),
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            auth.user?.name ?? 'Account',
                            style: Theme.of(context).textTheme.titleMedium,
                          ),
                          Text(
                            '@${auth.user?.username ?? ''}',
                            style: Theme.of(context).textTheme.bodySmall,
                          ),
                          Text(
                            auth.user?.role ?? '',
                            style: Theme.of(context).textTheme.bodySmall,
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 12),
            OutlinedButton.icon(
              onPressed: auth.busy ? null : () => auth.logout(),
              icon: const Icon(TablerIcons.logout),
              label: const Text('Sign out'),
            ),
          ],
          const SizedBox(height: 12),
          _SettingsTile(
            icon: TablerIcons.bell,
            title: 'Notifications',
            subtitle: 'Delivery requires a future notifications API.',
            onTap: () => _showPlanned(context, 'Notifications'),
          ),
          if (showMemoryShortcut)
            _SettingsTile(
              icon: TablerIcons.photo,
              title: 'Memory',
              subtitle: 'Module shortcut until Memory gets its own tab.',
              onTap: () => _showPlanned(context, 'Memory'),
            ),
        ],
      ),
    );
  }

  void _showPlanned(BuildContext context, String feature) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          '$feature will connect to OurNear Laravel in a later phase.',
        ),
      ),
    );
  }
}

class _SettingsTile extends StatelessWidget {
  const _SettingsTile({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.onTap,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: ListTile(
        leading: Icon(icon, color: Theme.of(context).colorScheme.primary),
        title: Text(title),
        subtitle: Text(subtitle),
        trailing: const Icon(TablerIcons.chevron_right, size: 18),
        onTap: onTap,
      ),
    );
  }
}
