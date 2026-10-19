import 'package:flutter/material.dart';
import 'package:flutter_tabler_icons/flutter_tabler_icons.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/theme/hire_theme_context.dart';
import 'package:ournear_mobile/features/hire/widgets/hire_profile_avatar.dart';
import 'package:ournear_mobile/features/hire/profile/data/profile_repository.dart';
import 'package:ournear_mobile/features/hire/profile/models/candidate_profile.dart';
import 'package:ournear_mobile/features/hire/profile/profile_demo_banner.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';
import 'package:ournear_mobile/features/hire/profile/widgets/profile_photo_flow.dart';
import 'package:ournear_mobile/features/hire/profile/widgets/profile_section_card.dart';
import 'package:provider/provider.dart';

/// Read-only professional profile sections shared by the Profile tab and pushed routes.
class ProfessionalProfileBody extends StatelessWidget {
  const ProfessionalProfileBody({
    super.key,
    required this.snapshot,
    this.failure,
    this.onEditProfile,
    this.profileRepository,
    this.onProfileUpdated,
  });

  final ProfileSnapshot? snapshot;
  final ProfileRepositoryException? failure;
  final VoidCallback? onEditProfile;
  final ProfileRepository? profileRepository;
  final VoidCallback? onProfileUpdated;

  @override
  Widget build(BuildContext context) {
    final profile = snapshot?.profile;
    final demo = snapshot?.source == ProfileDataSource.mockDemo;
    final auth = _auth(context);
    final signedIn = auth?.status == AuthStatus.authenticated && auth?.user != null;

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        if (demo) ...[const ProfileDemoBanner(), const SizedBox(height: 12)],
        if (failure != null) ...[
          Text(failure!.message, key: const Key('profile-load-error')),
          const SizedBox(height: 12),
        ],
        _header(context, auth, profile, demo, signedIn),
        const SizedBox(height: 16),
        if (snapshot?.source != ProfileDataSource.liveApi)
          const Text(
            'Professional information is separate from your signed-in account. '
            'Configure OURNEAR_API_BASE and sign in to load profile data from OurNear.',
          ),
        const SizedBox(height: 16),
        _sectionCard(context, 'Professional summary', _text(profile?.summary, demo)),
        _sectionCard(context, 'Work experience', _experience(profile, demo)),
        _sectionCard(context, 'Education', _education(profile, demo)),
        _sectionCard(context, 'Qualifications', _list(profile?.qualifications, demo)),
        _sectionCard(context, 'Skills', _list(profile?.skills, demo)),
        _sectionCard(context, 'Certifications', _certifications(profile, demo)),
        _sectionCard(context, 'Job preferences', _preferences(profile, demo)),
        if (signedIn && onEditProfile != null) ...[
          const SizedBox(height: 8),
          FilledButton(
            key: const Key('profile-edit-action'),
            style: FilledButton.styleFrom(
              minimumSize: const Size.fromHeight(48),
            ),
            onPressed: onEditProfile,
            child: const Text('Edit Profile'),
          ),
        ],
        if (!signedIn) ...[
          const SizedBox(height: 12),
          const Text(
            'Sign in to edit your professional profile. Public job browsing does not require an account.',
          ),
        ],
      ],
    );
  }

  AuthController? _auth(BuildContext context) {
    try {
      return context.watch<AuthController>();
    } on ProviderNotFoundException {
      return null;
    }
  }

  Widget _header(
    BuildContext context,
    AuthController? auth,
    CandidateProfile? profile,
    bool demo,
    bool signedIn,
  ) {
    final canChangePhoto = signedIn &&
        !demo &&
        snapshot?.source == ProfileDataSource.liveApi &&
        profileRepository != null &&
        onProfileUpdated != null;
    final user = auth?.user;
    final headline = profile?.headline;
    final location = profile?.publicLocation;
    return Card(
      color: context.hireCardSurface,
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsets.all(OurNearTokens.space16),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            HireProfileAvatar(imageUrl: profile?.photoUrl),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    user?.name ?? 'Candidate',
                    style: Theme.of(context).textTheme.titleLarge,
                  ),
                  if (user != null) Text('@${user.username}'),
                  if (user?.profileCompletion != null) ...[
                    const SizedBox(height: 4),
                    Text(
                      'Profile completion ${user!.profileCompletion}%',
                      key: const Key('profile-completion'),
                    ),
                  ],
                  const SizedBox(height: 4),
                  Text(
                    _headlineText(headline, demo),
                    key: const Key('profile-headline'),
                  ),
                  if (location != null && location.trim().isNotEmpty) ...[
                    const SizedBox(height: 4),
                    Text(location, key: const Key('profile-location')),
                  ] else if (!demo) ...[
                    const SizedBox(height: 4),
                    const Text('Location is not available.'),
                  ],
                  if (canChangePhoto) ...[
                    const SizedBox(height: OurNearTokens.space4),
                    TextButton.icon(
                      key: const Key('profile-change-photo'),
                      onPressed: () => ProfilePhotoFlow.start(
                        context,
                        repository: profileRepository!,
                        onUploaded: onProfileUpdated!,
                      ),
                      icon: Icon(TablerIcons.camera, size: 18),
                      label: const Text('Change profile photo'),
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _headlineText(String? headline, bool demo) {
    if (headline != null && headline.trim().isNotEmpty) {
      return headline;
    }
    if (demo) {
      return 'No headline was provided.';
    }
    return 'No professional headline is available.';
  }

  Widget _sectionCard(BuildContext context, String title, String body) {
    return ProfileSectionCard(
      title: title,
      body: body,
      sectionKey: Key(
        'profile-section-${title.toLowerCase().replaceAll(' ', '-')}',
      ),
    );
  }

  String _text(String? value, bool demo) {
    if (value != null && value.trim().isNotEmpty) {
      return value;
    }
    if (demo) {
      return 'Nothing was provided in this demo profile.';
    }
    return 'Not available.';
  }

  String _list(List<String>? values, bool demo) {
    if (values != null && values.isNotEmpty) {
      return values.join(', ');
    }
    return _text(null, demo);
  }

  String _experience(CandidateProfile? profile, bool demo) {
    final rows = profile?.experience ?? const <ProfileExperience>[];
    if (rows.isEmpty) {
      return _text(null, demo);
    }
    return rows
        .map((row) {
          final period = row.period == null ? '' : ' (${row.period})';
          return '${row.title}, ${row.organization}$period';
        })
        .join('\n');
  }

  String _education(CandidateProfile? profile, bool demo) {
    final rows = profile?.education ?? const <ProfileEducation>[];
    if (rows.isEmpty) {
      return _text(null, demo);
    }
    return rows.map((row) => '${row.credential}, ${row.institution}').join('\n');
  }

  String _certifications(CandidateProfile? profile, bool demo) {
    final rows = profile?.certifications ?? const <ProfileCertification>[];
    if (rows.isEmpty) {
      return _text(null, demo);
    }
    return rows
        .map(
          (row) => row.issuer == null ? row.name : '${row.name}, ${row.issuer}',
        )
        .join('\n');
  }

  String _preferences(CandidateProfile? profile, bool demo) {
    final prefs = profile?.jobPreferences;
    if (prefs != null && prefs.trim().isNotEmpty) {
      return prefs;
    }
    return _text(null, demo);
  }
}
