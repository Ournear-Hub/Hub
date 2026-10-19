import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/theme/hire_theme_context.dart';
import 'package:ournear_mobile/features/hire/communication/communication_demo_banner.dart';
import 'package:ournear_mobile/features/hire/communication/communication_signed_out.dart';
import 'package:ournear_mobile/features/hire/communication/data/notifications_repository.dart';
import 'package:ournear_mobile/features/hire/communication/models/candidate_notification.dart';
import 'package:provider/provider.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key, this.repository});

  final NotificationsRepository? repository;

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  NotificationsSnapshot? _snapshot;
  NotificationRepositoryException? _error;
  String? _actionMessage;
  var _loading = false;
  var _acting = false;
  var _started = false;

  bool _signedIn(AuthController auth) {
    return auth.status == AuthStatus.authenticated && auth.user != null;
  }

  NotificationsRepository _repository() {
    final injected = widget.repository;
    if (injected != null) {
      return injected;
    }
    try {
      return context.read<NotificationsRepository>();
    } on ProviderNotFoundException {
      return LiveNotificationsRepository();
    }
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final snapshot = await _repository().fetchNotifications();
      if (!mounted) {
        return;
      }
      setState(() {
        _snapshot = snapshot;
        _loading = false;
      });
    } on NotificationRepositoryException catch (error) {
      if (!mounted) {
        return;
      }
      setState(() {
        _snapshot = null;
        _error = error;
        _loading = false;
      });
    }
  }

  Future<void> _run(Future<void> Function(String id) action, String id) async {
    if (_acting) {
      return;
    }
    setState(() {
      _acting = true;
      _actionMessage = null;
    });
    try {
      await action(id);
    } on NotificationRepositoryException catch (error) {
      if (!mounted) {
        return;
      }
      setState(() => _actionMessage = error.message);
    } finally {
      if (mounted) {
        setState(() => _acting = false);
      }
    }
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    final auth = context.read<AuthController>();
    if (!_signedIn(auth)) {
      _started = false;
      _snapshot = null;
      return;
    }
    if (!_started) {
      _started = true;
      _load();
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthController>();
    final signedIn = _signedIn(auth);
    return Scaffold(
      backgroundColor: context.hireCanvas,
      appBar: AppBar(title: const Text('Notifications')),
      body: !signedIn
          ? const CommunicationSignedOut(
              detail:
                  'Sign in to view notifications. No alerts are shown before sign-in.',
            )
          : _body(),
    );
  }

  Widget _body() {
    final snapshot = _snapshot;
    final showActions =
        _error != null ||
        (snapshot != null && snapshot.notifications.isNotEmpty);
    final actionId = snapshot != null && snapshot.notifications.isNotEmpty
        ? snapshot.notifications.first.id
        : '';
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        if (_loading)
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 24),
            child: Center(
              child: CircularProgressIndicator(
                semanticsLabel: 'Loading notifications',
              ),
            ),
          ),
        if (_error != null) ...[
          Text(_error!.message),
          const SizedBox(height: 12),
          FilledButton(
            key: const Key('notifications-retry'),
            style: FilledButton.styleFrom(
              minimumSize: const Size.fromHeight(48),
            ),
            onPressed: _loading ? null : _load,
            child: const Text('Try again'),
          ),
        ],
        if (snapshot != null) ...[
          if (snapshot.source == NotificationDataSource.mockDemo) ...[
            const CommunicationDemoBanner(
              message:
                  'Demo notifications. These are not from OurNear and have not been delivered to this device.',
            ),
            const SizedBox(height: 12),
          ],
          if (snapshot.notifications.isEmpty)
            Text(
              snapshot.source == NotificationDataSource.liveApi
                  ? LiveNotificationsRepository.emptyInbox
                  : 'This demo list has no notifications. It is not a response from OurNear.',
            )
          else
            for (final item in snapshot.notifications) _row(item),
        ],
        if (showActions) ...[
          const SizedBox(height: 12),
          OutlinedButton(
            key: const Key('notification-mark-read'),
            style: OutlinedButton.styleFrom(
              minimumSize: const Size.fromHeight(48),
            ),
            onPressed: _acting
                ? null
                : () => _run(_repository().markRead, actionId),
            child: const Text('Mark read'),
          ),
          const SizedBox(height: 8),
          OutlinedButton(
            key: const Key('notification-dismiss'),
            style: OutlinedButton.styleFrom(
              minimumSize: const Size.fromHeight(48),
            ),
            onPressed: _acting
                ? null
                : () => _run(_repository().dismiss, actionId),
            child: const Text('Dismiss'),
          ),
        ],
        if (_actionMessage != null) ...[
          const SizedBox(height: 12),
          Text(_actionMessage!, key: const Key('notification-action-message')),
        ],
      ],
    );
  }

  Widget _row(CandidateNotification item) {
    return Card(
      color: context.hireCardSurface,
      child: ListTile(
        title: Text(item.title),
        subtitle: Text(item.body),
        trailing: item.unread ? const Text('Unread') : null,
      ),
    );
  }
}
