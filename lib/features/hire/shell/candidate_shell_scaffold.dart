import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/navigation/root_tab_shell.dart';
import 'package:ournear_mobile/core/navigation/shell_destination.dart';
import 'package:ournear_mobile/features/hire/shell/candidate_app_menu.dart';
import 'package:ournear_mobile/features/hire/shell/candidate_shell_scope.dart';

/// Candidate root shell with end drawer menu. Four tabs remain in [RootTabShell].
class CandidateShellScaffold extends StatefulWidget {
  const CandidateShellScaffold({super.key, required this.destinations});

  final List<ShellDestination> destinations;

  @override
  State<CandidateShellScaffold> createState() => _CandidateShellScaffoldState();
}

class _CandidateShellScaffoldState extends State<CandidateShellScaffold> {
  final _scaffoldKey = GlobalKey<ScaffoldState>();
  final _shellKey = GlobalKey<RootTabShellState>();
  var _activeTabId = 'home';

  bool get _menuEnabled => _activeTabId == 'profile';

  void _openMenu() {
    if (!_menuEnabled) {
      return;
    }
    _scaffoldKey.currentState?.openEndDrawer();
  }

  void _closeMenu() {
    final scaffold = _scaffoldKey.currentState;
    if (scaffold?.isEndDrawerOpen ?? false) {
      Navigator.of(context).pop();
    }
  }

  void _onTabIndexChanged(int index) {
    final nextId = widget.destinations[index].id;
    if (_activeTabId == 'profile' && nextId != 'profile') {
      final scaffold = _scaffoldKey.currentState;
      if (scaffold?.isEndDrawerOpen ?? false) {
        _closeMenu();
      }
    }
    if (_activeTabId != nextId) {
      setState(() => _activeTabId = nextId);
    }
  }

  @override
  Widget build(BuildContext context) {
    return CandidateShellScope(
      openMenu: _openMenu,
      menuEnabled: _menuEnabled,
      child: Scaffold(
        key: _scaffoldKey,
        endDrawer: Drawer(
          key: const Key('candidate-app-menu'),
          child: CandidateAppMenu(
            onClose: _closeMenu,
            onNavigateTab: (id) {
              _closeMenu();
              _shellKey.currentState?.goToId(id);
            },
            onViewProfile: () {
              _closeMenu();
              _shellKey.currentState?.goToId('profile');
            },
          ),
        ),
        body: RootTabShell(
          key: _shellKey,
          destinations: widget.destinations,
          onIndexChanged: _onTabIndexChanged,
        ),
      ),
    );
  }
}
