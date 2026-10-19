import 'package:flutter/material.dart';
import 'package:flutter_tabler_icons/flutter_tabler_icons.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/providers/candidate_repository_resolver.dart';
import 'package:ournear_mobile/core/theme/hire_theme_context.dart';
import 'package:ournear_mobile/features/auth/login_screen.dart';
import 'package:ournear_mobile/features/hire/profile/data/documents_repository.dart';
import 'package:ournear_mobile/features/hire/profile/data/profile_repository.dart';
import 'package:ournear_mobile/features/hire/profile/documents_screen.dart';
import 'package:ournear_mobile/features/hire/profile/edit_profile_section_screen.dart';
import 'package:ournear_mobile/features/hire/profile/models/candidate_profile.dart';
import 'package:ournear_mobile/features/hire/profile/profile_demo_banner.dart';
import 'package:ournear_mobile/features/hire/profile/profile_setup/profile_setup_progress.dart';
import 'package:provider/provider.dart';

/// Optional guided walkthrough over the existing profile section forms.
class GuidedProfileSetupScreen extends StatefulWidget {
  const GuidedProfileSetupScreen({
    super.key,
    this.profileRepository,
    this.documentsRepository,
  });

  final ProfileRepository? profileRepository;
  final DocumentsRepository? documentsRepository;

  @override
  State<GuidedProfileSetupScreen> createState() =>
      _GuidedProfileSetupScreenState();
}

class _GuidedProfileSetupScreenState extends State<GuidedProfileSetupScreen> {
  ProfileSnapshot? _snapshot;
  ProfileRepositoryException? _error;
  var _loading = false;
  var _requested = false;
  final _touchedSections = <ProfileSection>{};

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

  CandidateProfile get _profileForProgress =>
      _snapshot?.profile ?? CandidateProfile.empty;

  ProfileSetupProgress get _progress => ProfileSetupProgress(
    profile: _profileForProgress,
    touchedSections: _touchedSections,
  );

  Future<void> _openSection(ProfileSection section) async {
    setState(() => _touchedSections.add(section));
    await Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => EditProfileSectionScreen(
          profileRepository: _profiles(),
          snapshot: _snapshot,
          initialSection: section,
        ),
      ),
    );
    if (!mounted) {
      return;
    }
    final auth = _authOf(context, listen: false);
    if (auth?.status == AuthStatus.authenticated) {
      await _load();
    }
    if (!mounted) {
      return;
    }
    setState(() {
      if (_progress.hasLocalContent(section)) {
        _touchedSections.remove(section);
      }
    });
  }

  AuthController? _authOf(BuildContext context, {required bool listen}) {
    try {
      return listen
          ? context.watch<AuthController>()
          : context.read<AuthController>();
    } on ProviderNotFoundException {
      return null;
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = _authOf(context, listen: true);
    final signedIn =
        auth?.status == AuthStatus.authenticated && auth?.user != null;

    if (signedIn && !_requested) {
      _requested = true;
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) {
          _load();
        }
      });
    }

    return Scaffold(
      backgroundColor: context.hireCanvas,
      appBar: AppBar(
        title: const Text('Profile setup'),
        leading: BackButton(
          key: const Key('profile-setup-back'),
          onPressed: () => Navigator.of(context).maybePop(),
        ),
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          const Text(
            'This guided setup is optional. It reuses the same profile edit forms as the Profile tab. '
            'Progress is local setup progress only. Nothing here is saved on OurNear until a verified profile API exists.',
            key: Key('profile-setup-intro'),
          ),
          const SizedBox(height: 12),
          if (!signedIn) ...[
            Card(
              color: context.hireCardSurface,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Text(
                      'Sign in to edit sections',
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                    const SizedBox(height: 8),
                    const Text(
                      'Public job browsing does not require profile setup. Sign in when you want to walk through the forms.',
                    ),
                    const SizedBox(height: 12),
                    FilledButton.icon(
                      key: const Key('profile-setup-sign-in'),
                      onPressed: auth?.busy == true
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
          ],
          if (signedIn) ...[
            if (_loading)
              const CircularProgressIndicator(
                semanticsLabel: 'Loading profile for setup',
              ),
            if (_snapshot?.source == ProfileDataSource.mockDemo) ...[
              const ProfileDemoBanner(),
              const SizedBox(height: 12),
            ],
            if (_error != null) ...[
              Text(_error!.message, key: const Key('profile-setup-load-error')),
              const SizedBox(height: 8),
              const Text(
                'You can still open a section. Saving to OurNear remains unavailable on the live repository.',
              ),
              const SizedBox(height: 12),
            ],
          ],
          if (signedIn)
            Text(
              'Local setup progress: ${_progress.reviewedLocallyCount} of ${guidedProfileSetupSections.length} sections reviewed locally',
              key: const Key('profile-setup-progress-summary'),
              style: Theme.of(context).textTheme.titleSmall,
            ),
          if (signedIn) const SizedBox(height: 12),
          for (final section in guidedProfileSetupSections)
            _sectionTile(context, section, signedIn: signedIn),
          const SizedBox(height: 12),
          _documentsCard(context, signedIn: signedIn),
          const SizedBox(height: 12),
          OutlinedButton(
            key: const Key('profile-setup-finish'),
            style: OutlinedButton.styleFrom(
              minimumSize: const Size.fromHeight(48),
            ),
            onPressed: () => Navigator.of(context).maybePop(),
            child: const Text('Finish later'),
          ),
        ],
      ),
    );
  }

  Widget _sectionTile(
    BuildContext context,
    ProfileSection section, {
    required bool signedIn,
  }) {
    final status = _progress.statusFor(section);
    final label = ProfileSetupProgress.statusLabel(status);
    return Card(
      color: context.hireCardSurface,
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        key: Key('profile-setup-section-${section.name}'),
        title: Text(guidedProfileSetupTitle(section)),
        subtitle: Text(
          '${guidedProfileSetupSubtitle(section)}\nStatus: $label',
        ),
        isThreeLine: true,
        trailing: const Icon(Icons.chevron_right),
        enabled: signedIn,
        onTap: signedIn ? () => _openSection(section) : null,
      ),
    );
  }

  Widget _documentsCard(BuildContext context, {required bool signedIn}) {
    return Card(
      color: context.hireCardSurface,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Resume and documents',
              style: Theme.of(context).textTheme.titleMedium,
            ),
            const SizedBox(height: 8),
            const Text(
              'Hire Resume is intended for a system-generated resume from canonical profile data. '
              'My Resume is for an uploaded file. Live generation and upload are not connected.',
            ),
            const SizedBox(height: 12),
            OutlinedButton.icon(
              key: const Key('profile-setup-documents'),
              style: OutlinedButton.styleFrom(
                minimumSize: const Size.fromHeight(48),
              ),
              onPressed: signedIn
                  ? () {
                      Navigator.of(context).push(
                        MaterialPageRoute<void>(
                          builder: (_) => DocumentsScreen(
                            repository: _documents(),
                          ),
                        ),
                      );
                    }
                  : null,
              icon: const Icon(TablerIcons.file_description),
              label: const Text('Open resume and documents'),
            ),
          ],
        ),
      ),
    );
  }
}
