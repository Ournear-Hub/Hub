import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/theme/hire_theme_context.dart';
import 'package:ournear_mobile/features/hire/profile/data/profile_repository.dart';
import 'package:ournear_mobile/features/hire/profile/edit_profile_section_screen.dart';
import 'package:ournear_mobile/features/hire/profile/models/candidate_profile.dart';
import 'package:ournear_mobile/features/hire/profile/profile_demo_banner.dart';
import 'package:provider/provider.dart';

class ProfessionalProfileScreen extends StatelessWidget {
  const ProfessionalProfileScreen({
    super.key,
    this.snapshot,
    this.failure,
    this.profileRepository,
  });

  final ProfileSnapshot? snapshot;
  final ProfileRepositoryException? failure;
  final ProfileRepository? profileRepository;

  @override
  Widget build(BuildContext context) {
    final profile = snapshot?.profile;
    final demo = snapshot?.source == ProfileDataSource.mockDemo;
    final signedIn = _signedIn(context);
    return Scaffold(
      backgroundColor: context.hireCanvas,
      appBar: AppBar(title: const Text('Professional profile')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (demo) ...[const ProfileDemoBanner(), const SizedBox(height: 12)],
          if (failure != null) ...[
            Text(failure!.message),
            const SizedBox(height: 12),
          ],
          const Text(
            'This view does not show private contact details or resume files. Recruiter visibility is not established by this app.',
          ),
          const SizedBox(height: 16),
          _section(
            context,
            'Professional summary',
            _text(profile?.summary, demo),
          ),
          _section(context, 'Skills', _list(profile?.skills, demo)),
          _section(context, 'Work experience', _experience(profile, demo)),
          _section(context, 'Education', _education(profile, demo)),
          _section(
            context,
            'Qualifications',
            _list(profile?.qualifications, demo),
          ),
          _section(context, 'Certifications', _certifications(profile, demo)),
          _section(
            context,
            'Location and job preferences',
            _preferences(profile, demo),
          ),
          if (signedIn) ...[
            const SizedBox(height: 16),
            FilledButton(
              style: FilledButton.styleFrom(
                minimumSize: const Size.fromHeight(48),
              ),
              onPressed: () {
                Navigator.of(context).push(
                  MaterialPageRoute<void>(
                    builder: (_) => EditProfileSectionScreen(
                      profileRepository: profileRepository,
                      snapshot: snapshot,
                    ),
                  ),
                );
              },
              child: const Text('Edit profile'),
            ),
          ],
        ],
      ),
    );
  }

  bool _signedIn(BuildContext context) {
    try {
      final auth = context.watch<AuthController>();
      return auth.status == AuthStatus.authenticated;
    } on ProviderNotFoundException {
      return false;
    }
  }

  Widget _section(BuildContext context, String title, String body) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: 4),
          Text(body),
        ],
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
    return 'This section is not available.';
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
    return rows
        .map((row) => '${row.credential}, ${row.institution}')
        .join('\n');
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
    final parts = [
      if (profile?.publicLocation != null &&
          profile!.publicLocation!.trim().isNotEmpty)
        profile.publicLocation!,
      if (profile?.jobPreferences != null &&
          profile!.jobPreferences!.trim().isNotEmpty)
        profile.jobPreferences!,
    ];
    if (parts.isEmpty) {
      return _text(null, demo);
    }
    return parts.join('\n');
  }
}
