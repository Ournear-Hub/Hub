import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/theme/hire_theme_context.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';
import 'package:ournear_mobile/features/auth/widgets/auth_tab_selector.dart';
import 'package:ournear_mobile/features/auth/widgets/login_form.dart';
import 'package:ournear_mobile/features/auth/widgets/ournear_auth_header.dart';
import 'package:ournear_mobile/features/auth/data/account_access_repository.dart';
import 'package:ournear_mobile/features/auth/widgets/signup_form.dart';

/// Unified candidate authentication: Login and Sign up tabs.
class LoginScreen extends StatefulWidget {
  const LoginScreen({
    super.key,
    this.initialTabIndex = 0,
    this.accountAccessRepository,
  });

  /// 0 = Login, 1 = Sign up
  final int initialTabIndex;
  final AccountAccessRepository? accountAccessRepository;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  late int _tabIndex;

  @override
  void initState() {
    super.initState();
    _tabIndex = widget.initialTabIndex.clamp(0, 1);
  }

  void _selectTab(int index) {
    setState(() => _tabIndex = index);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: context.hireCanvas,
      appBar: AppBar(
        title: Text(_tabIndex == 0 ? 'Sign in' : 'Create account'),
      ),
      body: SafeArea(
        child: LayoutBuilder(
          builder: (context, constraints) {
            return SingleChildScrollView(
              padding: OurNearTokens.pagePadding(context),
              child: Center(
                child: ConstrainedBox(
                  constraints: const BoxConstraints(
                    maxWidth: OurNearTokens.maxContentWidth,
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      const OurNearAuthHeader(),
                      const SizedBox(height: OurNearTokens.space20),
                      AuthTabSelector(
                        index: _tabIndex,
                        onChanged: _selectTab,
                      ),
                      const SizedBox(height: OurNearTokens.space20),
                      AnimatedSwitcher(
                        duration: const Duration(milliseconds: 200),
                        child: _tabIndex == 0
                            ? LoginForm(
                                key: const Key('auth-login-panel'),
                                onCreateAccount: () => _selectTab(1),
                              )
                            : SignupForm(
                                key: const Key('auth-signup-panel'),
                                repository: widget.accountAccessRepository,
                                onSignIn: () => _selectTab(0),
                              ),
                      ),
                    ],
                  ),
                ),
              ),
            );
          },
        ),
      ),
    );
  }
}
