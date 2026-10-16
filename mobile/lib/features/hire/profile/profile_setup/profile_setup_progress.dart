import 'package:ournear_mobile/features/hire/profile/models/candidate_profile.dart';

/// Local UI progress only. Not a server completion score.
enum ProfileSetupSectionStatus {
  notStarted,
  inProgress,
  reviewedLocally,
}

/// Guided setup section order (maps to existing [ProfileSection] edit forms).
const guidedProfileSetupSections = <ProfileSection>[
  ProfileSection.personal,
  ProfileSection.summary,
  ProfileSection.skills,
  ProfileSection.experience,
  ProfileSection.education,
  ProfileSection.qualifications,
  ProfileSection.certifications,
  ProfileSection.jobPreferences,
];

String guidedProfileSetupTitle(ProfileSection section) {
  return switch (section) {
    ProfileSection.personal => 'Personal information',
    ProfileSection.summary => 'Professional headline and summary',
    ProfileSection.skills => 'Skills',
    ProfileSection.experience => 'Work experience',
    ProfileSection.education => 'Education',
    ProfileSection.qualifications => 'Qualifications',
    ProfileSection.certifications => 'Certifications',
    ProfileSection.jobPreferences => 'Job preferences',
  };
}

String guidedProfileSetupSubtitle(ProfileSection section) {
  return switch (section) {
    ProfileSection.personal =>
      'Public location and professional headline. Account name stays on the signed-in account.',
    ProfileSection.summary => 'Professional summary (bio) shown on your profile.',
    ProfileSection.skills => 'Skills you want employers to see.',
    ProfileSection.experience => 'Recent role title and organization.',
    ProfileSection.education => 'Credential and institution.',
    ProfileSection.qualifications => 'Qualifications or registrations.',
    ProfileSection.certifications => 'Certification name and issuer.',
    ProfileSection.jobPreferences =>
      'Roles, locations, or schedule preferences in free text.',
  };
}

class ProfileSetupProgress {
  const ProfileSetupProgress({
    required this.profile,
    this.touchedSections = const {},
  });

  final CandidateProfile profile;
  final Set<ProfileSection> touchedSections;

  ProfileSetupSectionStatus statusFor(ProfileSection section) {
    if (_sectionHasLocalContent(section, profile)) {
      return ProfileSetupSectionStatus.reviewedLocally;
    }
    if (touchedSections.contains(section)) {
      return ProfileSetupSectionStatus.inProgress;
    }
    return ProfileSetupSectionStatus.notStarted;
  }

  int get reviewedLocallyCount {
    var count = 0;
    for (final section in guidedProfileSetupSections) {
      if (statusFor(section) == ProfileSetupSectionStatus.reviewedLocally) {
        count++;
      }
    }
    return count;
  }

  bool hasLocalContent(ProfileSection section) =>
      _sectionHasLocalContent(section, profile);

  static bool _sectionHasLocalContent(
    ProfileSection section,
    CandidateProfile profile,
  ) {
    return switch (section) {
      ProfileSection.personal => _filled(profile.publicLocation),
      ProfileSection.summary => _filled(profile.summary),
      ProfileSection.skills => profile.skills.any(_filled),
      ProfileSection.experience =>
        profile.experience.any((row) => _filled(row.title)),
      ProfileSection.education =>
        profile.education.any((row) => _filled(row.credential)),
      ProfileSection.qualifications =>
        profile.qualifications.any(_filled),
      ProfileSection.certifications =>
        profile.certifications.any((row) => _filled(row.name)),
      ProfileSection.jobPreferences => _filled(profile.jobPreferences),
    };
  }

  static bool _filled(String? value) =>
      value != null && value.trim().isNotEmpty;

  static String statusLabel(ProfileSetupSectionStatus status) {
    return switch (status) {
      ProfileSetupSectionStatus.notStarted => 'Not started',
      ProfileSetupSectionStatus.inProgress => 'In progress',
      ProfileSetupSectionStatus.reviewedLocally => 'Reviewed locally',
    };
  }
}
