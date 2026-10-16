import 'package:flutter/material.dart';
import 'package:flutter_tabler_icons/flutter_tabler_icons.dart';
import 'package:ournear_mobile/core/navigation/root_tab_shell.dart';
import 'package:ournear_mobile/core/navigation/shell_destination.dart';
import 'package:ournear_mobile/features/drops/drops_screen.dart';
import 'package:ournear_mobile/features/hire/hire_screen.dart';
import 'package:ournear_mobile/features/home/home_screen.dart';
import 'package:ournear_mobile/features/profile/profile_screen.dart';
import 'package:ournear_mobile/features/vibes/vibes_screen.dart';

enum AppTab { home, vibes, drops, hire, profile }

class AppShell extends StatefulWidget {
  const AppShell({super.key, this.initialTab = AppTab.home});

  final AppTab initialTab;

  @override
  State<AppShell> createState() => AppShellState();
}

class AppShellState extends State<AppShell> {
  final GlobalKey<RootTabShellState> _shellKey = GlobalKey<RootTabShellState>();

  static const _destinations = <ShellDestination>[
    ShellDestination(
      id: 'home',
      label: 'Home',
      icon: TablerIcons.home_2,
      selectedIcon: TablerIcons.home_2,
      builder: _buildHome,
    ),
    ShellDestination(
      id: 'vibes',
      label: 'Vibes',
      icon: TablerIcons.flame,
      selectedIcon: TablerIcons.flame,
      builder: _buildVibes,
    ),
    ShellDestination(
      id: 'drops',
      label: 'Drops',
      icon: TablerIcons.droplet,
      selectedIcon: TablerIcons.droplet,
      builder: _buildDrops,
    ),
    ShellDestination(
      id: 'hire',
      label: 'Hire',
      icon: TablerIcons.briefcase,
      selectedIcon: TablerIcons.briefcase,
      builder: _buildHire,
    ),
    ShellDestination(
      id: 'profile',
      label: 'Profile',
      icon: TablerIcons.user_circle,
      selectedIcon: TablerIcons.user_circle,
      builder: _buildProfile,
    ),
  ];

  static Widget _buildHome(BuildContext context) {
    final shell = context.findAncestorStateOfType<AppShellState>();
    return HomeScreen(onOpenModule: shell?._openModuleFromHome ?? (_) {});
  }

  static Widget _buildVibes(BuildContext context) => const VibesScreen();

  static Widget _buildDrops(BuildContext context) => const DropsScreen();

  static Widget _buildHire(BuildContext context) => const HireScreen();

  static Widget _buildProfile(BuildContext context) => const ProfileScreen();

  void goToTab(AppTab tab) {
    _shellKey.currentState?.goToIndex(tab.index);
  }

  void _openModuleFromHome(AppTab tab) {
    goToTab(tab);
  }

  @override
  Widget build(BuildContext context) {
    return RootTabShell(
      key: _shellKey,
      initialIndex: widget.initialTab.index,
      destinations: _destinations,
    );
  }
}
