import 'package:flutter/material.dart';
import 'package:flutter_tabler_icons/flutter_tabler_icons.dart';
import 'package:ournear_mobile/core/theme/hire_theme_context.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';
import 'package:ournear_mobile/features/hire/jobs/widgets/job_company_avatar.dart';
import 'package:ournear_mobile/core/widgets/ournear_card.dart';
import 'package:ournear_mobile/features/hire/applications/models/candidate_application.dart';
import 'package:ournear_mobile/features/hire/applications/widgets/application_status_badge.dart';

class ApplicationListTile extends StatelessWidget {
  const ApplicationListTile({
    super.key,
    required this.application,
    required this.onTap,
  });

  final CandidateApplication application;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final dateLabel = _formatDate(application.lastActivityAt);

    return Semantics(
      button: true,
      label:
          '${application.roleTitle} at ${application.companyName}. Status ${application.statusLabel}. Updated $dateLabel.',
      child: OurNearCard(
        onTap: onTap,
        padding: const EdgeInsets.all(OurNearTokens.space14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                JobCompanyAvatar(
                  companyName: application.companyName,
                  size: 40,
                ),
                const SizedBox(width: OurNearTokens.space10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        application.roleTitle,
                        style: Theme.of(context).textTheme.titleMedium,
                      ),
                      const SizedBox(height: OurNearTokens.space4),
                      Text(
                        application.companyName,
                        style: Theme.of(context).textTheme.bodyMedium,
                      ),
                    ],
                  ),
                ),
                ApplicationStatusBadge(application: application),
              ],
            ),
            const SizedBox(height: OurNearTokens.space6),
            if (application.location != null &&
                application.location!.isNotEmpty) ...[
              const SizedBox(height: OurNearTokens.space4),
              Text(
                application.location!,
                style: Theme.of(context).textTheme.bodySmall,
              ),
            ],
            const SizedBox(height: OurNearTokens.space8),
            Text(
              'Submitted ${_formatDate(application.submittedAt)}',
              style: Theme.of(context).textTheme.bodySmall,
            ),
            const SizedBox(height: OurNearTokens.space8),
            Row(
              children: [
                Icon(
                  TablerIcons.calendar,
                  size: OurNearTokens.iconSm,
                  color: context.hireSecondaryText,
                ),
                const SizedBox(width: OurNearTokens.space6),
                Text(
                  'Updated $dateLabel',
                  style: Theme.of(context).textTheme.bodySmall,
                ),
                const Spacer(),
                Icon(
                  TablerIcons.chevron_right,
                  size: OurNearTokens.iconSm,
                  color: context.hireSecondaryText,
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  static String _formatDate(DateTime value) {
    final local = value.toLocal();
    final month = _monthName(local.month);
    return '$month ${local.day}, ${local.year}';
  }

  static String _monthName(int month) {
    const names = [
      'Jan',
      'Feb',
      'Mar',
      'Apr',
      'May',
      'Jun',
      'Jul',
      'Aug',
      'Sep',
      'Oct',
      'Nov',
      'Dec',
    ];
    return names[month - 1];
  }
}
