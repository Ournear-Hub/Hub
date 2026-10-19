import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/api/api_config.dart';
import 'package:ournear_mobile/core/theme/hire_theme_context.dart';
import 'package:ournear_mobile/features/auth/data/account_access_repository.dart';
import 'package:ournear_mobile/features/auth/login_screen.dart';
import 'package:provider/provider.dart';

class PasswordRecoveryScreen extends StatefulWidget {
  const PasswordRecoveryScreen({
    super.key,
    this.repository,
    this.initialEmail = '',
  });

  final AccountAccessRepository? repository;
  final String initialEmail;

  @override
  State<PasswordRecoveryScreen> createState() => _PasswordRecoveryScreenState();
}

class _PasswordRecoveryScreenState extends State<PasswordRecoveryScreen> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _emailController;
  var _submitting = false;
  String? _message;

  @override
  void initState() {
    super.initState();
    _emailController = TextEditingController(text: widget.initialEmail);
  }

  @override
  void dispose() {
    _emailController.dispose();
    super.dispose();
  }

  AccountAccessRepository _repository() {
    final injected = widget.repository;
    if (injected != null) {
      return injected;
    }
    try {
      return context.read<AccountAccessRepository>();
    } on ProviderNotFoundException {
      return LiveAccountAccessRepository();
    }
  }

  Future<void> _submit() async {
    if (_submitting || !_formKey.currentState!.validate()) {
      return;
    }
    setState(() {
      _submitting = true;
      _message = null;
    });
    try {
      await _repository().requestPasswordRecovery(
        email: _emailController.text.trim(),
      );
      if (!mounted) {
        return;
      }
      setState(() {
        _message =
            'If an account exists for that email, a password reset link has been sent.';
      });
    } on AccountAccessException catch (error) {
      if (!mounted) {
        return;
      }
      setState(() => _message = error.message);
    } finally {
      if (mounted) {
        setState(() => _submitting = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: context.hireCanvas,
      appBar: AppBar(title: const Text('Password recovery')),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(20),
          children: [
            Text(
              ApiConfig.hasApiBase
                  ? 'Enter the email for your account. If it matches an OurNear account, we will email a reset link. This screen does not confirm whether an account exists.'
                  : 'Enter the email for your account. Set OURNEAR_API_BASE to connect password recovery.',
            ),
            const SizedBox(height: 16),
            Form(
              key: _formKey,
              child: TextFormField(
                key: const Key('recovery-email'),
                controller: _emailController,
                decoration: const InputDecoration(labelText: 'Email'),
                keyboardType: TextInputType.emailAddress,
                autofillHints: const [AutofillHints.email],
                textInputAction: TextInputAction.done,
                onFieldSubmitted: (_) => _submit(),
                validator: (value) {
                  final trimmed = value?.trim() ?? '';
                  if (trimmed.isEmpty) {
                    return 'Enter an email address. This check is not a server rule.';
                  }
                  if (!trimmed.contains('@') || !trimmed.contains('.')) {
                    return 'Enter a valid email address. This check is not a server rule.';
                  }
                  return null;
                },
              ),
            ),
            if (_message != null) ...[
              const SizedBox(height: 12),
              Text(_message!, key: const Key('recovery-message')),
            ],
            const SizedBox(height: 20),
            FilledButton(
              key: const Key('recovery-submit'),
              style: FilledButton.styleFrom(
                minimumSize: const Size.fromHeight(48),
                backgroundColor: Theme.of(context).colorScheme.primary,
              ),
              onPressed: _submitting ? null : _submit,
              child: Text(_submitting ? 'Please wait' : 'Send reset link'),
            ),
            TextButton(
              key: const Key('recovery-to-login'),
              onPressed: () {
                Navigator.of(context).pushReplacement(
                  MaterialPageRoute<void>(builder: (_) => const LoginScreen()),
                );
              },
              child: const Text('Back to sign in'),
            ),
          ],
        ),
      ),
    );
  }
}
