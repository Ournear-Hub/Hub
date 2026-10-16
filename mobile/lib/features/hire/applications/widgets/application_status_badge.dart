import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/widgets/status_badge.dart';
import 'package:ournear_mobile/features/hire/applications/models/candidate_application.dart';
import 'package:ournear_mobile/features/hire/applications/models/candidate_application_status.dart';

class ApplicationStatusBadge extends StatelessWidget {
  const ApplicationStatusBadge({super.key, required this.application})
    : status = null,
      statusLabel = null;

  const ApplicationStatusBadge.legacy({
    super.key,
    required CandidateApplicationStatus this.status,
  }) : application = null,
       statusLabel = null;

  const ApplicationStatusBadge.label({
    super.key,
    required String this.statusLabel,
    this.status,
  }) : application = null;

  final CandidateApplication? application;
  final CandidateApplicationStatus? status;
  final String? statusLabel;

  String get _label =>
      application?.statusLabel ?? statusLabel ?? status?.label ?? 'Status';

  @override
  Widget build(BuildContext context) {
    final tone = switch (status ?? _guessTone(_label)) {
      CandidateApplicationStatus.submitted => StatusBadgeTone.info,
      CandidateApplicationStatus.underReview => StatusBadgeTone.brand,
      CandidateApplicationStatus.shortlisted => StatusBadgeTone.success,
      CandidateApplicationStatus.notSelected => StatusBadgeTone.neutral,
    };

    return StatusBadge(label: _label, tone: tone);
  }

  CandidateApplicationStatus _guessTone(String label) {
    final lower = label.toLowerCase();
    if (lower.contains('shortlist') || lower.contains('selected')) {
      return CandidateApplicationStatus.shortlisted;
    }
    if (lower.contains('reject') || lower.contains('not selected')) {
      return CandidateApplicationStatus.notSelected;
    }
    if (lower.contains('review')) {
      return CandidateApplicationStatus.underReview;
    }
    return CandidateApplicationStatus.submitted;
  }
}
