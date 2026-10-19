import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/providers/candidate_repository_resolver.dart';
import 'package:ournear_mobile/core/theme/hire_theme_context.dart';
import 'package:ournear_mobile/features/hire/profile/data/profile_repository.dart';
import 'package:ournear_mobile/features/hire/profile/models/candidate_profile.dart';
import 'package:ournear_mobile/features/hire/profile/profile_demo_banner.dart';
import 'package:provider/provider.dart';

/// Menu of separate edit forms. A save is confirmed only when [ProfileSnapshot.persisted] is true.
class EditProfileSectionScreen extends StatelessWidget {
  const EditProfileSectionScreen({
    super.key,
    this.profileRepository,
    this.snapshot,
    this.initialSection,
  });

  final ProfileRepository? profileRepository;
  final ProfileSnapshot? snapshot;
  final ProfileSection? initialSection;

  @override
  Widget build(BuildContext context) {
    final section = initialSection;
    if (section != null) {
      return _SectionForm(
        section: section,
        profileRepository: profileRepository,
        snapshot: snapshot,
      );
    }
    return Scaffold(
      backgroundColor: context.hireCanvas,
      appBar: AppBar(title: const Text('Edit profile')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (snapshot?.source == ProfileDataSource.mockDemo) ...[
            const ProfileDemoBanner(),
            const SizedBox(height: 12),
          ],
          const Text(
            'Each section is a separate form. Name and username stay on the signed-in account and are not edited here.',
          ),
          const SizedBox(height: 12),
          for (final section in ProfileSection.values)
            Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: OutlinedButton(
                style: OutlinedButton.styleFrom(
                  minimumSize: const Size.fromHeight(48),
                ),
                onPressed: () {
                  Navigator.of(context).push(
                    MaterialPageRoute<void>(
                      builder: (_) => _SectionForm(
                        section: section,
                        profileRepository: profileRepository,
                        snapshot: snapshot,
                      ),
                    ),
                  );
                },
                child: Text(_sectionMenuLabel(section)),
              ),
            ),
        ],
      ),
    );
  }

  static String _label(ProfileSection section) {
    return switch (section) {
      ProfileSection.personal => 'Personal information',
      ProfileSection.summary => 'Professional summary',
      ProfileSection.experience => 'Work experience',
      ProfileSection.education => 'Education',
      ProfileSection.skills => 'Skills',
      ProfileSection.qualifications => 'Qualifications',
      ProfileSection.certifications => 'Certifications',
      ProfileSection.jobPreferences => 'Job preferences',
    };
  }

  static String _sectionMenuLabel(ProfileSection section) => _label(section);
}

class _SectionForm extends StatefulWidget {
  const _SectionForm({
    required this.section,
    required this.profileRepository,
    required this.snapshot,
  });

  final ProfileSection section;
  final ProfileRepository? profileRepository;
  final ProfileSnapshot? snapshot;

  @override
  State<_SectionForm> createState() => _SectionFormState();
}

class _SectionFormState extends State<_SectionForm> {
  late final TextEditingController _primary;
  late final TextEditingController _secondary;
  var _saving = false;
  String? _message;
  var _persisted = false;
  var _savedInSession = false;
  int? _recordId;

  @override
  void initState() {
    super.initState();
    final profile = widget.snapshot?.profile;
    _recordId = _initialRecordId(profile);
    _primary = TextEditingController(text: _initialPrimary(profile));
    _secondary = TextEditingController(text: _initialSecondary(profile));
  }

  @override
  void dispose() {
    _primary.dispose();
    _secondary.dispose();
    super.dispose();
  }

  bool get _dirty =>
      _primary.text != _initialPrimary(widget.snapshot?.profile) ||
      _secondary.text != _initialSecondary(widget.snapshot?.profile);

  ProfileRepository _repository() {
    return CandidateRepositoryResolver.profile(
      context,
      injected: widget.profileRepository,
    );
  }

  Future<void> _save() async {
    if (_saving) {
      return;
    }
    setState(() {
      _saving = true;
      _message = null;
      _persisted = false;
    });
    try {
      final result = await _repository().save(widget.section, _draft());
      if (!mounted) {
        return;
      }
      setState(() {
        _persisted = result.persisted;
        _savedInSession = true;
        _message = result.persisted
            ? 'Saved on OurNear.'
            : 'Demo profile updated in this session. Nothing was saved on OurNear.';
      });
    } on ProfileRepositoryException catch (error) {
      if (!mounted) {
        return;
      }
      setState(() => _message = error.message);
    } finally {
      if (mounted) {
        setState(() => _saving = false);
      }
    }
  }

  int? _initialRecordId(CandidateProfile? profile) {
    return switch (widget.section) {
      ProfileSection.education => profile?.education.firstOrNull?.id,
      ProfileSection.certifications => profile?.certifications.firstOrNull?.id,
      ProfileSection.experience => profile?.experience.firstOrNull?.id,
      _ => null,
    };
  }

  List<({int? id, String label})> get _recordRows {
    final profile = widget.snapshot?.profile;
    return switch (widget.section) {
      ProfileSection.education => [
        for (final row in profile?.education ?? const <ProfileEducation>[])
          (
            id: row.id,
            label: '${row.credential}, ${row.institution}',
          ),
      ],
      ProfileSection.certifications => [
        for (final row in profile?.certifications ?? const <ProfileCertification>[])
          (
            id: row.id,
            label: row.issuer == null ? row.name : '${row.name}, ${row.issuer}',
          ),
      ],
      ProfileSection.experience => [
        for (final row in profile?.experience ?? const <ProfileExperience>[])
          (
            id: row.id,
            label: '${row.title}, ${row.organization}',
          ),
      ],
      _ => const [],
    };
  }

  void _loadRecord(int? id) {
    final profile = widget.snapshot?.profile;
    setState(() {
      _recordId = id;
      _message = id == null
          ? 'This entry has no saved id, so it cannot be updated.'
          : 'Editing the selected entry. Save sends an update.';
    });
    switch (widget.section) {
      case ProfileSection.education:
        final row = profile?.education.where((item) => item.id == id).toList().firstOrNull;
        if (row == null) {
          return;
        }
        _primary.text = row.credential;
        _secondary.text = row.institution;
      case ProfileSection.certifications:
        final row = profile?.certifications.where((item) => item.id == id).toList().firstOrNull;
        if (row == null) {
          return;
        }
        _primary.text = row.name;
        _secondary.text = row.issuer ?? '';
      case ProfileSection.experience:
        final row = profile?.experience.where((item) => item.id == id).toList().firstOrNull;
        if (row == null) {
          return;
        }
        _primary.text = row.title;
        _secondary.text = row.organization;
      default:
        break;
    }
  }

  Future<void> _deleteId(int id) async {
    if (_saving) {
      return;
    }
    setState(() {
      _saving = true;
      _message = null;
    });
    try {
      final result = await _repository().deleteRecord(widget.section, id);
      if (!mounted) {
        return;
      }
      setState(() {
        _persisted = result.persisted;
        _savedInSession = true;
        if (_recordId == id) {
          _recordId = null;
        }
        _message = 'Removed on OurNear.';
      });
    } on ProfileRepositoryException catch (error) {
      if (mounted) {
        setState(() => _message = error.message);
      }
    } finally {
      if (mounted) {
        setState(() => _saving = false);
      }
    }
  }

  Future<void> _deleteRecord() async {
    final id = _recordId;
    if (id == null || _saving) {
      return;
    }
    await _deleteId(id);
  }

  ProfileSectionDraft _draft() {
    final primary = _primary.text;
    final secondary = _secondary.text;
    return switch (widget.section) {
      ProfileSection.personal => ProfileSectionDraft(
        text: primary,
        secondaryText: secondary,
      ),
      ProfileSection.summary || ProfileSection.jobPreferences =>
        ProfileSectionDraft(text: primary),
      ProfileSection.skills => ProfileSectionDraft(skills: [primary]),
      ProfileSection.qualifications => ProfileSectionDraft(
        qualifications: [primary],
      ),
      ProfileSection.experience => ProfileSectionDraft(
        recordId: _recordId,
        experience: [
          ProfileExperience(
            id: _recordId,
            title: primary,
            organization: secondary,
          ),
        ],
      ),
      ProfileSection.education => ProfileSectionDraft(
        recordId: _recordId,
        education: [
          ProfileEducation(
            id: _recordId,
            credential: primary,
            institution: secondary,
          ),
        ],
      ),
      ProfileSection.certifications => ProfileSectionDraft(
        recordId: _recordId,
        certifications: [
          ProfileCertification(
            id: _recordId,
            name: primary,
            issuer: secondary,
          ),
        ],
      ),
    };
  }

  Future<void> _maybeLeave() async {
    if (!_dirty || _persisted || _savedInSession) {
      if (mounted) {
        Navigator.of(context).pop();
      }
      return;
    }
    final discard = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Discard unsaved changes?'),
          content: const Text('These edits have not been saved.'),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(false),
              child: const Text('Keep editing'),
            ),
            TextButton(
              onPressed: () => Navigator.of(context).pop(true),
              child: const Text('Discard'),
            ),
          ],
        );
      },
    );
    if (discard == true && mounted) {
      Navigator.of(context).pop();
    }
  }

  @override
  Widget build(BuildContext context) {
    final account = _accountLine();
    return PopScope(
      canPop: !_dirty || _persisted || _savedInSession,
      onPopInvokedWithResult: (didPop, result) {
        if (!didPop) {
          _maybeLeave();
        }
      },
      child: Scaffold(
        backgroundColor: context.hireCanvas,
        appBar: AppBar(
          title: Text(EditProfileSectionScreen._label(widget.section)),
        ),
        body: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            if (widget.section == ProfileSection.personal &&
                account != null) ...[
              Text(account),
              const SizedBox(height: 8),
              const Text('The account name is not edited in this form.'),
              const SizedBox(height: 12),
            ],
            TextField(
              key: const Key('profile-primary-field'),
              controller: _primary,
              decoration: InputDecoration(labelText: _primaryLabel()),
              onChanged: (_) => setState(() {}),
            ),
            if (_recordRows.isNotEmpty) ...[
              const SizedBox(height: 12),
              const Text('Saved entries'),
              for (final row in _recordRows)
                ListTile(
                  contentPadding: EdgeInsets.zero,
                  title: Text(row.label),
                  onTap: _saving ? null : () => _loadRecord(row.id),
                  trailing: row.id == null
                      ? null
                      : TextButton(
                          onPressed: _saving ? null : () => _deleteId(row.id!),
                          child: const Text('Delete'),
                        ),
                ),
            ],
            if (_usesSecondary) ...[
              const SizedBox(height: 12),
              TextField(
                key: const Key('profile-secondary-field'),
                controller: _secondary,
                decoration: InputDecoration(labelText: _secondaryLabel()),
                onChanged: (_) => setState(() {}),
              ),
            ],
            if (_message != null) ...[
              const SizedBox(height: 12),
              Text(_message!, key: const Key('profile-save-message')),
            ],
            const SizedBox(height: 16),
            if (_recordId != null &&
                (widget.section == ProfileSection.education ||
                    widget.section == ProfileSection.certifications ||
                    widget.section == ProfileSection.experience)) ...[
              OutlinedButton(
                onPressed: _saving ? null : _deleteRecord,
                child: const Text('Delete entry'),
              ),
              const SizedBox(height: 8),
            ],
            FilledButton(
              key: const Key('profile-save'),
              style: FilledButton.styleFrom(
                minimumSize: const Size.fromHeight(48),
              ),
              onPressed: _saving ? null : _save,
              child: Text(_saving ? 'Please wait' : 'Save'),
            ),
            const SizedBox(height: 8),
            OutlinedButton(
              key: const Key('profile-cancel'),
              style: OutlinedButton.styleFrom(
                minimumSize: const Size.fromHeight(48),
              ),
              onPressed: _saving ? null : _maybeLeave,
              child: const Text('Cancel'),
            ),
          ],
        ),
      ),
    );
  }

  bool get _usesSecondary =>
      widget.section == ProfileSection.personal ||
      widget.section == ProfileSection.experience ||
      widget.section == ProfileSection.education ||
      widget.section == ProfileSection.certifications;

  String _primaryLabel() {
    return switch (widget.section) {
      ProfileSection.personal => 'Public location',
      ProfileSection.summary => 'Summary',
      ProfileSection.experience => 'Role title',
      ProfileSection.education => 'Credential',
      ProfileSection.skills => 'Skill',
      ProfileSection.qualifications => 'Qualification',
      ProfileSection.certifications => 'Certification',
      ProfileSection.jobPreferences => 'Job preferences',
    };
  }

  String _secondaryLabel() {
    return switch (widget.section) {
      ProfileSection.personal => 'Professional headline',
      ProfileSection.experience => 'Organization',
      ProfileSection.education => 'Institution',
      ProfileSection.certifications => 'Issuer',
      _ => '',
    };
  }

  String _initialPrimary(CandidateProfile? profile) {
    return switch (widget.section) {
      ProfileSection.personal => profile?.publicLocation ?? '',
      ProfileSection.summary => profile?.summary ?? '',
      ProfileSection.jobPreferences => profile?.jobPreferences ?? '',
      ProfileSection.skills => profile?.skills.join(', ') ?? '',
      ProfileSection.qualifications => profile?.qualifications.join(', ') ?? '',
      ProfileSection.experience => _first(
        profile?.experience,
        (row) => row.title,
      ),
      ProfileSection.education => _first(
        profile?.education,
        (row) => row.credential,
      ),
      ProfileSection.certifications => _first(
        profile?.certifications,
        (row) => row.name,
      ),
    };
  }

  String _initialSecondary(CandidateProfile? profile) {
    return switch (widget.section) {
      ProfileSection.personal => profile?.headline ?? '',
      ProfileSection.experience => _first(
        profile?.experience,
        (row) => row.organization,
      ),
      ProfileSection.education => _first(
        profile?.education,
        (row) => row.institution,
      ),
      ProfileSection.certifications => _first(
        profile?.certifications,
        (row) => row.issuer ?? '',
      ),
      _ => '',
    };
  }

  String _first<T>(List<T>? rows, String Function(T row) read) {
    if (rows == null || rows.isEmpty) {
      return '';
    }
    return read(rows.first);
  }

  String? _accountLine() {
    try {
      final auth = context.read<AuthController>();
      final user = auth.user;
      if (user == null) {
        return null;
      }
      return 'Account: ${user.name} (@${user.username})';
    } on ProviderNotFoundException {
      return null;
    }
  }
}

extension<T> on List<T> {
  T? get firstOrNull => isEmpty ? null : first;
}

