import 'dart:async';

import 'package:http/http.dart' as http;
import 'package:ournear_mobile/core/api/hire_api_exception.dart';
import 'package:ournear_mobile/core/api/hire_api_service.dart';
import 'package:ournear_mobile/features/hire/communication/models/candidate_conversation.dart';

enum MessageDataSource { liveApi, mockDemo }

enum MessageFailureKind { offline, unavailable, unauthenticated, unknown }

class MessageRepositoryException implements Exception {
  const MessageRepositoryException(this.kind, this.message);

  final MessageFailureKind kind;
  final String message;

  @override
  String toString() => message;
}

class MessagesSnapshot {
  const MessagesSnapshot({required this.conversations, required this.source});

  final List<CandidateConversation> conversations;
  final MessageDataSource source;
}

class ConversationView {
  const ConversationView({required this.conversation, required this.source});

  final CandidateConversation conversation;
  final MessageDataSource source;
}

abstract class MessagesRepository {
  Future<MessagesSnapshot> fetchInbox();

  Future<ConversationView> fetchConversation(String id);

  Future<ConversationView> sendMessage(String conversationId, String body);
}

class LiveMessagesRepository implements MessagesRepository {
  LiveMessagesRepository({HireApiService? api}) : _api = api ?? HireApiService();

  final HireApiService _api;

  static const unavailable =
      'Messages are not connected. Configure OURNEAR_API_BASE and sign in.';

  static const emptyInbox = 'You have no messages.';

  @override
  Future<MessagesSnapshot> fetchInbox() async {
    if (!_api.isConfigured) {
      throw const MessageRepositoryException(
        MessageFailureKind.unavailable,
        unavailable,
      );
    }
    return _guard(() async {
      final rows = await _api.fetchMessagesInbox();
      final conversations = rows.map(_conversationFromInboxRow).toList();
      return MessagesSnapshot(
        conversations: conversations,
        source: MessageDataSource.liveApi,
      );
    });
  }

  @override
  Future<ConversationView> fetchConversation(String id) async {
    if (!_api.isConfigured) {
      throw const MessageRepositoryException(
        MessageFailureKind.unavailable,
        unavailable,
      );
    }
    return _guard(() async {
      final applicationId = int.tryParse(id);
      if (applicationId == null) {
        throw MessageRepositoryException(
          MessageFailureKind.unavailable,
          'Conversation is not available.',
        );
      }
      final data = await _api.fetchApplicationMessages(applicationId);
      return ConversationView(
        conversation: _conversationFromThread(data),
        source: MessageDataSource.liveApi,
      );
    });
  }

  @override
  Future<ConversationView> sendMessage(String conversationId, String body) async {
    if (!_api.isConfigured) {
      throw const MessageRepositoryException(
        MessageFailureKind.unavailable,
        unavailable,
      );
    }
    final trimmed = body.trim();
    if (trimmed.isEmpty) {
      throw const MessageRepositoryException(
        MessageFailureKind.unavailable,
        'Enter a message.',
      );
    }
    return _guard(() async {
      final applicationId = int.tryParse(conversationId);
      if (applicationId == null) {
        throw MessageRepositoryException(
          MessageFailureKind.unavailable,
          'Conversation is not available.',
        );
      }
      await _api.sendApplicationMessage(applicationId, trimmed);
      return fetchConversation(conversationId);
    });
  }

  CandidateConversation _conversationFromInboxRow(Map<String, dynamic> row) {
    return CandidateConversation(
      id: '${row['id']}',
      title: row['title'] as String? ?? 'Messages',
      preview: row['preview'] as String? ?? '',
      unread: row['unread'] as bool? ?? false,
      jobSlug: row['job_slug'] as String?,
      applicationId: row['application_id']?.toString(),
      messages: const [],
    );
  }

  CandidateConversation _conversationFromThread(Map<String, dynamic> data) {
    final messagesRaw = data['messages'];
    final messages = messagesRaw is List
        ? messagesRaw
            .whereType<Map>()
            .map(
              (row) => CandidateMessage(
                id: '${row['id']}',
                body: row['body'] as String? ?? '',
                fromCandidate: row['from_candidate'] as bool? ?? false,
              ),
            )
            .toList()
        : const <CandidateMessage>[];

    return CandidateConversation(
      id: '${data['id']}',
      title: data['title'] as String? ?? 'Messages',
      preview: data['preview'] as String? ?? '',
      unread: data['unread'] as bool? ?? false,
      jobSlug: data['job_slug'] as String?,
      applicationId: data['application_id']?.toString(),
      messages: messages,
    );
  }

  Future<T> _guard<T>(Future<T> Function() action) async {
    try {
      return await action();
    } on MessageRepositoryException {
      rethrow;
    } on HireApiException catch (error) {
      if (error.statusCode == 401) {
        throw MessageRepositoryException(
          MessageFailureKind.unauthenticated,
          error.message,
        );
      }
      throw MessageRepositoryException(
        MessageFailureKind.unavailable,
        error.message,
      );
    } on TimeoutException {
      throw const MessageRepositoryException(
        MessageFailureKind.offline,
        'Could not reach OurNear. Check your connection and try again.',
      );
    } on http.ClientException {
      throw const MessageRepositoryException(
        MessageFailureKind.offline,
        'Could not reach OurNear. Check your connection and try again.',
      );
    } catch (error) {
      throw MessageRepositoryException(
        MessageFailureKind.unknown,
        'Could not complete the messages request. $error',
      );
    }
  }
}
