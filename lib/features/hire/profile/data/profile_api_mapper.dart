import 'package:ournear_mobile/features/hire/profile/models/candidate_profile.dart';

/// Maps Laravel mobile profile JSON to [CandidateProfile].
class ProfileApiMapper {
  const ProfileApiMapper._();

  static CandidateProfile fromApi({
    required Map<String, dynamic> personal,
    required Map<String, dynamic> professional,
  }) {
    final skills = _skillNames(professional['skills']);
    final experience = _employment(professional['employment_histories']);
    final education = _education(professional['educations']);
    final qualifications = _qualifications(professional);
    final certifications = _certifications(professional['certifications']);

    final photoRaw =
        personal['photo_url'] as String? ?? personal['profile_photo_url'] as String?;
    final trimmedPhoto = photoRaw?.trim();
    final photoUrl =
        trimmedPhoto == null || trimmedPhoto.isEmpty ? null : trimmedPhoto;

    return CandidateProfile(
      headline: personal['professional_headline'] as String?,
      publicLocation: personal['location'] as String?,
      photoUrl: photoUrl,
      summary: personal['bio'] as String?,
      skills: skills,
      experience: experience,
      education: education,
      qualifications: qualifications,
      certifications: certifications,
      jobPreferences: professional['employment_preference'] as String?,
    );
  }

  static List<String> _skillNames(Object? raw) {
    if (raw is! List) {
      return const [];
    }
    return raw
        .map((item) {
          if (item is Map) {
            return (item['name'] as String?)?.trim() ?? '';
          }
          return item.toString();
        })
        .where((name) => name.isNotEmpty)
        .toList();
  }

  static List<ProfileExperience> _employment(Object? raw) {
    if (raw is! List) {
      return const [];
    }
    return raw.map((item) {
      final map = item as Map<String, dynamic>? ?? {};
      final period = _period(
        map['started_on'] as String?,
        map['ended_on'] as String?,
        map['is_current'] as bool? ?? false,
      );
      return ProfileExperience(
        id: map['id'] is int ? map['id'] as int : int.tryParse('${map['id']}'),
        title: map['designation'] as String? ?? '',
        organization: map['company'] as String? ?? '',
        period: period,
      );
    }).where((row) => row.title.isNotEmpty || row.organization.isNotEmpty).toList();
  }

  static List<ProfileEducation> _education(Object? raw) {
    if (raw is! List) {
      return const [];
    }
    return raw.map((item) {
      final map = item as Map<String, dynamic>? ?? {};
      return ProfileEducation(
        id: map['id'] is int ? map['id'] as int : int.tryParse('${map['id']}'),
        credential: map['degree'] as String? ?? map['qualification'] as String? ?? '',
        institution: map['institution'] as String? ?? map['school'] as String? ?? '',
      );
    }).where((row) => row.credential.isNotEmpty || row.institution.isNotEmpty).toList();
  }

  static List<String> _qualifications(Map<String, dynamic> professional) {
    final raw = professional['qualification'] as String?;
    if (raw == null || raw.trim().isEmpty) {
      return const [];
    }
    return [raw.trim()];
  }

  static List<ProfileCertification> _certifications(Object? raw) {
    if (raw is! List) {
      return const [];
    }
    return raw.map((item) {
      final map = item as Map<String, dynamic>? ?? {};
      return ProfileCertification(
        id: map['id'] is int ? map['id'] as int : int.tryParse('${map['id']}'),
        name: map['name'] as String? ?? map['title'] as String? ?? '',
        issuer: map['issuer'] as String? ?? map['organization'] as String?,
      );
    }).where((row) => row.name.isNotEmpty).toList();
  }

  static String? _period(String? start, String? end, bool isCurrent) {
    if ((start == null || start.isEmpty) && (end == null || end.isEmpty)) {
      return isCurrent ? 'Present' : null;
    }
    if (isCurrent) {
      return '${start ?? ''} – Present'.trim();
    }
    if (start != null && end != null) {
      return '$start – $end';
    }
    return start ?? end;
  }
}
