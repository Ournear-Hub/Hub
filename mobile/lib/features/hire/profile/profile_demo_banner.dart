import 'package:flutter/material.dart';
import 'package:ournear_mobile/features/hire/widgets/hire_notice_banner.dart';

class ProfileDemoBanner extends StatelessWidget {
  const ProfileDemoBanner({super.key});

  @override
  Widget build(BuildContext context) {
    return const HireNoticeBanner(
      semanticsLabel: 'Demo profile. Not saved on OurNear.',
      message:
          'Demo profile. These details are not saved on OurNear and are not a server record.',
    );
  }
}
