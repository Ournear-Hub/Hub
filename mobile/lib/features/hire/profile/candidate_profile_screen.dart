import 'package:flutter/material.dart';
import 'package:flutter_tabler_icons/flutter_tabler_icons.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/providers/candidate_repository_resolver.dart';
import 'package:ournear_mobile/core/theme/hire_theme_context.dart';
import 'package:ournear_mobile/core/theme/ournear_colors.dart';
import 'package:ournear_mobile/features/auth/login_screen.dart';
import 'package:ournear_mobile/features/hire/communication/communication_routes.dart';
import 'package:ournear_mobile/features/hire/settings/candidate_settings_screen.dart';
import 'package:ournear_mobile/features/hire/profile/data/documents_repository.dart';
import 'package:ournear_mobile/features/hire/profile/data/profile_repository.dart';
import 'package:ournear_mobile/features/hire/profile/documents_screen.dart';
import 'package:ournear_mobile/features/hire/profile/models/candidate_profile.dart';
import 'package:ournear_mobile/features/hire/profile/edit_profile_section_screen.dart';
import 'package:ournear_mobile/features/hire/profile/professional_profile_screen.dart';
import 'package:ournear_mobile/features/hire/profile/profile_demo_banner.dart';
import 'package:ournear_mobile/features/hire/profile/profile_setup/guided_profile_setup_screen.dart';
import 'package:ournear_mobile/features/hire/profile/profile_setup/profile_setup_entry_request.dart';
import 'package:provider/provider.dart';

/// Candidate Profile tab. Account identity comes from [AuthController].
class CandidateProfileScreen extends StatefulWidget {
  const CandidateProfileScreen({
    super.key,
    this.profileRepository,
    this.documentsRepository,
  });

  final ProfileRepository? profileRepository;
  final DocumentsRepository? documentsRepository;

  @override
  State<CandidateProfileScreen> createState() => _CandidateProfileScreenState();
}

class _CandidateProfileScreenState extends State<CandidateProfileScreen> {
  ProfileSnapshot? _snapshot;
  ProfileRepositoryException? _error;
  var _loading = false;
  var _requested = false;
  var _checkedSetupEntry = false;

  ProfileRepository _profiles() {
    return CandidateRepositoryResolver.profile(
      context,
      injected: widget.profileRepository,
    );
  }

  DocumentsRepository _documents() {
    return CandidateRepositoryResolver.documents(
      context,
      injected: widget.documentsRepository,
    );
  }

  void _openGuidedSetup() {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => GuidedProfileSetupScreen(
          profileRepository: widget.profileRepository ?? _profiles(),
          documentsRepository: widget.documentsRepository ?? _documents(),
        ),
      ),
    );
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final snapshot = await _profiles().fetchProfile();
      if (!mounted) {
        return;
      }
      setState(() {
        _snapshot = snapshot;
        _loading = false;
      });
    } on ProfileRepositoryException catch (error) {
      if (!mounted) {
        return;
      }
      setState(() {
        _snapshot = null;
        _error = error;
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthController>();
    final signedIn =
        auth.status == AuthStatus.authenticated && auth.user != null;
    if (!signedIn) {
      _requested = false;
    } else if (!_requested) {
      _requested = true;
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) {
          _load();
        }
      });
    }

    if (!_checkedSetupEntry && ProfileSetupEntryRequest.consume()) {
      _checkedSetupEntry = true;
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) {
          _openGuidedSetup();
        }
      });
    }

    return Scaffold(
      backgroundColor: context.hireCanvas,
      appBar: AppBar(title: const Text('Profile')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (!signedIn)
            ..._signedOut(context, auth)
          else
            ..._signedIn(context, auth),
          const SizedBox(height: 12),
          _entry(
            context,
            icon: TablerIcons.bell,
            title: 'Notifications',
            subtitle: 'Opens notifications. Delivery is not connected.',
            onTap: () => openCandidateNotifications(context),
          ),
        ],
      ),
    );
  }

  List<Widget> _signedOut(BuildContext context, AuthController auth) {
    return [
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
              const Text(
                'Public job browsing works without an account. Sign in to access protected account features.',
              ),
              const SizedBox(height: 16),
              FilledButton.icon(
                onPressed: auth.busy
                    ? null
                    : () {
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
      const SizedBox(height: 12),
      OutlinedButton.icon(
        key: const Key('profile-guided-setup-signed-out'),
        style: OutlinedButton.styleFrom(
          minimumSize: const Size.fromHeight(48),
        ),
        onPressed: _openGuidedSetup,
        icon: const Icon(TablerIcons.adjustments),
        label: const Text('Set up your profile'),
      ),
    ];
  }

  List<Widget> _signedIn(BuildContext context, AuthController auth) {
    final user = auth.user!;
    final snapshot = _snapshot;
    return [
      Text(
        'Your account',
        key: const Key('profile-section-account'),
        style: Theme.of(context).textTheme.titleMedium,
      ),
      const SizedBox(height: 4),
      const Text(
        'Name and username come from your signed-in OurNear account. They are separate from your professional candidate profile.',
      ),
      const SizedBox(height: 12),
      Container(
        height: 72,
        decoration: BoxDecoration(
          color: OurNearColors.hireTeal,
          borderRadius: BorderRadius.circular(12),
        ),
      ),
      Transform.translate(
        offset: const Offset(0, -28),
        child: Card(
          color: context.hireCardSurface,
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                CircleAvatar(
                  radius: 28,
                  backgroundColor: OurNearColors.hireTeal.withValues(
                    alpha: 0.12,
                  ),
                  child: const Icon(
                    TablerIcons.user,
                    color: OurNearColors.hireTeal,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  snapshot?.profile.photoUrl != null
                      ? 'Profile photo is loaded from OurNear when available.'
                      : 'Profile photo upload is not available in the mobile app. '
                          'Add or change your photo on the OurNear Hire website.',
                ),
                const SizedBox(height: 8),
                Text(user.name, style: Theme.of(context).textTheme.titleLarge),
                Text('@${user.username}'),
                const SizedBox(height: 8),
                Text(_headline(snapshot)),
                if (_filled(snapshot?.profile.publicLocation)) ...[
                  const SizedBox(height: 4),
                  Text(snapshot!.profile.publicLocation!),
                ],
              ],
            ),
          ),
        ),
      ),
      Text(
        'Professional profile',
        key: const Key('profile-section-professional'),
        style: Theme.of(context).textTheme.titleMedium,
      ),
      const SizedBox(height: 4),
      const Text(
        'Professional fields are not the same as your account record. Live saves to OurNear are not connected. Demo edits stay in this session only.',
      ),
      const SizedBox(height: 8),
      const Text(
        'Local setup progress is shown in guided profile setup. It is not server-confirmed completion.',
      ),
      const SizedBox(height: 12),
      OutlinedButton.icon(
        key: const Key('profile-guided-setup'),
        style: OutlinedButton.styleFrom(
          minimumSize: const Size.fromHeight(48),
        ),
        onPressed: _openGuidedSetup,
        icon: const Icon(TablerIcons.list_check),
        label: const Text('Complete your professional profile'),
      ),
      const SizedBox(height: 12),
      if (_loading)
        const CircularProgressIndicator(semanticsLabel: 'Loading profile'),
      if (snapshot?.source == ProfileDataSource.mockDemo) ...[
        const ProfileDemoBanner(),
        const SizedBox(height: 12),
        _summaryBlock(context, snapshot!.profile),
      ],
      if (_error != null) Text(_error!.message),
      const SizedBox(height: 12),
      _action(context, 'View professional profile', () {
        Navigator.of(context).push(
          MaterialPageRoute<void>(
            builder: (_) => ProfessionalProfileScreen(
              snapshot: _snapshot,
              failure: _error,
              profileRepository: _profiles(),
            ),
          ),
        );
      }),
      _action(context, 'Edit profile', () async {
        await Navigator.of(context).push(
          MaterialPageRoute<void>(
            builder: (_) => EditProfileSectionScreen(
              profileRepository: _profiles(),
              snapshot: _snapshot,
            ),
          ),
        );
        if (mounted && auth.status == AuthStatus.authenticated) {
          await _load();
        }
      }),
      _action(context, 'Resume and documents', () {
        Navigator.of(context).push(
          MaterialPageRoute<void>(
            builder: (_) => DocumentsScreen(repository: _documents()),
          ),
        );
      }),
      _action(context, 'Settings', () {
        Navigator.of(context).push(
          MaterialPageRoute<void>(
            builder: (_) => const CandidateSettingsScreen(),
          ),
        );
      }),
      const SizedBox(height: 8),
      OutlinedButton.icon(
        onPressed: auth.busy ? null : () => auth.logout(),
        icon: const Icon(TablerIcons.logout),
        label: const Text('Sign out'),
      ),
    ];
  }

  String _headline(ProfileSnapshot? snapshot) {
    final headline = snapshot?.profile.headline;
    if (headline != null && headline.trim().isNotEmpty) {
      return headline;
    }
    if (snapshot?.source == ProfileDataSource.mockDemo) {
      return 'No headline was provided.';
    }
    return 'No professional headline is available.';
  }

  Widget _summaryBlock(BuildContext context, CandidateProfile profile) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('About', style: Theme.of(context).textTheme.titleMedium),
        const SizedBox(height: 4),
        Text(_orEmpty(profile.summary, 'No summary was provided.')),
        const SizedBox(height: 8),
        Text('Skills', style: Theme.of(context).textTheme.titleMedium),
        const SizedBox(height: 4),
        Text(
          profile.skills.isEmpty
              ? 'No skills were provided.'
              : profile.skills.join(', '),
        ),
      ],
    );
  }

  Widget _action(BuildContext context, String label, VoidCallback onPressed) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: FilledButton(
        key: Key('profile-action-$label'),
        style: FilledButton.styleFrom(
          minimumSize: const Size.fromHeight(48),
          backgroundColor: OurNearColors.hireTeal,
        ),
        onPressed: onPressed,
        child: Text(label),
      ),
    );
  }

  Widget _entry(
    BuildContext context, {
    required IconData icon,
    required String title,
    required String subtitle,
    required VoidCallback onTap,
  }) {
    return Card(
      color: context.hireCardSurface,
      child: ListTile(
        leading: Icon(icon, color: OurNearColors.hireTeal),
        title: Text(title),
        subtitle: Text(subtitle),
        onTap: onTap,
      ),
    );
  }

  bool _filled(String? value) => value != null && value.trim().isNotEmpty;

  String _orEmpty(String? value, String fallback) {
    if (value == null || value.trim().isEmpty) {
      return fallback;
    }
    return value;
  }
}
