import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/theme/hire_theme_context.dart';
import 'package:ournear_mobile/features/auth/login_screen.dart';
import 'package:ournear_mobile/features/hire/profile/profile_setup/profile_setup_entry_request.dart';

class CandidateOnboardingScreen extends StatefulWidget {
  const CandidateOnboardingScreen({super.key, required this.onFinished});

  final VoidCallback onFinished;

  @override
  State<CandidateOnboardingScreen> createState() =>
      _CandidateOnboardingScreenState();
}

class _CandidateOnboardingScreenState extends State<CandidateOnboardingScreen> {
  final _controller = PageController();
  var _page = 0;

  static const _pages = [
    _OnboardingPage(
      title: 'Discover jobs',
      body:
          'Browse published roles on Home and Jobs. Search uses one keyword when OURNEAR_API_BASE is configured. No account is required to look.',
    ),
    _OnboardingPage(
      title: 'Build your profile',
      body:
          'The Profile tab holds your professional overview and edit forms. Saving to OurNear is not connected until a profile API exists.',
    ),
    _OnboardingPage(
      title: 'Track applications honestly',
      body:
          'You can review readiness steps for a job. Live application history and submission are not connected yet. Nothing is sent without a verified API.',
    ),
  ];

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _finish() {
    widget.onFinished();
  }

  void _skip() {
    _finish();
  }

  void _next() {
    if (_page >= _pages.length - 1) {
      _finish();
      return;
    }
    _controller.nextPage(
      duration: const Duration(milliseconds: 250),
      curve: Curves.easeOut,
    );
  }

  void _back() {
    if (_page == 0) {
      return;
    }
    _controller.previousPage(
      duration: const Duration(milliseconds: 250),
      curve: Curves.easeOut,
    );
  }

  @override
  Widget build(BuildContext context) {
    final last = _page >= _pages.length - 1;
    return PopScope(
      canPop: _page == 0,
      onPopInvokedWithResult: (didPop, result) {
        if (!didPop && _page > 0) {
          _back();
        }
      },
      child: Scaffold(
        backgroundColor: context.hireCanvas,
        appBar: AppBar(
          title: const Text('Welcome to OurNear Hire'),
          actions: [
            TextButton(
              key: const Key('onboarding-skip'),
              onPressed: _skip,
              child: const Text('Skip'),
            ),
          ],
        ),
        body: Column(
          children: [
            Expanded(
              child: PageView.builder(
                controller: _controller,
                onPageChanged: (index) => setState(() => _page = index),
                itemCount: _pages.length,
                itemBuilder: (context, index) {
                  final page = _pages[index];
                  return Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          page.title,
                          style: Theme.of(context).textTheme.headlineSmall,
                        ),
                        const SizedBox(height: 16),
                        Text(page.body),
                      ],
                    ),
                  );
                },
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  if (_page > 0)
                    OutlinedButton(
                      key: const Key('onboarding-back'),
                      style: OutlinedButton.styleFrom(
                        minimumSize: const Size.fromHeight(48),
                      ),
                      onPressed: _back,
                      child: const Text('Back'),
                    ),
                  if (_page > 0) const SizedBox(height: 8),
                  FilledButton(
                    key: Key(
                      last ? 'onboarding-get-started' : 'onboarding-next',
                    ),
                    style: FilledButton.styleFrom(
                      minimumSize: const Size.fromHeight(48),
                      backgroundColor: Theme.of(context).colorScheme.primary,
                    ),
                    onPressed: _next,
                    child: Text(last ? 'Get started' : 'Next'),
                  ),
                  const SizedBox(height: 8),
                  if (_page == 1)
                    OutlinedButton(
                      key: const Key('onboarding-setup-profile'),
                      style: OutlinedButton.styleFrom(
                        minimumSize: const Size.fromHeight(48),
                      ),
                      onPressed: () {
                        ProfileSetupEntryRequest.request();
                        _finish();
                      },
                      child: const Text('Set up your profile'),
                    ),
                  if (_page == 1) const SizedBox(height: 8),
                  TextButton(
                    key: const Key('onboarding-sign-in'),
                    onPressed: () {
                      widget.onFinished();
                      Navigator.of(context).push(
                        MaterialPageRoute<void>(
                          builder: (_) => const LoginScreen(),
                        ),
                      );
                    },
                    child: const Text('Sign in to an existing account'),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _OnboardingPage {
  const _OnboardingPage({required this.title, required this.body});

  final String title;
  final String body;
}
