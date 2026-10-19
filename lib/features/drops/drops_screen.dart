import 'package:flutter/material.dart';
import 'package:flutter_tabler_icons/flutter_tabler_icons.dart';
import 'package:ournear_mobile/core/widgets/platform_empty_state.dart';

class DropsScreen extends StatelessWidget {
  const DropsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Drops')),
      body: const Center(
        child: PlatformEmptyState(
          icon: TablerIcons.droplet,
          title: 'Drops is not live yet',
          message:
              'Reserved for shared drops and releases across OurNear. The navigation destination is wired; content integration comes next.',
        ),
      ),
    );
  }
}
