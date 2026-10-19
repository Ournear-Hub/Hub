import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/theme/hire_theme_context.dart';
import 'package:ournear_mobile/features/hire/communication/communication_demo_banner.dart';
import 'package:ournear_mobile/features/hire/communication/communication_signed_out.dart';
import 'package:ournear_mobile/features/hire/communication/data/messages_repository.dart';
import 'package:provider/provider.dart';

class MessageThreadScreen extends StatefulWidget {
  const MessageThreadScreen({
    super.key,
    required this.conversationId,
    this.repository,
  });

  final String conversationId;
  final MessagesRepository? repository;

  @override
  State<MessageThreadScreen> createState() => _MessageThreadScreenState();
}

class _MessageThreadScreenState extends State<MessageThreadScreen> {
  ConversationView? _view;
  MessageRepositoryException? _error;
  var _loading = false;
  var _started = false;
  var _sending = false;
  final _composer = TextEditingController();
  String? _sendMessage;

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
      final view = await _repository().fetchConversation(widget.conversationId);
      if (!mounted) {
        return;
      }
      setState(() {
        _view = view;
        _loading = false;
      });
    } on MessageRepositoryException catch (error) {
      if (!mounted) {
        return;
      }
      setState(() {
        _view = null;
        _error = error;
        _loading = false;
      });
    }
  }

  Future<void> _send() async {
    if (_sending || _view?.source == MessageDataSource.mockDemo) {
      return;
    }
    final body = _composer.text.trim();
    if (body.isEmpty) {
      return;
    }
    setState(() {
      _sending = true;
      _sendMessage = null;
    });
    try {
      final view = await _repository().sendMessage(widget.conversationId, body);
      if (!mounted) {
        return;
      }
      _composer.clear();
      setState(() {
        _view = view;
        _sending = false;
        _sendMessage = 'Message sent on OurNear.';
      });
    } on MessageRepositoryException catch (error) {
      if (!mounted) {
        return;
      }
      setState(() {
        _sending = false;
        _sendMessage = error.message;
      });
    }
  }

  @override
  void dispose() {
    _composer.dispose();
    super.dispose();
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
    final conversation = _view?.conversation;
    final live = _view?.source == MessageDataSource.liveApi;
    return Scaffold(
      backgroundColor: context.hireCanvas,
      appBar: AppBar(title: Text(conversation?.title ?? 'Conversation')),
      body: !signedIn
          ? const CommunicationSignedOut(
              detail:
                  'Sign in to view a conversation. No messages are shown before sign-in.',
            )
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                if (_loading)
                  const Center(
                    child: CircularProgressIndicator(
                      semanticsLabel: 'Loading conversation',
                    ),
                  ),
                if (_error != null) Text(_error!.message),
                if (conversation != null) ...[
                  if (_view?.source == MessageDataSource.mockDemo) ...[
                    const CommunicationDemoBanner(
                      message:
                          'Demo conversation. Sending is disabled. This thread is not a message from an employer or recruiter.',
                    ),
                    const SizedBox(height: 12),
                  ],
                  if (conversation.messages.isEmpty)
                    Text(
                      _view?.source == MessageDataSource.mockDemo
                          ? 'This demo conversation has no messages. It is not from an employer.'
                          : 'This conversation has no messages.',
                    )
                  else
                    for (final message in conversation.messages)
                      Card(
                        color: context.hireCardSurface,
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Text(message.body),
                        ),
                      ),
                  if (conversation.jobSlug != null) ...[
                    const SizedBox(height: 8),
                    Text('Job reference: ${conversation.jobSlug}'),
                  ],
                  if (conversation.applicationId != null) ...[
                    const SizedBox(height: 8),
                    Text(
                      'Application reference: ${conversation.applicationId}',
                    ),
                  ],
                ],
                const SizedBox(height: 16),
                TextField(
                  controller: _composer,
                  enabled: live && !_sending,
                  decoration: InputDecoration(
                    labelText: 'Message',
                    helperText: live
                        ? 'Your message is sent through OurNear after you tap Send.'
                        : 'Sending is not connected.',
                  ),
                ),
                const SizedBox(height: 8),
                OutlinedButton(
                  key: const Key('message-send'),
                  onPressed: live && !_sending ? _send : null,
                  child: Text(_sending ? 'Sending…' : 'Send'),
                ),
                if (_sendMessage != null) ...[
                  const SizedBox(height: 8),
                  Text(_sendMessage!),
                ],
              ],
            ),
    );
  }
}
