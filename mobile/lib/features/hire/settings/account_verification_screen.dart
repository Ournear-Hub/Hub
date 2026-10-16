import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/api/api_config.dart';
import 'package:ournear_mobile/core/api/hire_api_exception.dart';
import 'package:ournear_mobile/core/api/hire_api_service.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/theme/hire_theme_context.dart';
import 'package:provider/provider.dart';

/// Email verification status and resend via authorized mobile JSON APIs.
class AccountVerificationScreen extends StatefulWidget {
  const AccountVerificationScreen({super.key});

  @override
  State<AccountVerificationScreen> createState() =>
      _AccountVerificationScreenState();
}

class _AccountVerificationScreenState extends State<AccountVerificationScreen> {
  var _busy = false;
  String? _message;

  HireApiService _api() {
    try {
      return context.read<HireApiService>();
    } on ProviderNotFoundException {
      return HireApiService();
    }
  }

  Future<void> _resend() async {
    if (_busy || !ApiConfig.hasApiBase) {
      return;
    }
    setState(() {
      _busy = true;
      _message = null;
    });
    try {
      await _api().resendEmailVerification();
      if (!mounted) {
        return;
      }
      setState(() {
        _message =
            'A verification link has been sent to your email address when an account exists.';
      });
      await context.read<AuthController>().refreshAccount();
    } on HireApiException catch (error) {
      if (!mounted) {
        return;
      }
      setState(() => _message = error.message);
    } finally {
      if (mounted) {
        setState(() => _busy = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthController>();
    final user =
        auth.status == AuthStatus.authenticated ? auth.user : null;

    return Scaffold(
      backgroundColor: context.hireCanvas,
      appBar: AppBar(title: const Text('Verification')),
      body: ListView(
        key: const Key('settings-verification'),
        padding: const EdgeInsets.all(16),
        children: [
          if (user == null) ...[
            const Text(
              'Sign in to view verification status from your OurNear account.',
            ),
          ] else ...[
            Text(
              user.emailVerified ? 'Email verified' : 'Email not verified',
              style: Theme.of(context).textTheme.titleMedium,
            ),
            const SizedBox(height: 8),
            Text(
              user.emailVerified
                  ? 'Your account is marked as verified on OurNear.'
                  : 'Verify your email to apply for jobs. You can still browse jobs and update your profile while you wait.',
            ),
            if (!user.emailVerified && ApiConfig.hasApiBase) ...[
              const SizedBox(height: 16),
              FilledButton(
                key: const Key('verification-resend'),
                onPressed: _busy ? null : _resend,
                child: Text(_busy ? 'Please wait' : 'Resend verification email'),
              ),
            ],
            if (!user.emailVerified && !ApiConfig.hasApiBase) ...[
              const SizedBox(height: 16),
              const Text(
                'Configure OURNEAR_API_BASE to resend a verification email from the app.',
              ),
            ],
            if (_message != null) ...[
              const SizedBox(height: 12),
              Text(_message!, key: const Key('verification-message')),
            ],
            if (user.mustChangePassword) ...[
              const SizedBox(height: 16),
              Text(
                'Password change required',
                style: Theme.of(context).textTheme.titleMedium,
              ),
              const SizedBox(height: 8),
              const Text(
                'Your account is flagged to change password on OurNear. '
                'Password change is not available in this app.',
              ),
            ],
          ],
        ],
      ),
    );
  }
}
