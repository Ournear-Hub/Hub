import 'package:flutter/material.dart';
import 'package:ournear_mobile/features/hire/widgets/hire_notice_banner.dart';

class CommunicationDemoBanner extends StatelessWidget {
  const CommunicationDemoBanner({super.key, required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    return HireNoticeBanner(
      semanticsLabel: message,
      message: message,
    );
  }
}
