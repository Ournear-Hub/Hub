import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/api/models/public_job.dart';
import 'package:ournear_mobile/core/theme/hire_theme_context.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';
import 'package:ournear_mobile/core/widgets/status_badge.dart';
import 'package:ournear_mobile/features/hire/jobs/widgets/job_company_avatar.dart';

class JobDetailHeader extends StatelessWidget {
  const JobDetailHeader({
    super.key,
    required this.job,
    this.acceptsApplications,
  });

  final PublicJob job;
  final bool? acceptsApplications;

  @override
  Widget build(BuildContext context) {
    return Card(
      color: context.hireCardSurface,
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsets.all(OurNearTokens.space16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                JobCompanyAvatar(
                  companyName: job.companyName,
                  logoUrl: job.companyLogoUrl,
                  size: 52,
                ),
                const SizedBox(width: OurNearTokens.space12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        job.title,
                        style: Theme.of(context).textTheme.headlineSmall,
                      ),
                      if (job.companyName != null &&
                          job.companyName!.trim().isNotEmpty) ...[
                        const SizedBox(height: OurNearTokens.space4),
                        Text(
                          job.companyName!,
                          style: Theme.of(context).textTheme.bodyMedium,
                        ),
                      ],
                    ],
                  ),
                ),
              ],
            ),
            if (_badges.isNotEmpty) ...[
              const SizedBox(height: OurNearTokens.space10),
              Wrap(
                spacing: OurNearTokens.space6,
                runSpacing: OurNearTokens.space6,
                children: _badges,
              ),
            ],
            if (job.salary != null && job.salary!.trim().isNotEmpty) ...[
              const SizedBox(height: OurNearTokens.space10),
              Text(
                job.salary!,
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  color: Theme.of(context).colorScheme.primary,
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  List<Widget> get _badges {
    final badges = <Widget>[];
    if (job.featured) {
      badges.add(
        const StatusBadge(label: 'Featured', tone: StatusBadgeTone.brand),
      );
    }
    if (job.urgent) {
      badges.add(
        const StatusBadge(label: 'Urgent', tone: StatusBadgeTone.warning),
      );
    }
    if (job.isClosed) {
      badges.add(
        const StatusBadge(label: 'Closed', tone: StatusBadgeTone.neutral),
      );
    }
    if (acceptsApplications == false) {
      badges.add(
        const StatusBadge(
          label: 'Not accepting applications',
          tone: StatusBadgeTone.error,
        ),
      );
    }
    return badges;
  }
}
