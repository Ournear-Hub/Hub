import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/theme/hire_theme_context.dart';
import 'package:ournear_mobile/features/hire/communication/communication_demo_banner.dart';
import 'package:ournear_mobile/features/hire/communication/communication_signed_out.dart';
import 'package:ournear_mobile/features/hire/communication/data/messages_repository.dart';
import 'package:ournear_mobile/features/hire/communication/message_thread_screen.dart';
import 'package:ournear_mobile/features/hire/communication/models/candidate_conversation.dart';
import 'package:provider/provider.dart';

class MessagesInboxScreen extends StatefulWidget {
  const MessagesInboxScreen({super.key, this.repository});

  final MessagesRepository? repository;

  @override
  State<MessagesInboxScreen> createState() => _MessagesInboxScreenState();
}

class _MessagesInboxScreenState extends State<MessagesInboxScreen> {
  MessagesSnapshot? _snapshot;
  MessageRepositoryException? _error;
  var _loading = false;
  var _started = false;

  bool _signedIn(AuthController auth) {
    return auth.status == AuthStatus.authenticated && auth.user != null;
  }

  MessagesRepository _repository() {
    final injected = widget.repository;
    if (injected != null) {
      return injected;
    }
    try {
      return context.read<MessagesRepository>();
    } on ProviderNotFoundException {
      return LiveMessagesRepository();
    }
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final snapshot = await _repository().fetchInbox();
      if (!mounted) {
        return;
      }
      setState(() {
        _snapshot = snapshot;
        _loading = false;
      });
    } on MessageRepositoryException catch (error) {
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

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    final auth = context.read<AuthController>();
    if (!_signedIn(auth)) {
      _started = false;
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
      appBar: AppBar(title: const Text('Messages')),
      body: !signedIn
          ? const CommunicationSignedOut(
              detail:
                  'Sign in to view messages. No inbox is shown before sign-in.',
            )
          : _body(),
    );
  }

  Widget _body() {
    final snapshot = _snapshot;
    final error = _error;
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        if (_loading)
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 24),
            child: Center(
              child: CircularProgressIndicator(
                semanticsLabel: 'Loading messages',
              ),
            ),
          ),
        if (error != null) ...[
          Text(error.message),
          const SizedBox(height: 12),
          FilledButton(
            key: const Key('messages-retry'),
            style: FilledButton.styleFrom(
              minimumSize: const Size.fromHeight(48),
            ),
            onPressed: _loading ? null : _load,
            child: const Text('Try again'),
          ),
        ],
        if (snapshot != null) ...[
          if (snapshot.source == MessageDataSource.mockDemo) ...[
            const CommunicationDemoBanner(
              message:
                  'Demo messages. These are not from OurNear. They do not mean an employer or recruiter contacted you.',
            ),
            const SizedBox(height: 12),
          ],
          if (snapshot.conversations.isEmpty)
            Text(
              snapshot.source == MessageDataSource.liveApi
                  ? LiveMessagesRepository.emptyInbox
                  : 'This demo inbox has no conversations. It is not a response from OurNear.',
            )
          else
            for (final conversation in snapshot.conversations)
              _row(conversation),
        ],
      ],
    );
  }

  Widget _row(CandidateConversation conversation) {
    return Card(
      color: context.hireCardSurface,
      child: ListTile(
        title: Text(conversation.title),
        subtitle: Text(conversation.preview),
        trailing: conversation.unread ? const Text('Unread') : null,
        onTap: () {
          Navigator.of(context).push(
            MaterialPageRoute<void>(
              builder: (_) => MessageThreadScreen(
                conversationId: conversation.id,
                repository: widget.repository,
              ),
            ),
          );
        },
      ),
    );
  }
}
