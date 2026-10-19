/// Client-side application status labels used for mock/demo presentation.
///
/// These values are **not** verified Laravel API enums. Replace mapping once the
/// authorized applications endpoint contract is available.
enum CandidateApplicationStatus {
  submitted,
  underReview,
  shortlisted,
  notSelected,
}

extension CandidateApplicationStatusPresentation on CandidateApplicationStatus {
  String get label => switch (this) {
    CandidateApplicationStatus.submitted => 'Submitted',
    CandidateApplicationStatus.underReview => 'Under review',
    CandidateApplicationStatus.shortlisted => 'Shortlisted',
    CandidateApplicationStatus.notSelected => 'Not selected',
  };
}
