import 'package:flutter/material.dart';
import 'package:ournear_mobile/app/hire_app_scaffold.dart';
import 'package:ournear_mobile/core/app_config/hire_app_config.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/auth/auth_service.dart';
import 'package:provider/provider.dart';

/// Entry widget for the ON Hire employer app shell.
class HireEmployerApp extends StatelessWidget {
  const HireEmployerApp({super.key});

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (_) => AuthController(AuthService())..bootstrap(),
      child: HireAppScaffold(config: HireAppConfigs.employer),
    );
  }
}
