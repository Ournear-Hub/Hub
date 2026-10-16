import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/app_config/hire_app_config.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/navigation/ournear_motion.dart';
import 'package:ournear_mobile/core/navigation/root_tab_shell.dart';
import 'package:ournear_mobile/core/onboarding/onboarding_preferences.dart';
import 'package:ournear_mobile/core/theme/ournear_theme.dart';
import 'package:ournear_mobile/features/auth/candidate_session_gate.dart';
import 'package:ournear_mobile/features/auth/onboarding/candidate_onboarding_screen.dart';
import 'package:ournear_mobile/core/theme/candidate_theme_controller.dart';
import 'package:ournear_mobile/features/hire/shell/candidate_shell_scaffold.dart';
import 'package:provider/provider.dart';

class HireAppScaffold extends StatelessWidget {
  const HireAppScaffold({super.key, required this.config});

  final HireAppConfig config;

  @override
  Widget build(BuildContext context) {
    CandidateThemeController? themeCtrl;
    if (config.variant == HireAppVariant.candidate) {
      try {
        themeCtrl = context.watch<CandidateThemeController>();
      } on ProviderNotFoundException {
        themeCtrl = null;
      }
    }
    final theme = themeCtrl?.isDark == true
        ? OurNearTheme.hireDark()
        : OurNearTheme.hireLight();

    return MaterialApp(
      title: config.displayName,
      debugShowCheckedModeBanner: false,
      theme: theme,
      builder: (context, child) {
        final content = child ?? const SizedBox.shrink();
        if (!OurNearMotion.prefersReducedMotion(context)) {
          return content;
        }
        return Theme(
          data: Theme.of(context).copyWith(
            pageTransitionsTheme: OurNearMotion.reducedPageTransitions,
          ),
          child: content,
        );
      },
      home: _HireBootstrapGate(config: config),
    );
  }
}

class _HireBootstrapGate extends StatefulWidget {
  const _HireBootstrapGate({required this.config});

  final HireAppConfig config;

  @override
  State<_HireBootstrapGate> createState() => _HireBootstrapGateState();
}

class _HireBootstrapGateState extends State<_HireBootstrapGate> {
  final _onboardingPreferences = OnboardingPreferences();
  var _onboardingLoaded = false;
  var _showOnboarding = false;

  @override
  void initState() {
    super.initState();
    _loadOnboarding();
  }

  Future<void> _loadOnboarding() async {
    if (widget.config.variant != HireAppVariant.candidate) {
      if (mounted) {
        setState(() {
          _onboardingLoaded = true;
          _showOnboarding = false;
        });
      }
      return;
    }
    final complete = await _onboardingPreferences.isComplete();
    if (!mounted) {
      return;
    }
    setState(() {
      _onboardingLoaded = true;
      _showOnboarding = !complete;
    });
  }

  Future<void> _completeOnboarding() async {
    await _onboardingPreferences.markComplete();
    if (!mounted) {
      return;
    }
    setState(() => _showOnboarding = false);
  }

  Widget _shell() {
    if (widget.config.variant == HireAppVariant.candidate) {
      return CandidateShellScaffold(
        destinations: widget.config.rootDestinations,
      );
    }
    return RootTabShell(destinations: widget.config.rootDestinations);
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthController>();
    final isCandidate = widget.config.variant == HireAppVariant.candidate;

    Widget content;
    if (isCandidate &&
        auth.status != AuthStatus.authenticated &&
        auth.bootstrapFailure == BootstrapFailureKind.none &&
        _showOnboarding) {
      content = CandidateOnboardingScreen(onFinished: _completeOnboarding);
    } else {
      content = _shell();
    }

    if (!isCandidate) {
      if (auth.status == AuthStatus.unknown || !auth.bootstrapSettled) {
        return const Scaffold(body: Center(child: CircularProgressIndicator()));
      }
      return content;
    }

    return CandidateSessionGate(
      auth: auth,
      forceChecking: !_onboardingLoaded,
      onRetry: () => auth.retryBootstrap(),
      onBrowseJobs: auth.dismissBootstrapFailureForPublicBrowsing,
      child: content,
    );
  }
}

/// Wraps auth + a typed ON Hire configuration (for tests or custom entry points).
class HireRootApp extends StatelessWidget {
  const HireRootApp({
    super.key,
    required this.config,
    required this.authController,
  });

  final HireAppConfig config;
  final AuthController authController;

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider.value(
      value: authController,
      child: HireAppScaffold(config: config),
    );
  }
}
