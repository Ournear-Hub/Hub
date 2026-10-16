import 'package:flutter/material.dart';
import '../../../helpers/hire_candidate_shell_helpers.dart';
import '../../../helpers/hire_candidate_test_storage.dart';
import 'package:flutter_tabler_icons/flutter_tabler_icons.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:ournear_mobile/app/hire_candidate_app.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/auth/auth_service.dart';
import 'package:ournear_mobile/core/auth/auth_user.dart';
import 'package:ournear_mobile/core/navigation/root_tab_shell.dart';
import 'package:ournear_mobile/core/navigation/shell_destination.dart';
import 'package:ournear_mobile/core/theme/ournear_theme.dart';
import 'package:ournear_mobile/features/hire/communication/data/messages_repository.dart';
import 'package:ournear_mobile/features/hire/communication/data/mock_messages_repository.dart';
import 'package:ournear_mobile/features/hire/communication/data/mock_notifications_repository.dart';
import 'package:ournear_mobile/features/hire/communication/data/notifications_repository.dart';
import 'package:ournear_mobile/features/hire/communication/messages_inbox_screen.dart';
import 'package:ournear_mobile/features/hire/communication/models/candidate_conversation.dart';
import 'package:ournear_mobile/features/hire/communication/notifications_screen.dart';
import 'package:ournear_mobile/features/hire/profile/data/profile_repository.dart';
import 'package:ournear_mobile/features/hire/settings/candidate_settings_screen.dart';
import 'package:provider/provider.dart';

void main() {
  setUp(() {
    setHireCandidateTestStorage();
  });

  test(
    'live message and notification repositories do not call success paths',
    () async {
      final messages = LiveMessagesRepository();
      final notifications = LiveNotificationsRepository();
      await expectLater(
        messages.fetchInbox(),
        throwsA(
          isA<MessageRepositoryException>().having(
            (error) => error.message,
            'message',
            LiveMessagesRepository.unavailable,
          ),
        ),
      );
      await expectLater(
        messages.fetchConversation('demo-msg-001'),
        throwsA(isA<MessageRepositoryException>()),
      );
      await expectLater(
        notifications.fetchNotifications(),
        throwsA(
          isA<NotificationRepositoryException>().having(
            (error) => error.message,
            'message',
            LiveNotificationsRepository.unavailable,
          ),
        ),
      );
      await expectLater(
        notifications.markRead('demo-notif-001'),
        throwsA(
          isA<NotificationRepositoryException>().having(
            (error) => error.message,
            'message',
            contains('Nothing was changed'),
          ),
        ),
      );
      await expectLater(
        notifications.dismiss('demo-notif-001'),
        throwsA(isA<NotificationRepositoryException>()),
      );
      expect(
        LiveMessagesRepository.unavailable.contains(
          LiveMessagesRepository.emptyInbox,
        ),
        isFalse,
      );
      expect(
        LiveNotificationsRepository.unavailable.contains(
          LiveNotificationsRepository.emptyInbox,
        ),
        isFalse,
      );
    },
  );

  test('mock mark-read does not clear the unread flag', () async {
    final repository = MockNotificationsRepository();
    final before = await repository.fetchNotifications();
    expect(before.notifications.single.unread, isTrue);
    await expectLater(
      repository.markRead('demo-notif-001'),
      throwsA(isA<NotificationRepositoryException>()),
    );
    final after = await repository.fetchNotifications();
    expect(after.source, NotificationDataSource.mockDemo);
    expect(after.notifications.single.unread, isTrue);
  });

  testWidgets('signed-out messages are not an empty inbox', (tester) async {
    await tester.pumpWidget(const HireCandidateApp());
    await tester.pumpAndSettle();
    await ensureCandidateShellVisible(tester);

    await tester.tap(candidateNavLabel('Applications'));
    await tester.pumpAndSettle();
    await tester.tap(find.byTooltip('Messages'));
    await tester.pumpAndSettle();

    expect(find.text('Messages'), findsWidgets);
    expect(find.text('Sign in to OurNear'), findsWidgets);
    expect(find.text(LiveMessagesRepository.emptyInbox), findsNothing);
    expect(find.textContaining('Demo messages'), findsNothing);
    expect(find.byType(NavigationBar).hitTestable(), findsNothing);

    await tester.tap(find.byTooltip('Back'));
    await tester.pumpAndSettle();
    expect(find.text('Applications'), findsWidgets);
    expect(find.byType(NavigationDestination), findsNWidgets(4));
    expect(
      find.descendant(
        of: find.byType(NavigationBar),
        matching: find.text('Messages'),
      ),
      findsNothing,
    );
    expect(
      find.descendant(
        of: find.byType(NavigationBar),
        matching: find.text('Home'),
      ),
      findsOneWidget,
    );
    expect(
      find.descendant(
        of: find.byType(NavigationBar),
        matching: find.text('Jobs'),
      ),
      findsOneWidget,
    );
    expect(
      find.descendant(
        of: find.byType(NavigationBar),
        matching: find.text('Applications'),
      ),
      findsOneWidget,
    );
    expect(
      find.descendant(
        of: find.byType(NavigationBar),
        matching: find.text('Profile'),
      ),
      findsOneWidget,
    );
  });

  testWidgets('signed-out notifications are not an empty inbox', (
    tester,
  ) async {
    await tester.pumpWidget(
      _host(
        auth: _signedOut(),
        child: NotificationsScreen(
          repository: MockNotificationsRepository(),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Sign in to OurNear'), findsOneWidget);
    expect(find.text('Demo alert text'), findsNothing);
    expect(find.text(LiveNotificationsRepository.emptyInbox), findsNothing);
  });

  testWidgets('live messages stay unavailable and are not an empty inbox', (
    tester,
  ) async {
    await tester.pumpWidget(
      _host(
        auth: _signedIn(),
        messages: LiveMessagesRepository(),
        child: const MessagesInboxScreen(),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text(LiveMessagesRepository.unavailable), findsOneWidget);
    expect(find.text(LiveMessagesRepository.emptyInbox), findsNothing);
    expect(find.textContaining('Demo messages'), findsNothing);

    await tester.tap(find.byKey(const Key('messages-retry')));
    await tester.pumpAndSettle();
    expect(find.text(LiveMessagesRepository.unavailable), findsOneWidget);
    expect(find.text(LiveMessagesRepository.emptyInbox), findsNothing);
  });

  testWidgets('a confirmed empty message list can say the inbox is empty', (
    tester,
  ) async {
    await tester.pumpWidget(
      _host(
        auth: _signedIn(),
        messages: _EmptyLiveMessagesRepository(),
        child: const MessagesInboxScreen(),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text(LiveMessagesRepository.emptyInbox), findsOneWidget);
    expect(find.textContaining('Demo messages'), findsNothing);
  });

  testWidgets('mock messages stay labelled and send cannot succeed', (
    tester,
  ) async {
    await tester.binding.setSurfaceSize(const Size(420, 900));
    addTearDown(() => tester.binding.setSurfaceSize(null));

    await tester.pumpWidget(
      _host(
        auth: _signedIn(),
        messages: MockMessagesRepository(),
        child: const MessagesInboxScreen(),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.textContaining('Demo messages'), findsOneWidget);
    expect(find.text('Unread'), findsOneWidget);
    expect(find.text('Demo conversation'), findsOneWidget);
    expect(find.text(LiveMessagesRepository.emptyInbox), findsNothing);

    await tester.tap(find.text('Demo conversation'));
    await tester.pumpAndSettle();

    expect(find.text('Demo message body only in the thread'), findsOneWidget);
    expect(find.text('Job reference: demo-job'), findsOneWidget);
    expect(find.textContaining('Sending is not connected'), findsOneWidget);
    expect(find.text('Message sent'), findsNothing);
    final send = tester.widget<OutlinedButton>(
      find.byKey(const Key('message-send')),
    );
    expect(send.onPressed, isNull);

    await tester.tap(find.byTooltip('Back'));
    await tester.pumpAndSettle();
    expect(find.text('Demo preview'), findsOneWidget);
  });

  testWidgets('a thread drag does not change the root tab', (tester) async {
    await tester.binding.setSurfaceSize(const Size(420, 900));
    addTearDown(() => tester.binding.setSurfaceSize(null));

    await tester.pumpWidget(
      _host(
        auth: _signedIn(),
        messages: MockMessagesRepository(),
        child: RootTabShell(
          destinations: [
            ShellDestination(
              id: 'home',
              label: 'Home',
              icon: TablerIcons.home_2,
              selectedIcon: TablerIcons.home_2,
              builder: (_) => const MessagesInboxScreen(),
            ),
            ShellDestination(
              id: 'jobs',
              label: 'Jobs',
              icon: TablerIcons.briefcase,
              selectedIcon: TablerIcons.briefcase,
              builder: (_) => const Scaffold(body: Text('Jobs tab')),
            ),
          ],
        ),
      ),
    );
    await tester.pumpAndSettle();

    await tester.tap(find.text('Demo conversation'));
    await tester.pumpAndSettle();
    await tester.drag(
      find.text('Demo message body only in the thread'),
      const Offset(-240, 0),
    );
    await tester.pumpAndSettle();

    expect(find.text('Demo message body only in the thread'), findsOneWidget);
    expect(find.text('Jobs tab'), findsNothing);
    expect(find.byType(NavigationBar).hitTestable(), findsNothing);

    await tester.tap(find.byTooltip('Back'));
    await tester.pumpAndSettle();
    expect(
      tester.widget<NavigationBar>(find.byType(NavigationBar)).selectedIndex,
      0,
    );
    expect(find.byType(NavigationBar).hitTestable(), findsOneWidget);
  });

  testWidgets(
    'live notifications stay unavailable and actions do not succeed',
    (tester) async {
      await tester.pumpWidget(
        _host(
          auth: _signedIn(),
          notifications: LiveNotificationsRepository(),
          child: const NotificationsScreen(),
        ),
      );
      await tester.pumpAndSettle();

      expect(
        find.text(LiveNotificationsRepository.unavailable),
        findsOneWidget,
      );
      expect(find.text(LiveNotificationsRepository.emptyInbox), findsNothing);
      expect(find.textContaining('Demo notifications'), findsNothing);

      await tester.tap(find.byKey(const Key('notification-mark-read')));
      await tester.pumpAndSettle();
      expect(
        find.text(LiveNotificationsRepository.markReadUnavailable),
        findsOneWidget,
      );
      expect(find.textContaining('Marked read'), findsNothing);

      await tester.tap(find.byKey(const Key('notification-dismiss')));
      await tester.pumpAndSettle();
      expect(
        find.text(LiveNotificationsRepository.markReadUnavailable),
        findsWidgets,
      );
      expect(find.textContaining('dismissed'), findsNothing);
    },
  );

  testWidgets('mock notifications stay unread after mark read', (tester) async {
    await tester.pumpWidget(
      _host(
        auth: _signedIn(),
        notifications: MockNotificationsRepository(),
        child: const NotificationsScreen(),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.textContaining('Demo notifications'), findsOneWidget);
    expect(find.text('Unread'), findsOneWidget);
    expect(find.text(LiveNotificationsRepository.emptyInbox), findsNothing);

    await tester.tap(find.byKey(const Key('notification-mark-read')));
    await tester.pumpAndSettle();
    expect(find.text('Unread'), findsOneWidget);
    expect(
      find.text(LiveNotificationsRepository.markReadUnavailable),
      findsOneWidget,
    );
  });

  testWidgets('settings use the account name and do not save unavailable items', (
    tester,
  ) async {
    await tester.binding.setSurfaceSize(const Size(420, 1400));
    addTearDown(() => tester.binding.setSurfaceSize(null));

    final auth = _signedIn();
    await tester.pumpWidget(
      _host(
        auth: auth,
        profiles: LiveProfileRepository(),
        child: const CandidateSettingsScreen(),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Asha'), findsOneWidget);
    expect(find.text('@asha'), findsOneWidget);
    expect(find.byType(Switch), findsNothing);
    expect(find.text('Delete account'), findsNothing);
    expect(find.text('Reset password'), findsNothing);

    await tester.tap(
      find.byKey(const Key('settings-Notification preferences')),
    );
    await tester.pumpAndSettle();
    expect(
      find.text(
        'Notification preferences are not connected. No channels were changed.',
      ),
      findsOneWidget,
    );
    await tester.tap(find.byTooltip('Back'));
    await tester.pumpAndSettle();

    await tester.tap(find.byKey(const Key('settings-Job preferences')));
    await tester.pumpAndSettle();
    await tester.tap(find.byKey(const Key('open-job-preferences')));
    await tester.pumpAndSettle();
    await tester.enterText(
      find.byKey(const Key('profile-primary-field')),
      'Ward shifts',
    );
    await tester.tap(find.byKey(const Key('profile-save')));
    await tester.pumpAndSettle();
    expect(find.textContaining('Nothing was saved'), findsOneWidget);
    expect(find.text('Saved on OurNear.'), findsNothing);

    await tester.tap(find.byTooltip('Back'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Discard'));
    await tester.pumpAndSettle();
    await tester.tap(find.byTooltip('Back'));
    await tester.pumpAndSettle();

    await tester.tap(find.byKey(const Key('settings-Help and support')));
    await tester.pumpAndSettle();
    expect(find.textContaining('No support service is named'), findsOneWidget);
    await tester.tap(find.byTooltip('Back'));
    await tester.pumpAndSettle();

    await tester.ensureVisible(
      find.byKey(const Key('settings-Privacy and account')),
    );
    await tester.tap(find.byKey(const Key('settings-Privacy and account')));
    await tester.pumpAndSettle();
    expect(find.text('Account deletion is not available.'), findsOneWidget);
    expect(find.text('Asha'), findsOneWidget);
    await tester.tap(find.text('Sign out'));
    await tester.pumpAndSettle();

    expect(auth.status, AuthStatus.unauthenticated);
    expect(auth.user, isNull);
    expect(find.text('Asha'), findsNothing);
    expect(
      find.textContaining('Sign in to view account settings'),
      findsOneWidget,
    );
  });

  testWidgets('signed-out settings hide the previous account', (tester) async {
    await tester.pumpWidget(
      _host(auth: _signedOut(), child: const CandidateSettingsScreen()),
    );
    await tester.pumpAndSettle();

    expect(find.text('Asha'), findsNothing);
    expect(
      find.textContaining('No account details are shown before sign-in'),
      findsOneWidget,
    );
  });
}

AuthController _signedIn() {
  final auth = AuthController(AuthService());
  auth.status = AuthStatus.authenticated;
  auth.user = const AuthUser(
    name: 'Asha',
    username: 'asha',
    role: 'candidate',
    emailVerified: true,
    mustChangePassword: false,
  );
  return auth;
}

AuthController _signedOut() {
  final auth = AuthController(AuthService());
  auth.status = AuthStatus.unauthenticated;
  return auth;
}

Widget _host({
  required AuthController auth,
  required Widget child,
  MessagesRepository? messages,
  NotificationsRepository? notifications,
  ProfileRepository? profiles,
}) {
  return MultiProvider(
    providers: [
      ChangeNotifierProvider<AuthController>.value(value: auth),
      Provider<MessagesRepository>(
        create: (_) => messages ?? LiveMessagesRepository(),
      ),
      Provider<NotificationsRepository>(
        create: (_) => notifications ?? LiveNotificationsRepository(),
      ),
      Provider<ProfileRepository>(
        create: (_) => profiles ?? LiveProfileRepository(),
      ),
    ],
    child: MaterialApp(theme: OurNearTheme.hireLight(), home: child),
  );
}

class _EmptyLiveMessagesRepository implements MessagesRepository {
  @override
  Future<MessagesSnapshot> fetchInbox() async {
    return const MessagesSnapshot(
      conversations: [],
      source: MessageDataSource.liveApi,
    );
  }

  @override
  Future<ConversationView> fetchConversation(String id) async {
    return const ConversationView(
      conversation: CandidateConversation(
        id: 'empty',
        title: 'Empty',
        preview: '',
        unread: false,
        messages: [],
      ),
      source: MessageDataSource.liveApi,
    );
  }

  @override
  Future<ConversationView> sendMessage(String conversationId, String body) {
    return fetchConversation(conversationId);
  }
}
