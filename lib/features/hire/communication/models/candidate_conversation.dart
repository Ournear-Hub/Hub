class CandidateMessage {
  const CandidateMessage({
    required this.id,
    required this.body,
    required this.fromCandidate,
  });

  final String id;
  final String body;
  final bool fromCandidate;
}

class CandidateConversation {
  const CandidateConversation({
    required this.id,
    required this.title,
    required this.preview,
    required this.unread,
    required this.messages,
    this.jobSlug,
    this.applicationId,
  });

  final String id;
  final String title;
  final String preview;
  final bool unread;
  final String? jobSlug;
  final String? applicationId;
  final List<CandidateMessage> messages;
}
