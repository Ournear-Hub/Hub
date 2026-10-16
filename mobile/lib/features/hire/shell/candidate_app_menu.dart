import 'package:flutter/material.dart';
import 'package:flutter_tabler_icons/flutter_tabler_icons.dart';
import 'package:ournear_mobile/core/app_version.dart';
import 'package:ournear_mobile/core/api/hire_api_service.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/providers/candidate_repository_resolver.dart';
import 'package:ournear_mobile/core/theme/candidate_theme_controller.dart';
import 'package:ournear_mobile/core/theme/ournear_colors.dart';
import 'package:ournear_mobile/features/hire/jobs/data/jobs_repository.dart';
import 'package:ournear_mobile/features/hire/jobs/saved_jobs_screen.dart';
import 'package:ournear_mobile/features/hire/profile/documents_screen.dart';
import 'package:ournear_mobile/features/hire/settings/candidate_settings_screen.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

typedef CandidateTabNavigator = void Function(String tabId);

class CandidateAppMenu extends StatelessWidget {
  const CandidateAppMenu({
    super.key,
    required this.onClose,
    required this.onNavigateTab,
    required this.onViewProfile,
  });

  final VoidCallback onClose;
  final CandidateTabNavigator onNavigateTab;
  final VoidCallback onViewProfile;

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthController>();
    final user = auth.status == AuthStatus.authenticated ? auth.user : null;
    CandidateThemeController? themeCtrl;
    try {
      themeCtrl = context.watch<CandidateThemeController>();
    } on ProviderNotFoundException {
      themeCtrl = null;
    }

    final scheme = Theme.of(context).colorScheme;
    return SafeArea(
      child: ListView(
        key: const Key('candidate-menu-list'),
        padding: const EdgeInsets.symmetric(vertical: 8),
        children: [
          UserAccountsDrawerHeader(
            accountName: Text(
              user?.name ?? 'Guest',
              style: TextStyle(color: scheme.onPrimary),
            ),
            accountEmail: Text(
              user != null ? '@${user.username}' : 'Sign in to sync account',
              style: TextStyle(color: scheme.onPrimary.withValues(alpha: 0.85)),
            ),
            currentAccountPicture: CircleAvatar(
              backgroundColor: scheme.onPrimary.withValues(alpha: 0.15),
              child: Icon(TablerIcons.user, color: scheme.onPrimary),
            ),
            decoration: BoxDecoration(color: scheme.primary),
          ),
          ListTile(
            key: const Key('menu-view-profile'),
            leading: const Icon(TablerIcons.user_circle),
            title: const Text('View Profile'),
            onTap: onViewProfile,
          ),
          const Divider(),
          _item(context, 'menu-saved-jobs', 'Saved Jobs', TablerIcons.bookmark, () {
            onClose();
            _openSavedJobs(context);
          }),
          _item(context, 'menu-applied-jobs', 'Applied Jobs', TablerIcons.file_description, () {
            onClose();
            onNavigateTab('applications');
          }),
          _item(context, 'menu-resumes', 'Resumes', TablerIcons.file_description, () {
            onClose();
            _openResumes(context);
          }),
          _item(context, 'menu-settings', 'Settings', TablerIcons.settings, () {
            onClose();
            Navigator.of(context).push(
              MaterialPageRoute<void>(
                builder: (_) => const CandidateSettingsScreen(),
              ),
            );
          }),
          if (themeCtrl != null)
            SwitchListTile(
              key: const Key('menu-dark-mode'),
              secondary: const Icon(TablerIcons.moon),
              title: const Text('Dark Mode'),
              value: themeCtrl.isDark,
              onChanged: (value) => themeCtrl!.setDark(value),
            ),
          const Divider(),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 4),
            child: Text(
              'OURNEAR APPS',
              style: Theme.of(context).textTheme.labelSmall,
            ),
          ),
          _item(context, 'menu-ournear', 'OurNear', TablerIcons.home_2, () {
            onClose();
            _showInfo(
              context,
              'OurNear',
              'The unified OurNear app (Home, Vibes, Drops, Hire, Profile) launches from the main app entry. '
              'This build is the OurNear Hire candidate preview.',
            );
          }),
          _item(context, 'menu-ournear-hire', 'OurNear Hire', TablerIcons.briefcase, () {
            onClose();
            onNavigateTab('home');
          }),
          const Divider(),
          _item(context, 'menu-about', 'About Us', TablerIcons.info_circle, () {
            onClose();
            _openSiteUrl(context, 'about_url', 'About Us');
          }),
          _item(context, 'menu-terms', 'Terms & Conditions', TablerIcons.file_text, () {
            onClose();
            _openSiteUrl(context, 'terms_url', 'Terms & Conditions');
          }),
          _item(context, 'menu-privacy', 'Privacy Policy', TablerIcons.shield_lock, () {
            onClose();
            _openSiteUrl(context, 'privacy_url', 'Privacy Policy');
          }),
          _item(context, 'menu-help', 'Help & Support', TablerIcons.lifebuoy, () {
            onClose();
            _openHelp(context);
          }),
          ListTile(
            key: const Key('menu-app-version'),
            leading: const Icon(TablerIcons.info_square),
            title: const Text('App Version'),
            subtitle: Text(AppVersion.label),
          ),
          const Divider(),
          ListTile(
            key: const Key('menu-delete-account'),
            leading: const Icon(TablerIcons.trash, color: OurNearColors.error),
            title: const Text('Delete Account'),
            onTap: () => _confirmDeleteAccount(context),
          ),
          ListTile(
            key: const Key('menu-logout'),
            leading: const Icon(TablerIcons.logout),
            title: const Text('Logout'),
            enabled: user != null && !auth.busy,
            onTap: user == null || auth.busy
                ? null
                : () async {
                    onClose();
                    await auth.logout();
                  },
          ),
        ],
      ),
    );
  }

  Widget _item(
    BuildContext context,
    String key,
    String label,
    IconData icon,
    VoidCallback onTap,
  ) {
    return ListTile(
      key: Key(key),
      leading: Icon(icon),
      title: Text(label),
      onTap: onTap,
    );
  }

  void _openSavedJobs(BuildContext context) {
    JobsRepository repository;
    try {
      repository = context.read<JobsRepository>();
    } on ProviderNotFoundException {
      repository = LiveJobsRepository(service: context.read<HireApiService>());
    }
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => SavedJobsScreen(repository: repository),
      ),
    );
  }

  void _openResumes(BuildContext context) {
    final repository = CandidateRepositoryResolver.documents(context);
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => DocumentsScreen(repository: repository),
      ),
    );
  }

  Future<void> _openSiteUrl(
    BuildContext context,
    String key,
    String title,
  ) async {
    final api = context.read<HireApiService>();
    if (!api.isConfigured) {
      _showInfo(
        context,
        title,
        'Configure OURNEAR_API_BASE to open the official OurNear page in your browser.',
      );
      return;
    }
    try {
      final contact = await api.fetchSiteContact();
      if (!context.mounted) {
        return;
      }
      final url = contact[key] as String?;
      if (url == null || url.trim().isEmpty) {
        _showInfo(context, title, 'This page is not configured on OurNear.');
        return;
      }
      final uri = Uri.parse(url);
      if (!await launchUrl(uri, mode: LaunchMode.externalApplication) &&
          context.mounted) {
        _showInfo(context, title, 'Could not open $url');
      }
    } catch (_) {
      if (context.mounted) {
        _showInfo(context, title, 'Could not load site links from OurNear.');
      }
    }
  }

  Future<void> _openHelp(BuildContext context) async {
    final api = context.read<HireApiService>();
    if (!api.isConfigured) {
      _showInfo(
        context,
        'Help & Support',
        'Configure OURNEAR_API_BASE to load support contact details from OurNear.',
      );
      return;
    }
    try {
      final contact = await api.fetchSiteContact();
      if (!context.mounted) {
        return;
      }
      final email = contact['enquiry_email'] as String?;
      final phone = contact['contact_phone'] as String?;
      final whatsapp = contact['whatsapp_number'] as String?;
      final address = contact['office_address'] as String?;
      final lines = <String>[
        if (email != null && email.isNotEmpty) 'Email: $email',
        if (phone != null && phone.isNotEmpty) 'Phone: $phone',
        if (whatsapp != null && whatsapp.isNotEmpty) 'WhatsApp: $whatsapp',
        if (address != null && address.isNotEmpty) 'Address: $address',
      ];
      _showInfo(
        context,
        'Help & Support',
        lines.isEmpty
            ? 'Support contact details are not published on OurNear.'
            : lines.join('\n'),
      );
    } catch (_) {
      if (context.mounted) {
        _showInfo(
          context,
          'Help & Support',
          'Could not load support contact details.',
        );
      }
    }
  }

  void _showInfo(BuildContext context, String title, String message) {
    showDialog<void>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(title),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Close'),
          ),
        ],
      ),
    );
  }

  Future<void> _confirmDeleteAccount(BuildContext context) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Delete Account'),
        content: const Text(
          'Account deletion is not connected to OurNear. '
          'No request will be sent and your account will not be deleted. '
          'Sign out instead if you want to clear this device session.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.of(context).pop(true),
            child: const Text('I understand'),
          ),
        ],
      ),
    );
    if (confirmed == true && context.mounted) {
      _showInfo(
        context,
        'Delete Account',
        'Account deletion cannot be completed from this app. Nothing was deleted.',
      );
    }
  }
}
