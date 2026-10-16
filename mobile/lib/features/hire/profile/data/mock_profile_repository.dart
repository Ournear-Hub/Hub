import 'package:ournear_mobile/features/hire/profile/data/profile_repository.dart';
import 'package:ournear_mobile/features/hire/profile/models/candidate_profile.dart';

/// In-memory professional profile for tests. Writes are not server persistence.
class MockProfileRepository implements ProfileRepository {
  MockProfileRepository({
    CandidateProfile? profile,
    this.failure,
    this.artificialDelay = Duration.zero,
  }) : _profile = profile ?? CandidateProfile.empty;

  CandidateProfile _profile;
  final ProfileRepositoryException? failure;
  final Duration artificialDelay;

  Future<void> _pause() async {
    if (artificialDelay > Duration.zero) {
      await Future<void>.delayed(artificialDelay);
    }
    final error = failure;
    if (error != null) {
      throw error;
    }
  }

  ProfileSnapshot _snapshot() {
    return ProfileSnapshot(
      profile: _profile,
      source: ProfileDataSource.mockDemo,
      persisted: false,
    );
  }

  @override
  Future<ProfileSnapshot> fetchProfile() async {
    await _pause();
    return _snapshot();
  }

  @override
  Future<ProfileSnapshot> save(
    ProfileSection section,
    ProfileSectionDraft draft,
  ) async {
    await _pause();
    switch (section) {
      case ProfileSection.personal:
        _profile = _profile.copyWith(
          publicLocation: _requiredText(draft.text, 'location'),
        );
      case ProfileSection.summary:
        _profile = _profile.copyWith(
          summary: _requiredText(draft.text, 'summary'),
        );
      case ProfileSection.jobPreferences:
        _profile = _profile.copyWith(
          jobPreferences: _requiredText(draft.text, 'job preferences'),
        );
      case ProfileSection.skills:
        _profile = _profile.copyWith(skills: _words(draft.skills, 'skill'));
      case ProfileSection.qualifications:
        _profile = _profile.copyWith(
          qualifications: _words(draft.qualifications, 'qualification'),
        );
      case ProfileSection.experience:
        _profile = _profile.copyWith(experience: _experience(draft.experience));
      case ProfileSection.education:
        _profile = _profile.copyWith(education: _education(draft.education));
      case ProfileSection.certifications:
        _profile = _profile.copyWith(
          certifications: _certifications(draft.certifications),
        );
    }
    return _snapshot();
  }

  String _requiredText(String? value, String label) {
    final trimmed = value?.trim() ?? '';
    if (trimmed.isEmpty) {
      throw ProfileRepositoryException.validation(
        'Enter a $label or cancel. This check is local to the demo profile and is not a server rule.',
      );
    }
    return trimmed;
  }

  List<String> _words(List<String> values, String label) {
    final cleaned = [
      for (final value in values)
        if (value.trim().isNotEmpty) value.trim(),
    ];
    if (cleaned.isEmpty) {
      throw ProfileRepositoryException.validation(
        'Enter a $label or cancel. This check is local to the demo profile and is not a server rule.',
      );
    }
    return cleaned;
  }

  List<ProfileExperience> _experience(List<ProfileExperience> values) {
    if (values.isEmpty) {
      throw ProfileRepositoryException.validation(
        'Enter a role title and organization or cancel. This check is local to the demo profile and is not a server rule.',
      );
    }
    return [
      for (final row in values)
        ProfileExperience(
          title: _requiredText(row.title, 'role title'),
          organization: _requiredText(row.organization, 'organization'),
          period: row.period?.trim().isEmpty ?? true
              ? null
              : row.period!.trim(),
        ),
    ];
  }

  List<ProfileEducation> _education(List<ProfileEducation> values) {
    if (values.isEmpty) {
      throw ProfileRepositoryException.validation(
        'Enter a credential and institution or cancel. This check is local to the demo profile and is not a server rule.',
      );
    }
    return [
      for (final row in values)
        ProfileEducation(
          credential: _requiredText(row.credential, 'credential'),
          institution: _requiredText(row.institution, 'institution'),
        ),
    ];
  }

  List<ProfileCertification> _certifications(
    List<ProfileCertification> values,
  ) {
    if (values.isEmpty) {
      throw ProfileRepositoryException.validation(
        'Enter a certification or cancel. This check is local to the demo profile and is not a server rule.',
      );
    }
    return [
      for (final row in values)
        ProfileCertification(
          name: _requiredText(row.name, 'certification'),
          issuer: row.issuer?.trim().isEmpty ?? true
              ? null
              : row.issuer!.trim(),
        ),
    ];
  }

  @override
  Future<ProfileSnapshot> deleteRecord(
    ProfileSection section,
    int recordId,
  ) async {
    await _pause();
    return _snapshot();
  }

  @override
  Future<ProfileSnapshot> uploadProfilePhoto({
    required List<int> bytes,
    required String filename,
  }) async {
    await _pause();
    if (bytes.isEmpty) {
      throw ProfileRepositoryException.validation('Choose a photo to upload.');
    }
    _profile = _profile.copyWith(photoUrl: 'demo://profile-photo');
    return ProfileSnapshot(
      profile: _profile,
      source: ProfileDataSource.mockDemo,
      persisted: false,
    );
  }
}
