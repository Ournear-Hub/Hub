import 'package:flutter/material.dart';
import 'package:flutter_tabler_icons/flutter_tabler_icons.dart';
import 'package:ournear_mobile/core/widgets/platform_empty_state.dart';

class VibesScreen extends StatelessWidget {
  const VibesScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Vibes')),
      body: const Center(
        child: PlatformEmptyState(
          icon: TablerIcons.flame,
          title: 'Vibes is not live yet',
          message:
              'This tab is part of the unified OurNear shell. Social feeds and posting will connect to Laravel when those APIs exist — no sample posts are shown here.',
        ),
      ),
    );
  }
}
