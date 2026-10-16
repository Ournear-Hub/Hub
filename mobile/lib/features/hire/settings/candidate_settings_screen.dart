import 'package:flutter/material.dart';
import 'package:flutter_tabler_icons/flutter_tabler_icons.dart';
import 'package:ournear_mobile/core/widgets/platform_empty_state.dart';
import 'package:ournear_mobile/core/app_version.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/theme/hire_theme_context.dart';
import 'package:ournear_mobile/features/hire/settings/account_verification_screen.dart';
import 'package:ournear_mobile/features/auth/login_screen.dart';
import 'package:ournear_mobile/features/hire/profile/edit_profile_section_screen.dart';
import 'package:ournear_mobile/features/hire/profile/models/candidate_profile.dart';
import 'package:ournear_mobile/features/hire/profile/preferences_unavailable_screen.dart';
import 'package:ournear_mobile/features/hire/widgets/hire_settings_nav_tile.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';
import 'package:provider/provider.dart';

class CandidateSettingsScreen extends StatelessWidget {
  const CandidateSettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthController>();
    final user = auth.status == AuthStatus.authenticated ? auth.user : null;
    return Scaffold(
      backgroundColor: context.hireCanvas,
      appBar: AppBar(title: const Text('Settings')),
      body: ListView(
        padding: const EdgeInsets.all(OurNearTokens.pageHorizontal),
        children: [
          Text('Account', style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: 8),
          Card(
            color: context.hireCardSurface,
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: user == null
                  ? Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Sign in to view account settings. No account details are shown before sign-in.',
                        ),
                        const SizedBox(height: 12),
                        FilledButton(
                          onPressed: () {
                            Navigator.of(context).push(
                              MaterialPageRoute<void>(
                                builder: (_) => const LoginScreen(),
                              ),
                            );
                          },
                          child: const Text('Sign in'),
                        ),
                      ],
                    )
                  : Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [Text(user.name), Text('@${user.username}')],
                    ),
            ),
          ),
          const SizedBox(height: 8),
          HireSettingsNavTile(
            label: 'Verification',
            icon: TablerIcons.shield_check,
            destination: const AccountVerificationScreen(),
          ),
          const SizedBox(height: 16),
          Text(
            'Job and communication preferences',
            style: Theme.of(context).textTheme.titleMedium,
          ),
          const SizedBox(height: 8),
          const HireSettingsNavTile(
            label: 'Job preferences',
            destination: JobPreferencesSettingsScreen(),
          ),
          const HireSettingsNavTile(
            label: 'Job alerts',
            destination: PreferencesUnavailableScreen(),
          ),
          const HireSettingsNavTile(
            label: 'Notification preferences',
            destination: NotificationPreferencesScreen(),
          ),
          const SizedBox(height: 16),
          Text(
            'Privacy and account',
            style: Theme.of(context).textTheme.titleMedium,
          ),
          const SizedBox(height: 8),
          const HireSettingsNavTile(
            label: 'Privacy and account',
            destination: PrivacySettingsScreen(),
          ),
          const SizedBox(height: 16),
          Text(
            'Help and support',
            style: Theme.of(context).textTheme.titleMedium,
          ),
          const SizedBox(height: 8),
          const HireSettingsNavTile(
            label: 'Help and support',
            destination: UnavailableContentScreen(
              title: 'Help and support',
              message:
                  'Help and support content is not connected. No support service is named here.',
            ),
          ),
          const HireSettingsNavTile(
            label: 'Terms',
            destination: UnavailableContentScreen(
              title: 'Terms',
              message: 'Terms of use are not connected.',
            ),
          ),
          const HireSettingsNavTile(
            label: 'Privacy policy',
            destination: UnavailableContentScreen(
              title: 'Privacy policy',
              message: 'The privacy policy is not connected.',
            ),
          ),
          const SizedBox(height: 16),
          Text(
            'App information',
            style: Theme.of(context).textTheme.titleMedium,
          ),
          const SizedBox(height: 8),
          Card(
            color: context.hireCardSurface,
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Text('OurNear Hire ${AppVersion.label}'),
            ),
          ),
        ],
      ),
    );
  }
}

class JobPreferencesSettingsScreen extends StatelessWidget {
  const JobPreferencesSettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: context.hireCanvas,
      appBar: AppBar(title: const Text('Job preferences')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          const Text(
            'Job preferences use the candidate profile form. A save is recorded on OurNear only when that form reports it was saved. The live profile service is not connected.',
          ),
          const SizedBox(height: 12),
          FilledButton(
            key: const Key('open-job-preferences'),
            style: FilledButton.styleFrom(
              minimumSize: const Size.fromHeight(48),
            ),
            onPressed: () {
              Navigator.of(context).push(
                MaterialPageRoute<void>(
                  builder: (_) => const EditProfileSectionScreen(
                    initialSection: ProfileSection.jobPreferences,
                  ),
                ),
              );
            },
            child: const Text('Open job preferences'),
          ),
        ],
      ),
    );
  }
}

class NotificationPreferencesScreen extends StatelessWidget {
  const NotificationPreferencesScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: context.hireCanvas,
      appBar: AppBar(title: const Text('Notification preferences')),
      body: const Padding(
        padding: EdgeInsets.all(16),
        child: Text(
          'Notification preferences are not connected. No channels were changed.',
        ),
      ),
    );
  }
}

class PrivacySettingsScreen extends StatelessWidget {
  const PrivacySettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthController>();
    final signedIn =
        auth.status == AuthStatus.authenticated && auth.user != null;
    return Scaffold(
      backgroundColor: context.hireCanvas,
      appBar: AppBar(title: const Text('Privacy and account')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (signedIn) ...[
            Text(auth.user!.name),
            Text('@${auth.user!.username}'),
            const SizedBox(height: 12),
          ] else
            const Text(
              'Sign in to view account settings. No account details are shown before sign-in.',
            ),
          const SizedBox(height: 12),
          const Text('Password changes are not available.'),
          const SizedBox(height: 8),
          const Text('Account deletion is not available.'),
          const SizedBox(height: 16),
          if (signedIn)
            OutlinedButton(
              style: OutlinedButton.styleFrom(
                minimumSize: const Size.fromHeight(48),
              ),
              onPressed: auth.busy ? null : () => auth.logout(),
              child: const Text('Sign out'),
            ),
        ],
      ),
    );
  }
}

class UnavailableContentScreen extends StatelessWidget {
  const UnavailableContentScreen({
    super.key,
    required this.title,
    required this.message,
  });

  final String title;
  final String message;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: context.hireCanvas,
      appBar: AppBar(title: Text(title)),
      body: PlatformEmptyState(
        title: title,
        message: message,
      ),
    );
  }
}
