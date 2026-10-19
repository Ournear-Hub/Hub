import 'package:ournear_mobile/core/api/api_date.dart';
import 'package:ournear_mobile/features/hire/applications/models/candidate_application_status.dart';

/// A timestamp already stored on the application. Not an inferred recruiter event.
class ApplicationTimelineEvent {
  const ApplicationTimelineEvent({
    required this.label,
    required this.occurredAt,
  });

  final String label;
  final DateTime occurredAt;
}

/// Candidate job application summary for list and detail screens.
class CandidateApplication {
  const CandidateApplication({
    required this.id,
    required this.roleTitle,
    required this.companyName,
    required this.statusLabel,
    this.status,
    required this.submittedAt,
    this.location,
    this.updatedAt,
    this.jobSlug,
    this.timeline = const [],
    this.hasResume = false,
    this.businessId,
  });

  final String id;
  final String roleTitle;
  final String companyName;
  final String? location;

  /// Laravel `toCandidateStatusLabel()` — shown as-is in the UI.
  final String statusLabel;

  /// Optional demo enum for badge tone when using mock data.
  final CandidateApplicationStatus? status;
  final DateTime submittedAt;
  final DateTime? updatedAt;

  /// Optional public job slug when linked to a browsable posting.
  final String? jobSlug;

  /// Events supplied with the record. Empty means the source provided none.
  final List<ApplicationTimelineEvent> timeline;

  final bool hasResume;
  final String? businessId;

  DateTime get lastActivityAt => updatedAt ?? submittedAt;

  /// Builds a timeline from the submitted and updated timestamps only.
  static List<ApplicationTimelineEvent> timelineFromTimestamps({
    required DateTime submittedAt,
    DateTime? updatedAt,
  }) {
    final events = <ApplicationTimelineEvent>[
      ApplicationTimelineEvent(label: 'Submitted', occurredAt: submittedAt),
    ];
    if (updatedAt != null && updatedAt != submittedAt) {
      events.add(
        ApplicationTimelineEvent(label: 'Updated', occurredAt: updatedAt),
      );
    }
    return events;
  }

  factory CandidateApplication.fromApiJson(Map<String, dynamic> json) {
    final job = json['job'] as Map<String, dynamic>?;
    final submittedAt =
        parseApiDateTime(json['applied_at']) ?? DateTime.now().toUtc();
    final updatedAt = parseApiDateTime(json['updated_at']);
    final timelineRaw = json['timeline'] as List<dynamic>? ?? const [];
    final timeline = timelineRaw
        .map((item) {
          final map = item as Map<String, dynamic>? ?? {};
          final at = parseApiDateTime(map['at']);
          if (at == null) {
            return null;
          }
          return ApplicationTimelineEvent(
            label: map['label'] as String? ?? 'Update',
            occurredAt: at,
          );
        })
        .whereType<ApplicationTimelineEvent>()
        .toList();

    return CandidateApplication(
      id: '${json['id']}',
      businessId: json['business_id'] as String?,
      roleTitle: job?['title'] as String? ?? 'Role',
      companyName: job?['company'] as String? ?? '',
      location: job?['location'] as String?,
      statusLabel: json['status'] as String? ?? 'Submitted',
      submittedAt: submittedAt,
      updatedAt: updatedAt,
      jobSlug: job?['slug'] as String?,
      timeline: timeline.isEmpty
          ? timelineFromTimestamps(
              submittedAt: submittedAt,
              updatedAt: updatedAt,
            )
          : timeline,
      hasResume: json['has_resume'] as bool? ?? false,
    );
  }
}
