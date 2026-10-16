import 'dart:async';

import 'package:http/http.dart' as http;
import 'package:ournear_mobile/core/api/hire_api_exception.dart';
import 'package:ournear_mobile/core/api/hire_api_service.dart';
import 'package:ournear_mobile/features/hire/profile/data/profile_api_mapper.dart';
import 'package:ournear_mobile/features/hire/profile/models/candidate_profile.dart';

enum ProfileDataSource { liveApi, mockDemo }

enum ProfileFailureKind {
  offline,
  unavailable,
  unauthenticated,
  validation,
  sessionExpired,
  unknown,
}

class ProfileRepositoryException implements Exception {
  const ProfileRepositoryException(this.kind, this.message);

  final ProfileFailureKind kind;
  final String message;

  factory ProfileRepositoryException.unavailable([String? message]) {
    return ProfileRepositoryException(
      ProfileFailureKind.unavailable,
      message ?? 'The professional profile is not available.',
    );
  }

  factory ProfileRepositoryException.validation(String message) {
    return ProfileRepositoryException(ProfileFailureKind.validation, message);
  }

  factory ProfileRepositoryException.unauthenticated([String? message]) {
    return ProfileRepositoryException(
      ProfileFailureKind.unauthenticated,
      message ?? 'Sign in before opening the professional profile.',
    );
  }

  @override
  String toString() => message;
}

class ProfileSnapshot {
  const ProfileSnapshot({
    required this.profile,
    required this.source,
    required this.persisted,
  });

  final CandidateProfile profile;
  final ProfileDataSource source;

  /// True only after a verified profile endpoint confirms the write.
  final bool persisted;
}

abstract class ProfileRepository {
  Future<ProfileSnapshot> fetchProfile();

  Future<ProfileSnapshot> save(
    ProfileSection section,
    ProfileSectionDraft draft,
  );

  Future<ProfileSnapshot> deleteRecord(ProfileSection section, int recordId);

  Future<ProfileSnapshot> uploadProfilePhoto({
    required List<int> bytes,
    required String filename,
  });
}

class LiveProfileRepository implements ProfileRepository {
  LiveProfileRepository({HireApiService? api}) : _api = api ?? HireApiService();

  final HireApiService _api;

  static const readUnavailable =
      'The professional profile is not connected. Sign in and configure OURNEAR_API_BASE.';

  static const writeUnavailable =
      'Profile changes are not connected. Nothing was saved.';

  static const maxProfilePhotoBytes = 5120 * 1024;

  static const allowedProfilePhotoExtensions = {
    'jpg',
    'jpeg',
    'png',
    'webp',
  };

  @override
  Future<ProfileSnapshot> fetchProfile() async {
    if (!_api.isConfigured) {
      throw ProfileRepositoryException.unavailable(readUnavailable);
    }
    return _guard(() async {
      final personal = await _api.fetchProfilePersonal();
      final professional = await _api.fetchProfileProfessional();
      return ProfileSnapshot(
        profile: ProfileApiMapper.fromApi(
          personal: personal,
          professional: professional,
        ),
        source: ProfileDataSource.liveApi,
        persisted: false,
      );
    });
  }

  @override
  Future<ProfileSnapshot> save(
    ProfileSection section,
    ProfileSectionDraft draft,
  ) async {
    if (!_api.isConfigured) {
      throw ProfileRepositoryException.unavailable(writeUnavailable);
    }
    return _guard(() async {
      switch (section) {
        case ProfileSection.personal:
        case ProfileSection.summary:
          final personal = await _api.fetchProfilePersonal();
          await _api.patchProfilePersonal({
            'name': personal['name'],
            'username': personal['username'],
            'preferred_language': personal['preferred_language'] ?? 'en',
            if (section == ProfileSection.personal) ...{
              'location': draft.text?.trim(),
              if (draft.secondaryText != null &&
                  draft.secondaryText!.trim().isNotEmpty)
                'professional_headline': draft.secondaryText!.trim(),
            },
            if (section == ProfileSection.summary) 'bio': draft.text?.trim(),
          });
        case ProfileSection.jobPreferences:
          await _api.patchProfileProfessional({
            'employment_preference': draft.text?.trim(),
          });
        case ProfileSection.skills:
          final skill = draft.skills.firstWhere(
            (name) => name.trim().isNotEmpty,
            orElse: () => draft.text?.trim() ?? '',
          );
          if (skill.isEmpty) {
            throw ProfileRepositoryException.validation('Enter a skill.');
          }
          await _api.addProfileSkill(skill.trim());
        case ProfileSection.qualifications:
          final value = draft.qualifications.firstOrNull?.trim() ??
              draft.text?.trim() ??
              '';
          if (value.isEmpty) {
            throw ProfileRepositoryException.validation(
              'Enter a qualification.',
            );
          }
          await _api.patchProfileProfessional({'qualification': value});
        case ProfileSection.experience:
          final row = draft.experience.firstOrNull;
          if (row == null ||
              (row.title.trim().isEmpty && row.organization.trim().isEmpty)) {
            throw ProfileRepositoryException.validation(
              'Enter a role and organization.',
            );
          }
          final historyId = draft.recordId ?? row.id;
          if (historyId != null) {
            await _api.updateEmploymentHistory(
              historyId,
              designation: row.title.trim(),
              company: row.organization.trim(),
            );
          } else {
            await _api.addEmploymentHistory(
              designation: row.title.trim(),
              company: row.organization.trim(),
            );
          }
        case ProfileSection.education:
          final row = draft.education.firstOrNull;
          if (row == null || row.institution.trim().isEmpty) {
            throw ProfileRepositoryException.validation(
              'Enter an institution.',
            );
          }
          final educationId = draft.recordId ?? row.id;
          if (educationId != null) {
            await _api.updateProfileEducation(
              educationId,
              institution: row.institution.trim(),
              qualification: row.credential.trim().isEmpty
                  ? null
                  : row.credential.trim(),
            );
          } else {
            await _api.addProfileEducation(
              institution: row.institution.trim(),
              qualification: row.credential.trim().isEmpty
                  ? null
                  : row.credential.trim(),
            );
          }
        case ProfileSection.certifications:
          final row = draft.certifications.firstOrNull;
          if (row == null || row.name.trim().isEmpty) {
            throw ProfileRepositoryException.validation(
              'Enter a certification name.',
            );
          }
          final certificationId = draft.recordId ?? row.id;
          if (certificationId != null) {
            await _api.updateProfileCertification(
              certificationId,
              name: row.name.trim(),
              issuer: row.issuer?.trim(),
            );
          } else {
            await _api.addProfileCertification(
              name: row.name.trim(),
              issuer: row.issuer?.trim(),
            );
          }
      }
      final snapshot = await fetchProfile();
      return ProfileSnapshot(
        profile: snapshot.profile,
        source: ProfileDataSource.liveApi,
        persisted: true,
      );
    });
  }

  @override
  Future<ProfileSnapshot> deleteRecord(ProfileSection section, int recordId) async {
    if (!_api.isConfigured) {
      throw ProfileRepositoryException.unavailable(writeUnavailable);
    }
    return _guard(() async {
      switch (section) {
        case ProfileSection.education:
          await _api.deleteProfileEducation(recordId);
        case ProfileSection.certifications:
          await _api.deleteProfileCertification(recordId);
        case ProfileSection.experience:
          await _api.deleteEmploymentHistory(recordId);
        default:
          throw ProfileRepositoryException.validation(
            'This section cannot be deleted from the mobile app.',
          );
      }
      final snapshot = await fetchProfile();
      return ProfileSnapshot(
        profile: snapshot.profile,
        source: ProfileDataSource.liveApi,
        persisted: true,
      );
    });
  }

  @override
  Future<ProfileSnapshot> uploadProfilePhoto({
    required List<int> bytes,
    required String filename,
  }) async {
    if (!_api.isConfigured) {
      throw ProfileRepositoryException.unavailable(writeUnavailable);
    }
    if (bytes.isEmpty) {
      throw ProfileRepositoryException.validation('Choose a photo to upload.');
    }
    if (bytes.length > maxProfilePhotoBytes) {
      throw ProfileRepositoryException.validation(
        'Photo must be 5 MB or smaller.',
      );
    }
    final extension = filename.contains('.')
        ? filename.split('.').last.toLowerCase()
        : '';
    if (extension.isEmpty ||
        !allowedProfilePhotoExtensions.contains(extension)) {
      throw ProfileRepositoryException.validation(
        'Use a JPG, PNG, or WebP photo.',
      );
    }
    return _guard(() async {
      final personal = await _api.fetchProfilePersonal();
      await _api.patchProfilePersonalWithPhoto(
        fields: {
          'name': '${personal['name']}',
          'username': '${personal['username']}',
          'preferred_language':
              '${personal['preferred_language'] ?? 'en'}',
        },
        photoBytes: bytes,
        photoFilename: filename,
      );
      final snapshot = await fetchProfile();
      return ProfileSnapshot(
        profile: snapshot.profile,
        source: ProfileDataSource.liveApi,
        persisted: true,
      );
    });
  }

  Future<T> _guard<T>(Future<T> Function() action) async {
    try {
      return await action();
    } on ProfileRepositoryException {
      rethrow;
    } on HireApiException catch (error) {
      if (error.statusCode == 401) {
        throw ProfileRepositoryException(
          ProfileFailureKind.sessionExpired,
          error.message,
        );
      }
      if (error.statusCode == 422) {
        throw ProfileRepositoryException.validation(error.message);
      }
      throw ProfileRepositoryException.unavailable(error.message);
    } on TimeoutException {
      throw ProfileRepositoryException(
        ProfileFailureKind.offline,
        'Could not reach OurNear. Check your connection and try again.',
      );
    } on http.ClientException {
      throw ProfileRepositoryException(
        ProfileFailureKind.offline,
        'Could not reach OurNear. Check your connection and try again.',
      );
    } catch (error) {
      throw ProfileRepositoryException(
        ProfileFailureKind.unknown,
        'Could not complete the profile request. $error',
      );
    }
  }
}

extension<T> on List<T> {
  T? get firstOrNull => isEmpty ? null : first;
}
