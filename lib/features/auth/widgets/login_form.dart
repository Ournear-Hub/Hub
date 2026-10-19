import 'package:flutter/material.dart';
import 'package:flutter_tabler_icons/flutter_tabler_icons.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';
import 'package:ournear_mobile/core/widgets/ournear_password_field.dart';
import 'package:ournear_mobile/core/widgets/ournear_primary_button.dart';
import 'package:ournear_mobile/features/auth/password_recovery_screen.dart';
import 'package:provider/provider.dart';

class LoginForm extends StatefulWidget {
  const LoginForm({
    super.key,
    this.onCreateAccount,
  });

  final VoidCallback? onCreateAccount;

  @override
  State<LoginForm> createState() => _LoginFormState();
}

class _LoginFormState extends State<LoginForm> {
  final _formKey = GlobalKey<FormState>();
  final _loginController = TextEditingController();
  final _passwordController = TextEditingController();

  @override
  void dispose() {
    _loginController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }
    final auth = context.read<AuthController>();
    if (auth.busy) {
      return;
    }
    final ok = await auth.login(
      _loginController.text,
      _passwordController.text,
    );
    if (ok && mounted) {
      Navigator.of(context).pop(true);
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthController>();
    final theme = Theme.of(context);

    return Form(
      key: _formKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            'Sign in with your OurNear username or email.',
            style: theme.textTheme.bodyMedium,
          ),
          const SizedBox(height: OurNearTokens.space16),
          TextFormField(
            key: const Key('login-field'),
            controller: _loginController,
            decoration: const InputDecoration(
              labelText: 'Username or email',
            ),
            textInputAction: TextInputAction.next,
            autofillHints: const [AutofillHints.username],
            enabled: !auth.busy,
            validator: (value) => value == null || value.trim().isEmpty
                ? 'Enter your login.'
                : null,
          ),
          const SizedBox(height: OurNearTokens.space12),
          OurNearPasswordField(
            fieldKey: const Key('login-password'),
            controller: _passwordController,
            label: 'Password',
            autofillHints: const [AutofillHints.password],
            enabled: !auth.busy,
            onFieldSubmitted: (_) => _submit(),
            validator: (value) =>
                value == null || value.isEmpty ? 'Enter your password.' : null,
          ),
          if (auth.errorMessage != null) ...[
            const SizedBox(height: OurNearTokens.space12),
            Text(
              auth.errorMessage!,
              key: const Key('login-error'),
              style: theme.textTheme.bodySmall?.copyWith(
                color: theme.colorScheme.error,
              ),
            ),
          ],
          const SizedBox(height: OurNearTokens.space20),
          OurNearPrimaryButton(
            key: const ValueKey('login-submit'),
            label: 'Sign in',
            icon: TablerIcons.login,
            loading: auth.busy,
            onPressed: auth.busy ? null : _submit,
          ),
          TextButton(
            key: const Key('login-to-register'),
            onPressed: auth.busy ? null : widget.onCreateAccount,
            child: const Text('Create account'),
          ),
          TextButton(
            key: const Key('login-to-recovery'),
            onPressed: auth.busy
                ? null
                : () {
                    Navigator.of(context).push(
                      MaterialPageRoute<void>(
                        builder: (_) => const PasswordRecoveryScreen(),
                      ),
                    );
                  },
            child: const Text('Forgot password?'),
          ),
        ],
      ),
    );
  }
}
