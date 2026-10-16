import 'package:ournear_mobile/features/hire/communication/data/notifications_repository.dart';
import 'package:ournear_mobile/features/hire/communication/models/candidate_notification.dart';

/// In-memory alerts for tests. They are not delivered notifications.
class MockNotificationsRepository implements NotificationsRepository {
  MockNotificationsRepository({
    List<CandidateNotification>? notifications,
    this.failure,
  }) : _notifications = List<CandidateNotification>.of(
         notifications ?? demoNotifications,
       );

  static const demoNotifications = <CandidateNotification>[
    CandidateNotification(
      id: 'demo-notif-001',
      title: 'Demo notification',
      body: 'Demo alert text',
      unread: true,
    ),
  ];

  final List<CandidateNotification> _notifications;
  final NotificationRepositoryException? failure;

  @override
  Future<NotificationsSnapshot> fetchNotifications() async {
    final error = failure;
    if (error != null) {
      throw error;
    }
    return NotificationsSnapshot(
      notifications: List<CandidateNotification>.unmodifiable(_notifications),
      source: NotificationDataSource.mockDemo,
    );
  }

  @override
  Future<void> markRead(String id) async {
    throw const NotificationRepositoryException(
      NotificationFailureKind.unavailable,
      LiveNotificationsRepository.markReadUnavailable,
    );
  }

  @override
  Future<void> dismiss(String id) async {
    throw const NotificationRepositoryException(
      NotificationFailureKind.unavailable,
      LiveNotificationsRepository.dismissUnavailable,
    );
  }
}
