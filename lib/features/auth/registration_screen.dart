import 'package:flutter/material.dart';
import 'package:ournear_mobile/features/auth/data/account_access_repository.dart';
import 'package:ournear_mobile/features/auth/login_screen.dart';

/// Sign-up entry — same unified auth shell with the Sign up tab selected.
class RegistrationScreen extends StatelessWidget {
  const RegistrationScreen({super.key, this.repository});

  final AccountAccessRepository? repository;

  @override
  Widget build(BuildContext context) {
    return LoginScreen(
      initialTabIndex: 1,
      accountAccessRepository: repository,
    );
  }
}
