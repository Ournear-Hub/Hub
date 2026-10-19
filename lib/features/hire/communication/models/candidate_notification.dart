class CandidateNotification {
  const CandidateNotification({
    required this.id,
    required this.title,
    required this.body,
    required this.unread,
  });

  final String id;
  final String title;
  final String body;
  final bool unread;
}
