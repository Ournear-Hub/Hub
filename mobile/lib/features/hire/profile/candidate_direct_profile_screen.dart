import 'package:flutter/material.dart';
import 'package:flutter_tabler_icons/flutter_tabler_icons.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/providers/candidate_repository_resolver.dart';
import 'package:ournear_mobile/core/theme/hire_theme_context.dart';
import 'package:ournear_mobile/features/auth/login_screen.dart';
import 'package:ournear_mobile/features/hire/communication/communication_routes.dart';
import 'package:ournear_mobile/features/hire/profile/data/profile_repository.dart';
import 'package:ournear_mobile/features/hire/profile/professional_profile_body.dart';
import 'package:ournear_mobile/features/hire/profile/profile_setup/guided_profile_setup_screen.dart';
import 'package:ournear_mobile/features/hire/profile/profile_setup/profile_setup_entry_request.dart';
import 'package:ournear_mobile/features/hire/shell/candidate_shell_scope.dart';
import 'package:provider/provider.dart';

/// Profile tab: direct professional profile view.
class CandidateDirectProfileScreen extends StatefulWidget {
  const CandidateDirectProfileScreen({
    super.key,
    this.profileRepository,
  });

  final ProfileRepository? profileRepository;

  @override
  State<CandidateDirectProfileScreen> createState() =>
      _CandidateDirectProfileScreenState();
}

class _CandidateDirectProfileScreenState
    extends State<CandidateDirectProfileScreen> {
  ProfileSnapshot? _snapshot;
  ProfileRepositoryException? _error;
  var _loading = false;
  var _requested = false;
  var _checkedSetupEntry = false;

  ProfileRepository _profiles() {
    return CandidateRepositoryResolver.profile(
      context,
      injected: widget.profileRepository,
    );
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final snapshot = await _profiles().fetchProfile();
      if (!mounted) {
        return;
      }
      setState(() {
        _snapshot = snapshot;
        _loading = false;
      });
    } on ProfileRepositoryException catch (error) {
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

  void _openEdit() {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => GuidedProfileSetupScreen(
          profileRepository: _profiles(),
        ),
      ),
    ).then((_) {
      if (mounted && context.read<AuthController>().status ==
          AuthStatus.authenticated) {
        _load();
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthController>();
    final signedIn =
        auth.status == AuthStatus.authenticated && auth.user != null;

    if (!_checkedSetupEntry && ProfileSetupEntryRequest.consume()) {
      _checkedSetupEntry = true;
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted && signedIn) {
          _openEdit();
        }
      });
    }

    if (signedIn && !_requested) {
      _requested = true;
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) {
          _load();
        }
      });
    }
    if (!signedIn) {
      _requested = false;
    }

    return Scaffold(
      backgroundColor: context.hireCanvas,
      appBar: AppBar(
        title: const Text('Profile'),
        actions: [
          IconButton(
            tooltip: 'Notifications',
            icon: const Icon(TablerIcons.bell),
            onPressed: () => openCandidateNotifications(context),
          ),
          if (CandidateShellScope.menuEnabledFor(context))
            IconButton(
              key: const Key('candidate-menu-button'),
              tooltip: 'Menu',
              icon: const Icon(TablerIcons.menu_2),
              onPressed: CandidateShellScope.maybeOpenMenu(context),
            ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: signedIn ? _load : () async {},
        child: signedIn
            ? Stack(
                children: [
                  ProfessionalProfileBody(
                    snapshot: _snapshot,
                    failure: _error,
                    onEditProfile: _openEdit,
                    profileRepository: _profiles(),
                    onProfileUpdated: _load,
                  ),
                  if (_loading)
                    const LinearProgressIndicator(minHeight: 2),
                ],
              )
            : ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16),
                children: [
                  const Text(
                    'Sign in to view and edit your professional profile.',
                  ),
                  const SizedBox(height: 12),
                  FilledButton(
                    onPressed: auth.busy
                        ? null
                        : () {
                            Navigator.of(context).push(
                              MaterialPageRoute<void>(
                                builder: (_) => const LoginScreen(),
                              ),
                            );
                          },
                    child: const Text('Sign in'),
                  ),
                ],
              ),
      ),
    );
  }
}
