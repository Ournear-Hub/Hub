import 'dart:async';

import 'package:http/http.dart' as http;
import 'package:ournear_mobile/core/api/hire_api_exception.dart';
import 'package:ournear_mobile/core/api/hire_api_service.dart';
import 'package:ournear_mobile/features/hire/communication/models/candidate_notification.dart';

enum NotificationDataSource { liveApi, mockDemo }

enum NotificationFailureKind { offline, unavailable, unauthenticated, unknown }

class NotificationRepositoryException implements Exception {
  const NotificationRepositoryException(this.kind, this.message);

  final NotificationFailureKind kind;
  final String message;

  @override
  String toString() => message;
}

class NotificationsSnapshot {
  const NotificationsSnapshot({
    required this.notifications,
    required this.source,
  });

  final List<CandidateNotification> notifications;
  final NotificationDataSource source;
}

abstract class NotificationsRepository {
  Future<NotificationsSnapshot> fetchNotifications();

  Future<void> markRead(String id);

  Future<void> dismiss(String id);
}

class LiveNotificationsRepository implements NotificationsRepository {
  LiveNotificationsRepository({HireApiService? api}) : _api = api ?? HireApiService();

  final HireApiService _api;

  static const unavailable =
      'Notifications are not connected. Configure OURNEAR_API_BASE and sign in.';

  static const emptyInbox = 'You have no notifications.';

  static const markReadUnavailable =
      'Marking a notification read is not connected. Nothing was changed.';

  static const dismissUnavailable =
      'Dismiss is not supported on the server. Mark the notification as read instead.';

  @override
  Future<NotificationsSnapshot> fetchNotifications() async {
    if (!_api.isConfigured) {
      throw const NotificationRepositoryException(
        NotificationFailureKind.unavailable,
        unavailable,
      );
    }
    return _guard(() async {
      final rows = await _api.fetchNotifications();
      final notifications = rows
          .map(
            (row) => CandidateNotification(
              id: row['id'] as String? ?? '',
              title: row['title'] as String? ?? 'Notification',
              body: row['body'] as String? ?? '',
              unread: row['is_unread'] as bool? ?? row['read_at'] == null,
            ),
          )
          .where((row) => row.id.isNotEmpty)
          .toList();
      return NotificationsSnapshot(
        notifications: notifications,
        source: NotificationDataSource.liveApi,
      );
    });
  }

  @override
  Future<void> markRead(String id) async {
    if (!_api.isConfigured) {
      throw const NotificationRepositoryException(
        NotificationFailureKind.unavailable,
        markReadUnavailable,
      );
    }
    await _guard(() => _api.markNotificationRead(id));
  }

  @override
  Future<void> dismiss(String id) async {
    await markRead(id);
  }

  Future<T> _guard<T>(Future<T> Function() action) async {
    try {
      return await action();
    } on NotificationRepositoryException {
      rethrow;
    } on HireApiException catch (error) {
      if (error.statusCode == 401) {
        throw NotificationRepositoryException(
          NotificationFailureKind.unauthenticated,
          error.message,
        );
      }
      throw NotificationRepositoryException(
        NotificationFailureKind.unavailable,
        error.message,
      );
    } on TimeoutException {
      throw const NotificationRepositoryException(
        NotificationFailureKind.offline,
        'Could not reach OurNear. Check your connection and try again.',
      );
    } on http.ClientException {
      throw const NotificationRepositoryException(
        NotificationFailureKind.offline,
        'Could not reach OurNear. Check your connection and try again.',
      );
    } catch (error) {
      throw NotificationRepositoryException(
        NotificationFailureKind.unknown,
        'Could not complete the notifications request. $error',
      );
    }
  }
}
