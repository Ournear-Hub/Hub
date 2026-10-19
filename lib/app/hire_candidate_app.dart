import 'package:flutter/material.dart';
import 'package:ournear_mobile/app/hire_app_scaffold.dart';
import 'package:ournear_mobile/core/app_config/hire_app_config.dart';
import 'package:ournear_mobile/core/api/hire_api_service.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/auth/auth_service.dart';
import 'package:ournear_mobile/features/auth/data/account_access_repository.dart';
import 'package:ournear_mobile/features/hire/applications/data/applications_repository.dart';
import 'package:ournear_mobile/features/hire/profile/data/documents_repository.dart';
import 'package:ournear_mobile/features/hire/profile/data/profile_repository.dart';
import 'package:ournear_mobile/features/hire/communication/data/messages_repository.dart';
import 'package:ournear_mobile/features/hire/communication/data/notifications_repository.dart';
import 'package:ournear_mobile/features/hire/jobs/data/jobs_repository.dart';
import 'package:ournear_mobile/features/hire/jobs/jobs_discovery_controller.dart';
import 'package:ournear_mobile/core/branding/hire_branding_controller.dart';
import 'package:ournear_mobile/core/branding/hire_branding_service.dart';
import 'package:ournear_mobile/core/theme/candidate_theme_controller.dart';
import 'package:provider/provider.dart';

/// Entry widget for the ON Hire candidate app shell.
class HireCandidateApp extends StatelessWidget {
  const HireCandidateApp({
    super.key,
    this.authService,
    this.apiService,
    this.jobsRepository,
    this.applicationsRepository,
    this.profileRepository,
    this.documentsRepository,
    this.messagesRepository,
    this.notificationsRepository,
  });

  final AuthService? authService;
  final HireApiService? apiService;
  final JobsRepository? jobsRepository;
  final ApplicationsRepository? applicationsRepository;
  final ProfileRepository? profileRepository;
  final DocumentsRepository? documentsRepository;
  final MessagesRepository? messagesRepository;
  final NotificationsRepository? notificationsRepository;

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        Provider<AuthService>(
          create: (_) => authService ?? AuthService(),
        ),
        Provider<HireApiService>(
          create: (context) =>
              apiService ??
              HireApiService(authService: context.read<AuthService>()),
        ),
        ChangeNotifierProvider(
          create: (context) =>
              AuthController(context.read<AuthService>())..bootstrap(),
        ),
        ChangeNotifierProvider(create: (_) => JobsDiscoveryController()),
        Provider<JobsRepository>(
          create: (context) =>
              jobsRepository ??
              LiveJobsRepository(service: context.read<HireApiService>()),
        ),
        Provider<ApplicationsRepository>(
          create: (context) =>
              applicationsRepository ??
              LiveApplicationsRepository(api: context.read<HireApiService>()),
        ),
        Provider<ProfileRepository>(
          create: (context) =>
              profileRepository ??
              LiveProfileRepository(api: context.read<HireApiService>()),
        ),
        Provider<DocumentsRepository>(
          create: (context) =>
              documentsRepository ??
              LiveDocumentsRepository(api: context.read<HireApiService>()),
        ),
        Provider<MessagesRepository>(
          create: (context) =>
              messagesRepository ??
              LiveMessagesRepository(api: context.read<HireApiService>()),
        ),
        Provider<NotificationsRepository>(
          create: (context) =>
              notificationsRepository ??
              LiveNotificationsRepository(api: context.read<HireApiService>()),
        ),
        Provider<AccountAccessRepository>(
          create: (context) => LiveAccountAccessRepository(
            authService: context.read<AuthService>(),
          ),
        ),
        ChangeNotifierProvider(
          create: (_) => CandidateThemeController()..load(),
        ),
        ChangeNotifierProvider(
          create: (context) => HireBrandingController(
            service: HireBrandingService(
              apiService: context.read<HireApiService>(),
            ),
          )..load(),
        ),
      ],
      child: HireAppScaffold(config: HireAppConfigs.candidate),
    );
  }
}
