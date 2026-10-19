import 'package:flutter/material.dart';
import 'package:flutter_tabler_icons/flutter_tabler_icons.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/navigation/ournear_motion.dart';
import 'package:ournear_mobile/core/theme/hire_theme_context.dart';
import 'package:ournear_mobile/core/theme/ournear_colors.dart';

/// Branded candidate entry states while session restoration runs or fails.
class CandidateSessionGate extends StatelessWidget {
  const CandidateSessionGate({
    super.key,
    required this.auth,
    required this.onRetry,
    required this.onBrowseJobs,
    required this.child,
    this.forceChecking = false,
  });

  final AuthController auth;
  final VoidCallback onRetry;
  final VoidCallback onBrowseJobs;
  final Widget child;
  final bool forceChecking;

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: auth,
      builder: (context, _) {
        if (forceChecking ||
            auth.status == AuthStatus.unknown ||
            !auth.bootstrapSettled) {
          return const _BrandedGateShell(
            key: Key('session-gate-checking'),
            title: 'OurNear Hire',
            body: _CheckingBody(),
          );
        }

        if (auth.bootstrapFailure != BootstrapFailureKind.none) {
          return _BrandedGateShell(
            key: const Key('session-gate-recoverable-error'),
            title: 'OurNear Hire',
            body: _RecoverableErrorBody(
              auth: auth,
              onRetry: onRetry,
              onBrowseJobs: onBrowseJobs,
            ),
          );
        }

        return child;
      },
    );
  }
}

class _BrandedGateShell extends StatelessWidget {
  const _BrandedGateShell({
    super.key,
    required this.title,
    required this.body,
  });

  final String title;
  final Widget body;

  @override
  Widget build(BuildContext context) {
    final reduced = OurNearMotion.prefersReducedMotion(context);
    final scheme = Theme.of(context).colorScheme;
    return Scaffold(
      backgroundColor: context.hireCanvas,
      body: SafeArea(
        child: Column(
          children: [
            Container(
              width: double.infinity,
              color: scheme.primary,
              padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                      color: scheme.onPrimary,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Candidate',
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      color: scheme.onPrimary.withValues(alpha: 0.88),
                    ),
                  ),
                ],
              ),
            ),
            Expanded(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: reduced
                    ? body
                    : AnimatedSwitcher(
                        duration: const Duration(milliseconds: 200),
                        child: body,
                      ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _CheckingBody extends StatelessWidget {
  const _CheckingBody();

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const CircularProgressIndicator(
            key: Key('session-gate-spinner'),
            color: OurNearColors.hireOrange,
            semanticsLabel: 'Checking your session',
          ),
          const SizedBox(height: 16),
          Text(
            'Checking your session',
            style: Theme.of(context).textTheme.titleMedium,
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 8),
          const Text(
            'Public job browsing stays available while we restore your account.',
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }
}

class _RecoverableErrorBody extends StatelessWidget {
  const _RecoverableErrorBody({
    required this.auth,
    required this.onRetry,
    required this.onBrowseJobs,
  });

  final AuthController auth;
  final VoidCallback onRetry;
  final VoidCallback onBrowseJobs;

  @override
  Widget build(BuildContext context) {
    final network = auth.bootstrapFailure == BootstrapFailureKind.network;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Icon(
          network ? TablerIcons.cloud_off : TablerIcons.alert_circle,
          size: 40,
          color: OurNearColors.hireOrange,
          semanticLabel: network ? 'Network issue' : 'Server issue',
        ),
        const SizedBox(height: 16),
        Text(
          network ? 'Could not reach OurNear' : 'Could not verify your session',
          style: Theme.of(context).textTheme.titleLarge,
        ),
        const SizedBox(height: 8),
        Text(
          auth.bootstrapFailureMessage ??
              'This is not the same as signing out. Retry when you are back online.',
          key: const Key('session-gate-error-message'),
        ),
        if (auth.hasStoredSessionToken) ...[
          const SizedBox(height: 8),
          const Text(
            'A sign-in token is still stored on this device. Retry before signing in again.',
          ),
        ],
        const Spacer(),
        FilledButton.icon(
          key: const Key('session-gate-retry'),
          style: FilledButton.styleFrom(
            minimumSize: const Size.fromHeight(48),
            backgroundColor: OurNearColors.hireTeal,
          ),
          onPressed: auth.busy ? null : onRetry,
          icon: const Icon(TablerIcons.refresh),
          label: Text(auth.busy ? 'Retrying' : 'Retry'),
        ),
        const SizedBox(height: 8),
        OutlinedButton.icon(
          key: const Key('session-gate-browse-jobs'),
          style: OutlinedButton.styleFrom(minimumSize: const Size.fromHeight(48)),
          onPressed: onBrowseJobs,
          icon: const Icon(TablerIcons.briefcase),
          label: const Text('Browse jobs without account'),
        ),
      ],
    );
  }
}
