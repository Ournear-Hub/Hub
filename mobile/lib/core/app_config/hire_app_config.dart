import 'package:flutter/material.dart';
import 'package:flutter_tabler_icons/flutter_tabler_icons.dart';
import 'package:ournear_mobile/core/app_config/hire_feature_flags.dart';
import 'package:ournear_mobile/core/navigation/shell_destination.dart';
import 'package:ournear_mobile/core/widgets/ournear_feedback.dart';
import 'package:ournear_mobile/features/hire/applications/candidate_applications_screen.dart';
import 'package:ournear_mobile/features/hire/hire_screen.dart';
import 'package:ournear_mobile/features/hire/home/candidate_home_screen.dart';
import 'package:ournear_mobile/features/hire/shell/hire_shell_placeholder_tab.dart';
import 'package:ournear_mobile/features/hire/profile/candidate_direct_profile_screen.dart';
import 'package:ournear_mobile/features/profile/profile_screen.dart';

enum HireAppVariant { candidate, employer }

class HireAppConfig {
  const HireAppConfig({
    required this.variant,
    required this.displayName,
    required this.rootDestinations,
    required this.enabledFeatures,
  });

  final HireAppVariant variant;
  final String displayName;
  final List<ShellDestination> rootDestinations;
  final Set<HireFeatureFlag> enabledFeatures;

  bool isEnabled(HireFeatureFlag flag) => enabledFeatures.contains(flag);
}

abstract final class HireAppConfigs {
  static void _plannedSnack(BuildContext context, String feature) {
    showOurNearSnackBar(
      context,
      '$feature will connect to OurNear Laravel in a later phase — not available in this foundation build.',
    );
  }

  static final candidate = HireAppConfig(
    variant: HireAppVariant.candidate,
    displayName: 'OurNear Hire',
    enabledFeatures: {HireFeatureFlag.publicJobBrowse},
    rootDestinations: [
      ShellDestination(
        id: 'home',
        label: 'Home',
        icon: TablerIcons.home_2,
        selectedIcon: TablerIcons.home_2,
        builder: (_) => const CandidateHomeScreen(),
      ),
      ShellDestination(
        id: 'jobs',
        label: 'Jobs',
        icon: TablerIcons.briefcase,
        selectedIcon: TablerIcons.briefcase,
        builder: (_) =>
            const HireScreen(appBarTitle: 'Jobs', showDiscoveryControls: true),
      ),
      ShellDestination(
        id: 'applications',
        label: 'Applications',
        icon: TablerIcons.file_description,
        selectedIcon: TablerIcons.file_description,
        builder: (_) => const CandidateApplicationsScreen(),
      ),
      ShellDestination(
        id: 'profile',
        label: 'Profile',
        icon: TablerIcons.user_circle,
        selectedIcon: TablerIcons.user_circle,
        builder: (_) => const CandidateDirectProfileScreen(),
      ),
    ],
  );

  static final employer = HireAppConfig(
    variant: HireAppVariant.employer,
    displayName: 'ON Hire: Employer',
    enabledFeatures: {
      HireFeatureFlag.employerJobManagement,
      HireFeatureFlag.employerApplicantReview,
    },
    rootDestinations: [
      ShellDestination(
        id: 'home',
        label: 'Home',
        icon: TablerIcons.home_2,
        selectedIcon: TablerIcons.home_2,
        builder: (context) => HireShellPlaceholderTab(
          title: 'Home',
          icon: TablerIcons.home_2,
          message:
              'Employer home will surface hiring activity once Laravel employer APIs are integrated. No fabricated metrics are displayed.',
          onMessagesTap: () => _plannedSnack(context, 'Messages'),
          onNotificationsTap: () => _plannedSnack(context, 'Notifications'),
        ),
      ),
      ShellDestination(
        id: 'my_jobs',
        label: 'My Jobs',
        icon: TablerIcons.briefcase,
        selectedIcon: TablerIcons.briefcase,
        builder: (context) => HireShellPlaceholderTab(
          title: 'My Jobs',
          icon: TablerIcons.briefcase,
          message:
              'Create and manage job posts after employer job APIs are available.',
          onMessagesTap: () => _plannedSnack(context, 'Messages'),
          onNotificationsTap: () => _plannedSnack(context, 'Notifications'),
        ),
      ),
      ShellDestination(
        id: 'applicants',
        label: 'Applicants',
        icon: TablerIcons.users,
        selectedIcon: TablerIcons.users,
        builder: (context) => HireShellPlaceholderTab(
          title: 'Applicants',
          icon: TablerIcons.users,
          message:
              'Review applicants per job once employer review APIs are connected.',
          onMessagesTap: () => _plannedSnack(context, 'Messages'),
          onNotificationsTap: () => _plannedSnack(context, 'Notifications'),
        ),
      ),
      ShellDestination(
        id: 'profile',
        label: 'Profile',
        icon: TablerIcons.user_circle,
        selectedIcon: TablerIcons.user_circle,
        builder: (_) => const ProfileScreen(),
      ),
    ],
  );
}
