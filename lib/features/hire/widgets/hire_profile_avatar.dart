import 'package:flutter/material.dart';
import 'package:flutter_tabler_icons/flutter_tabler_icons.dart';

class HireProfileAvatar extends StatelessWidget {
  const HireProfileAvatar({super.key, this.radius = 32, this.imageUrl});

  final double radius;
  final String? imageUrl;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final url = imageUrl?.trim();
    if (url != null && url.isNotEmpty) {
      return CircleAvatar(
        key: const Key('hire-profile-avatar'),
        radius: radius,
        backgroundColor: scheme.primary.withValues(alpha: 0.12),
        backgroundImage: NetworkImage(url),
        onBackgroundImageError: (Object error, StackTrace? stackTrace) {},
      );
    }
    return CircleAvatar(
      key: const Key('hire-profile-avatar'),
      radius: radius,
      backgroundColor: scheme.primary.withValues(alpha: 0.12),
      child: Icon(
        TablerIcons.user,
        color: scheme.primary,
        size: radius,
      ),
    );
  }
}
