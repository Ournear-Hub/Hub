import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/api/api_config.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';
import 'package:ournear_mobile/core/widgets/ournear_password_field.dart';
import 'package:ournear_mobile/core/widgets/ournear_primary_button.dart';
import 'package:ournear_mobile/features/auth/data/account_access_repository.dart';
import 'package:ournear_mobile/features/auth/password_recovery_screen.dart';
import 'package:ournear_mobile/features/hire/widgets/hire_notice_banner.dart';
import 'package:provider/provider.dart';

class SignupForm extends StatefulWidget {
  const SignupForm({
    super.key,
    this.repository,
    this.onSignIn,
  });

  final AccountAccessRepository? repository;
  final VoidCallback? onSignIn;

  @override
  State<SignupForm> createState() => _SignupFormState();
}

class _SignupFormState extends State<SignupForm> {
  final _formKey = GlobalKey<FormState>();
  final _usernameController = TextEditingController();
  final _nameController = TextEditingController();
  final _mobileController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmController = TextEditingController();
  var _termsAccepted = false;
  var _privacyAccepted = false;
  var _submitting = false;
  String? _message;

  @override
  void dispose() {
    _usernameController.dispose();
    _nameController.dispose();
    _mobileController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    _confirmController.dispose();
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
    if (_submitting) {
      return;
    }
    if (!_formKey.currentState!.validate()) {
      return;
    }
    if (!_termsAccepted || !_privacyAccepted) {
      setState(() {
        _message =
            'Accept the Terms & Conditions and Privacy Policy to continue.';
      });
      return;
    }
    setState(() {
      _submitting = true;
      _message = null;
    });
    try {
      final injected = widget.repository;
      if (injected != null) {
        await injected.register(
          username: _usernameController.text.trim(),
          displayName: _nameController.text.trim(),
          mobile: _mobileController.text.trim(),
          email: _emailController.text.trim(),
          password: _passwordController.text,
          passwordConfirmation: _confirmController.text,
        );
      } else if (ApiConfig.hasApiBase) {
        final auth = context.read<AuthController>();
        final ok = await auth.register(
          username: _usernameController.text.trim(),
          displayName: _nameController.text.trim(),
          mobile: _mobileController.text.trim(),
          email: _emailController.text.trim(),
          password: _passwordController.text,
          passwordConfirmation: _confirmController.text,
        );
        if (!mounted) {
          return;
        }
        if (ok) {
          if (mounted && Navigator.of(context).canPop()) {
            Navigator.of(context).pop(true);
            return;
          }
          setState(() {
            _message =
                'Account created. Check your email to verify your address before applying to jobs.';
          });
          return;
        }
        setState(() => _message = auth.errorMessage ?? 'Registration failed.');
        return;
      } else {
        await _repository().register(
          username: _usernameController.text.trim(),
          displayName: _nameController.text.trim(),
          mobile: _mobileController.text.trim(),
          email: _emailController.text.trim(),
          password: _passwordController.text,
          passwordConfirmation: _confirmController.text,
        );
      }
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

  void _showLegalNotice(String title) {
    showDialog<void>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(title),
        content: Text(
          '$title is not connected in this app. No official URL is opened.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('OK'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Form(
      key: _formKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            'Create your OurNear account. Username is your unique sign-in identity.',
            style: Theme.of(context).textTheme.bodyMedium,
          ),
          const SizedBox(height: OurNearTokens.space16),
          TextFormField(
            key: const Key('register-username'),
            controller: _usernameController,
            decoration: const InputDecoration(
              labelText: 'Username',
              helperText: 'Required. Unique on OurNear when registration is connected.',
            ),
            textInputAction: TextInputAction.next,
            autofillHints: const [AutofillHints.username],
            enabled: !_submitting,
            validator: _validateUsername,
          ),
          const SizedBox(height: OurNearTokens.space12),
          TextFormField(
            key: const Key('register-name'),
            controller: _nameController,
            decoration: const InputDecoration(
              labelText: 'Display name',
            ),
            textInputAction: TextInputAction.next,
            enabled: !_submitting,
            validator: (value) {
              if (value == null || value.trim().isEmpty) {
                return 'Enter a display name. This check is not a server rule.';
              }
              return null;
            },
          ),
          const SizedBox(height: OurNearTokens.space12),
          TextFormField(
            key: const Key('register-mobile'),
            controller: _mobileController,
            decoration: const InputDecoration(
              labelText: 'Mobile number',
              helperText: 'Required. Must match OurNear account rules when connected.',
            ),
            keyboardType: TextInputType.phone,
            autofillHints: const [AutofillHints.telephoneNumber],
            textInputAction: TextInputAction.next,
            enabled: !_submitting,
            validator: _validateMobile,
          ),
          const SizedBox(height: OurNearTokens.space12),
          TextFormField(
            key: const Key('register-email'),
            controller: _emailController,
            decoration: const InputDecoration(labelText: 'Email'),
            keyboardType: TextInputType.emailAddress,
            autofillHints: const [AutofillHints.email],
            textInputAction: TextInputAction.next,
            enabled: !_submitting,
            validator: _validateEmail,
          ),
          const SizedBox(height: OurNearTokens.space12),
          OurNearPasswordField(
            fieldKey: const Key('register-password'),
            controller: _passwordController,
            label: 'Password',
            autofillHints: const [AutofillHints.newPassword],
            enabled: !_submitting,
            validator: (value) {
              if (value == null || value.isEmpty) {
                return 'Enter a password. This check is not a server rule.';
              }
              return null;
            },
          ),
          const SizedBox(height: OurNearTokens.space12),
          OurNearPasswordField(
            fieldKey: const Key('register-confirm'),
            controller: _confirmController,
            label: 'Confirm password',
            textInputAction: TextInputAction.done,
            enabled: !_submitting,
            onFieldSubmitted: (_) => _submit(),
            validator: (value) {
              if (value != _passwordController.text) {
                return 'Passwords do not match. This check is not a server rule.';
              }
              return null;
            },
          ),
          const SizedBox(height: OurNearTokens.space12),
          CheckboxListTile(
            key: const Key('register-consent-terms'),
            contentPadding: EdgeInsets.zero,
            controlAffinity: ListTileControlAffinity.leading,
            value: _termsAccepted,
            onChanged: _submitting
                ? null
                : (value) => setState(() => _termsAccepted = value ?? false),
            title: Wrap(
              crossAxisAlignment: WrapCrossAlignment.center,
              children: [
                const Text('I agree to the '),
                InkWell(
                  onTap: _submitting
                      ? null
                      : () => _showLegalNotice('Terms & Conditions'),
                  child: Text(
                    'Terms & Conditions',
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.primary,
                      decoration: TextDecoration.underline,
                    ),
                  ),
                ),
              ],
            ),
          ),
          CheckboxListTile(
            key: const Key('register-consent-privacy'),
            contentPadding: EdgeInsets.zero,
            controlAffinity: ListTileControlAffinity.leading,
            value: _privacyAccepted,
            onChanged: _submitting
                ? null
                : (value) => setState(() => _privacyAccepted = value ?? false),
            title: Wrap(
              crossAxisAlignment: WrapCrossAlignment.center,
              children: [
                const Text('I agree to the '),
                InkWell(
                  onTap: _submitting
                      ? null
                      : () => _showLegalNotice('Privacy Policy'),
                  child: Text(
                    'Privacy Policy',
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.primary,
                      decoration: TextDecoration.underline,
                    ),
                  ),
                ),
              ],
            ),
          ),
          if (_message != null) ...[
            const SizedBox(height: OurNearTokens.space12),
            Text(
              _message!,
              key: const Key('register-message'),
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                color: Theme.of(context).colorScheme.error,
              ),
            ),
          ],
          const SizedBox(height: OurNearTokens.space16),
          OurNearPrimaryButton(
            key: const Key('register-submit'),
            label: 'Create account',
            loading: _submitting,
            onPressed: _submitting ? null : _submit,
          ),
          const SizedBox(height: OurNearTokens.space8),
          if (!ApiConfig.hasApiBase)
            const HireNoticeBanner(
              tone: HireNoticeTone.neutral,
              message:
                  'Set OURNEAR_API_BASE to connect registration. Until then, the form validates locally only and no account is created.',
            ),
          TextButton(
            key: const Key('register-to-login'),
            onPressed: _submitting ? null : widget.onSignIn,
            child: const Text('Already have an account? Sign in'),
          ),
          TextButton(
            onPressed: _submitting
                ? null
                : () {
                    Navigator.of(context).push(
                      MaterialPageRoute<void>(
                        builder: (_) => PasswordRecoveryScreen(
                          initialEmail: _emailController.text.trim(),
                        ),
                      ),
                    );
                  },
            child: const Text('Forgot password?'),
          ),
        ],
      ),
    );
  }

  String? _validateUsername(String? value) {
    final trimmed = value?.trim() ?? '';
    if (trimmed.isEmpty) {
      return 'Enter a username. This check is not a server rule.';
    }
    if (trimmed.length < 3) {
      return 'Username must be at least 3 characters for this form.';
    }
    if (!RegExp(r'^[a-zA-Z0-9._-]+$').hasMatch(trimmed)) {
      return 'Use letters, numbers, dots, dashes or underscores only.';
    }
    return null;
  }

  String? _validateMobile(String? value) {
    final trimmed = value?.trim() ?? '';
    if (trimmed.isEmpty) {
      return 'Enter a mobile number. This check is not a server rule.';
    }
    final digits = trimmed.replaceAll(RegExp(r'\D'), '');
    if (digits.length < 8) {
      return 'Enter a valid mobile number for this form.';
    }
    return null;
  }

  String? _validateEmail(String? value) {
    final trimmed = value?.trim() ?? '';
    if (trimmed.isEmpty) {
      return 'Enter an email address. This check is not a server rule.';
    }
    if (!trimmed.contains('@') || !trimmed.contains('.')) {
      return 'Enter a valid email address. This check is not a server rule.';
    }
    return null;
  }
}
