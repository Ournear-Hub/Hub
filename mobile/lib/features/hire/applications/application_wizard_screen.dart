import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/api/models/public_job.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/providers/candidate_repository_resolver.dart';
import 'package:ournear_mobile/core/auth/auth_user.dart';
import 'package:ournear_mobile/features/auth/login_screen.dart';
import 'package:ournear_mobile/features/hire/applications/data/applications_repository.dart';
import 'package:ournear_mobile/features/hire/applications/widgets/applications_demo_banner.dart';
import 'package:ournear_mobile/features/hire/profile/profile_readiness_note.dart';
import 'package:provider/provider.dart';

/// Account, resume gap, and review. Job questions are omitted because none are supplied.
class ApplicationWizardScreen extends StatefulWidget {
  const ApplicationWizardScreen({
    super.key,
    required this.job,
    this.repository,
  });

  final PublicJobDetail job;
  final ApplicationsRepository? repository;

  @override
  State<ApplicationWizardScreen> createState() =>
      _ApplicationWizardScreenState();
}

class _ApplicationWizardScreenState extends State<ApplicationWizardScreen> {
  static const _stepCount = 3;

  var _step = 0;
  var _submitting = false;
  var _submitted = false;
  String? _accountError;
  String? _submitError;
  ApplicationSubmission? _submission;

  ApplicationsRepository _repository() {
    return CandidateRepositoryResolver.applications(
      context,
      injected: widget.repository,
    );
  }

  AuthUser? _signedInUser() {
    try {
      final auth = context.read<AuthController>();
      if (auth.status == AuthStatus.authenticated) {
        return auth.user;
      }
    } on ProviderNotFoundException {
      return null;
    }
    return null;
  }

  void _next() {
    if (_step == 0 && _signedInUser() == null) {
      setState(() {
        _accountError =
            'Sign in before continuing. This app will not collect a stand-in profile.';
      });
      return;
    }
    setState(() {
      _accountError = null;
      if (_step < _stepCount - 1) {
        _step += 1;
      }
    });
  }

  void _back() {
    if (_step == 0 || _submitting) {
      return;
    }
    setState(() => _step -= 1);
  }

  Future<void> _submit() async {
    if (_submitting || _submitted) {
      return;
    }
    final user = _signedInUser();
    if (user == null) {
      setState(() {
        _submitError =
            'Sign in before continuing. This app will not collect a stand-in profile.';
      });
      return;
    }
    setState(() {
      _submitting = true;
      _submitError = null;
    });
    try {
      final result = await _repository().submitApplication(
        ApplicationDraft(
          jobSlug: widget.job.slug,
          jobTitle: widget.job.title,
          companyName: widget.job.companyName ?? '',
          location: widget.job.location,
        ),
      );
      if (!mounted) {
        return;
      }
      final demoRecord =
          result.source == ApplicationsDataSource.mockDemo &&
          !result.confirmedByServer;
      if (!result.confirmedByServer && !demoRecord) {
        setState(() {
          _submitError =
              'The server did not confirm this application. Nothing was recorded as sent.';
        });
        return;
      }
      setState(() {
        _submission = result;
        _submitted = true;
      });
    } on ApplicationsRepositoryException catch (error) {
      if (!mounted) {
        return;
      }
      setState(() {
        _submitError = error.kind == ApplicationsFailureKind.verificationRequired
            ? '${error.message} Use Settings → Verification to resend the email link.'
            : error.message;
      });
    } finally {
      if (mounted) {
        setState(() => _submitting = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final user = _watchUser();
    return PopScope(
      canPop: _step == 0 && !_submitting,
      onPopInvokedWithResult: (didPop, result) {
        if (!didPop && _step > 0 && !_submitting) {
          setState(() => _step -= 1);
        }
      },
      child: Scaffold(
        appBar: AppBar(title: const Text('Apply')),
        body: ListView(
          key: const Key('application-wizard'),
          padding: const EdgeInsets.all(16),
          children: [
            Text(
              'Step ${_step + 1} of $_stepCount',
              key: const Key('wizard-progress'),
            ),
            const SizedBox(height: 8),
            LinearProgressIndicator(value: (_step + 1) / _stepCount),
            const SizedBox(height: 16),
            Text(
              widget.job.title,
              style: Theme.of(context).textTheme.titleMedium,
            ),
            const SizedBox(height: 16),
            if (_step == 0) ..._accountStep(context, user),
            if (_step == 1) ..._resumeStep(),
            if (_step == 2) ..._reviewStep(user),
            const SizedBox(height: 20),
            Row(
              children: [
                if (_step > 0)
                  OutlinedButton(
                    key: const Key('wizard-back'),
                    style: OutlinedButton.styleFrom(
                      minimumSize: const Size(48, 48),
                    ),
                    onPressed: _submitting ? null : _back,
                    child: const Text('Back'),
                  ),
                if (_step > 0) const SizedBox(width: 12),
                if (_step < _stepCount - 1)
                  FilledButton(
                    key: const Key('wizard-next'),
                    style: FilledButton.styleFrom(
                      minimumSize: const Size(48, 48),
                    ),
                    onPressed: _next,
                    child: const Text('Next'),
                  ),
                if (_step == _stepCount - 1)
                  FilledButton(
                    key: const Key('wizard-submit'),
                    style: FilledButton.styleFrom(
                      minimumSize: const Size(48, 48),
                    ),
                    onPressed: (_submitting || _submitted) ? null : _submit,
                    child: Text(
                      _submitting
                          ? 'Please wait'
                          : _submitted
                          ? 'Recorded'
                          : 'Submit application',
                    ),
                  ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  bool _authAvailable() {
    try {
      context.read<AuthController>();
      return true;
    } on ProviderNotFoundException {
      return false;
    }
  }

  AuthUser? _watchUser() {
    try {
      final auth = context.watch<AuthController>();
      if (auth.status == AuthStatus.authenticated) {
        return auth.user;
      }
    } on ProviderNotFoundException {
      return null;
    }
    return null;
  }

  List<Widget> _accountStep(BuildContext context, AuthUser? user) {
    return [
      Text('Account', style: Theme.of(context).textTheme.titleMedium),
      const SizedBox(height: 8),
      if (user == null) ...[
        const Text(
          'You are not signed in. This step uses the signed-in account and does not ask for a separate profile.',
        ),
        if (_authAvailable()) ...[
          const SizedBox(height: 12),
          OutlinedButton(
            style: OutlinedButton.styleFrom(minimumSize: const Size(48, 48)),
            onPressed: () {
              Navigator.of(context).push(
                MaterialPageRoute<void>(builder: (_) => const LoginScreen()),
              );
            },
            child: const Text('Sign in'),
          ),
        ],
      ] else ...[
        Text('Name: ${user.name}'),
        const SizedBox(height: 8),
        Text('Username: ${user.username}'),
        if (user.mustChangePassword) ...[
          const SizedBox(height: 8),
          const Text(
            'This account is marked to change its password. This app has no password-change screen.',
          ),
        ],
        if (!user.emailVerified) ...[
          const SizedBox(height: 8),
          Text(
            'Email not verified. OurNear may reject applications until you verify. '
            'Open Settings → Verification to resend the link.',
            key: const Key('wizard-email-unverified'),
          ),
        ],
      ],
      if (_accountError != null) ...[
        const SizedBox(height: 12),
        Text(_accountError!, key: const Key('wizard-account-error')),
      ],
      const ProfileReadinessNote(),
    ];
  }

  List<Widget> _resumeStep() {
    return const [
      Text('Resume'),
      SizedBox(height: 8),
      Text(
        'OurNear uses your primary resume from your profile library when you submit. '
        'This wizard does not send a separate file in the apply request.',
      ),
    ];
  }

  List<Widget> _reviewStep(AuthUser? user) {
    final submission = _submission;
    return [
      Text('Review', style: Theme.of(context).textTheme.titleMedium),
      const SizedBox(height: 8),
      Text('Job: ${widget.job.title}'),
      if (_filled(widget.job.companyName))
        Text('Company: ${widget.job.companyName}'),
      Text(
        user == null
            ? 'Account: not signed in'
            : 'Account: ${user.name} (${user.username})',
      ),
      const Text('Resume: not attached'),
      const Text(
        'No job questions were provided. This app does not call a questions endpoint.',
      ),
      const SizedBox(height: 8),
      Text(_availability),
      if (_submitError != null) ...[
        const SizedBox(height: 12),
        Text(_submitError!, key: const Key('wizard-submit-error')),
      ],
      if (submission != null) ...[
        const SizedBox(height: 12),
        if (submission.source == ApplicationsDataSource.mockDemo &&
            !submission.confirmedByServer) ...[
          const ApplicationsDemoBanner(),
          const SizedBox(height: 8),
          const Text(
            'Demo record only. Nothing was sent to OurNear.',
            key: Key('wizard-demo-confirmation'),
          ),
        ],
        if (submission.confirmedByServer)
          const Text(
            'The server confirmed this application.',
            key: Key('wizard-server-confirmation'),
          ),
      ],
    ];
  }

  String get _availability {
    final job = widget.job;
    if (job.isClosed || job.acceptsApplications == false) {
      return 'This job is closed or is not accepting applications.';
    }
    if (job.acceptsApplications == true) {
      return 'This published job says it is accepting applications.';
    }
    return 'This job does not say whether it accepts applications.';
  }

  bool _filled(String? value) => value != null && value.trim().isNotEmpty;
}
