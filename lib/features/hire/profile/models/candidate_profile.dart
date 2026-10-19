/// Professional attributes for one candidate. Account name and username stay on [AuthUser].
class CandidateProfile {
  const CandidateProfile({
    this.headline,
    this.publicLocation,
    this.photoUrl,
    this.summary,
    this.skills = const [],
    this.experience = const [],
    this.education = const [],
    this.qualifications = const [],
    this.certifications = const [],
    this.jobPreferences,
  });

  final String? headline;
  final String? publicLocation;
  /// Absolute URL when returned on personal profile GET. Upload is not wired.
  final String? photoUrl;
  final String? summary;
  final List<String> skills;
  final List<ProfileExperience> experience;
  final List<ProfileEducation> education;
  final List<String> qualifications;
  final List<ProfileCertification> certifications;
  final String? jobPreferences;

  static const empty = CandidateProfile();

  CandidateProfile copyWith({
    String? headline,
    String? publicLocation,
    String? photoUrl,
    String? summary,
    List<String>? skills,
    List<ProfileExperience>? experience,
    List<ProfileEducation>? education,
    List<String>? qualifications,
    List<ProfileCertification>? certifications,
    String? jobPreferences,
  }) {
    return CandidateProfile(
      headline: headline ?? this.headline,
      publicLocation: publicLocation ?? this.publicLocation,
      photoUrl: photoUrl ?? this.photoUrl,
      summary: summary ?? this.summary,
      skills: skills ?? this.skills,
      experience: experience ?? this.experience,
      education: education ?? this.education,
      qualifications: qualifications ?? this.qualifications,
      certifications: certifications ?? this.certifications,
      jobPreferences: jobPreferences ?? this.jobPreferences,
    );
  }
}

class ProfileExperience {
  const ProfileExperience({
    this.id,
    required this.title,
    required this.organization,
    this.period,
  });

  final int? id;
  final String title;
  final String organization;
  final String? period;
}

class ProfileEducation {
  const ProfileEducation({
    this.id,
    required this.credential,
    required this.institution,
  });

  final int? id;
  final String credential;
  final String institution;
}

class ProfileCertification {
  const ProfileCertification({this.id, required this.name, this.issuer});

  final int? id;
  final String name;
  final String? issuer;
}

enum ProfileSection {
  personal,
  summary,
  experience,
  education,
  skills,
  qualifications,
  certifications,
  jobPreferences,
}

/// Fields for one edit form. The repository applies only the active section.
class ProfileSectionDraft {
  const ProfileSectionDraft({
    this.text,
    this.secondaryText,
    this.recordId,
    this.skills = const [],
    this.experience = const [],
    this.education = const [],
    this.qualifications = const [],
    this.certifications = const [],
  });

  final int? recordId;
  final String? text;
  /// Used by personal section for professional headline.
  final String? secondaryText;
  final List<String> skills;
  final List<ProfileExperience> experience;
  final List<ProfileEducation> education;
  final List<String> qualifications;
  final List<ProfileCertification> certifications;
}
