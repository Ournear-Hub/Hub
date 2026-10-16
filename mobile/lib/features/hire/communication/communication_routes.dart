import 'package:flutter/material.dart';
import 'package:ournear_mobile/features/hire/communication/messages_inbox_screen.dart';
import 'package:ournear_mobile/features/hire/communication/notifications_screen.dart';

void openCandidateMessages(BuildContext context) {
  Navigator.of(
    context,
  ).push(MaterialPageRoute<void>(builder: (_) => const MessagesInboxScreen()));
}

void openCandidateNotifications(BuildContext context) {
  Navigator.of(
    context,
  ).push(MaterialPageRoute<void>(builder: (_) => const NotificationsScreen()));
}
