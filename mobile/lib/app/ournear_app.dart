import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/auth/auth_service.dart';
import 'package:ournear_mobile/core/navigation/app_shell.dart';
import 'package:ournear_mobile/core/theme/ournear_theme.dart';
import 'package:provider/provider.dart';

class OurNearApp extends StatelessWidget {
  const OurNearApp({super.key});

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (_) => AuthController(AuthService())..bootstrap(),
      child: MaterialApp(
        title: 'OurNear',
        debugShowCheckedModeBanner: false,
        theme: OurNearTheme.light(),
        home: const _BootstrapGate(),
      ),
    );
  }
}

class _BootstrapGate extends StatelessWidget {
  const _BootstrapGate();

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthController>();
    if (auth.status == AuthStatus.unknown) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }
    return const AppShell();
  }
}
