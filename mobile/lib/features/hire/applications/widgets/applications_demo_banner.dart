import 'package:flutter/material.dart';
import 'package:ournear_mobile/features/hire/widgets/hire_notice_banner.dart';

/// Makes mock/demo application rows visually distinct from future live API data.
class ApplicationsDemoBanner extends StatelessWidget {
  const ApplicationsDemoBanner({super.key});

  @override
  Widget build(BuildContext context) {
    return const HireNoticeBanner(
      semanticsLabel:
          'Demo sample data. Not loaded from your OurNear account.',
      message:
          'Demo sample data — these applications are not loaded from your OurNear account or stored on the server.',
    );
  }
}
