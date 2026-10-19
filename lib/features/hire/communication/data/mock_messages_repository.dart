import 'package:ournear_mobile/features/hire/communication/data/messages_repository.dart';
import 'package:ournear_mobile/features/hire/communication/models/candidate_conversation.dart';

/// In-memory conversations for tests. They are not server messages.
class MockMessagesRepository implements MessagesRepository {
  MockMessagesRepository({
    List<CandidateConversation>? conversations,
    this.failure,
  }) : _conversations = List<CandidateConversation>.of(
         conversations ?? demoConversations,
       );

  static const demoConversations = <CandidateConversation>[
    CandidateConversation(
      id: 'demo-msg-001',
      title: 'Demo conversation',
      preview: 'Demo preview',
      unread: true,
      jobSlug: 'demo-job',
      messages: [
        CandidateMessage(
          id: 'demo-msg-001-a',
          body: 'Demo message body only in the thread',
          fromCandidate: false,
        ),
      ],
    ),
  ];

  final List<CandidateConversation> _conversations;
  final MessageRepositoryException? failure;

  @override
  Future<MessagesSnapshot> fetchInbox() async {
    final error = failure;
    if (error != null) {
      throw error;
    }
    return MessagesSnapshot(
      conversations: List<CandidateConversation>.unmodifiable(_conversations),
      source: MessageDataSource.mockDemo,
    );
  }

  @override
  Future<ConversationView> fetchConversation(String id) async {
    final error = failure;
    if (error != null) {
      throw error;
    }
    for (final conversation in _conversations) {
      if (conversation.id == id) {
        return ConversationView(
          conversation: conversation,
          source: MessageDataSource.mockDemo,
        );
      }
    }
    throw const MessageRepositoryException(
      MessageFailureKind.unavailable,
      'That conversation is not in this demo inbox.',
    );
  }

  @override
  Future<ConversationView> sendMessage(String conversationId, String body) async {
    final error = failure;
    if (error != null) {
      throw error;
    }
    return fetchConversation(conversationId);
  }
}
