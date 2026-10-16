import 'package:flutter/material.dart';
import 'package:flutter_tabler_icons/flutter_tabler_icons.dart';
import 'package:ournear_mobile/core/api/models/public_job.dart';
import 'package:ournear_mobile/core/theme/hire_theme_context.dart';
import 'package:ournear_mobile/core/theme/ournear_colors.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';
import 'package:ournear_mobile/core/widgets/status_badge.dart';
import 'package:ournear_mobile/features/hire/jobs/widgets/job_company_avatar.dart';

class JobCard extends StatelessWidget {
  const JobCard({
    super.key,
    required this.job,
    required this.onTap,
    this.onSaveToggle,
    this.saveBusy = false,
  });

  final PublicJob job;
  final VoidCallback onTap;
  final VoidCallback? onSaveToggle;
  final bool saveBusy;

  @override
  Widget build(BuildContext context) {
    final label = [
      job.title,
      if (job.companyName != null && job.companyName!.trim().isNotEmpty)
        job.companyName!,
      if (job.location != null && job.location!.trim().isNotEmpty)
        job.location!,
      if (job.experience != null && job.experience!.trim().isNotEmpty)
        job.experience!,
      if (job.employmentType != null && job.employmentType!.trim().isNotEmpty)
        job.employmentType!,
      if (job.isClosed) 'Closed',
      if (job.isSaved == true) 'Saved',
    ].join(', ');

    final showSave = job.isSaved != null && onSaveToggle != null;

    return Semantics(
      button: true,
      label: label,
      child: Card(
        key: Key('job-card-${job.slug}'),
        color: context.hireCardSurface,
        margin: EdgeInsets.zero,
        child: InkWell(
          borderRadius: OurNearTokens.borderRadiusXl,
          onTap: onTap,
          child: Padding(
            padding: const EdgeInsets.all(OurNearTokens.space14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    JobCompanyAvatar(
                      companyName: job.companyName,
                      logoUrl: job.companyLogoUrl,
                    ),
                    const SizedBox(width: OurNearTokens.space12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            job.title,
                            style: Theme.of(context).textTheme.titleMedium,
                          ),
                          if (job.companyName != null &&
                              job.companyName!.trim().isNotEmpty) ...[
                            const SizedBox(height: OurNearTokens.space4),
                            Text(
                              job.companyName!,
                              style: Theme.of(context).textTheme.bodySmall
                                  ?.copyWith(color: context.hireSecondaryText),
                            ),
                          ],
                        ],
                      ),
                    ),
                    if (showSave)
                      IconButton(
                        key: Key('job-save-${job.slug}'),
                        tooltip: job.isSaved!
                            ? 'Unsave job'
                            : 'Save job',
                        onPressed: saveBusy ? null : onSaveToggle,
                        icon: saveBusy
                            ? const SizedBox(
                                width: 20,
                                height: 20,
                                child: CircularProgressIndicator(strokeWidth: 2),
                              )
                            : Icon(
                                job.isSaved!
                                    ? TablerIcons.bookmark_filled
                                    : TablerIcons.bookmark,
                                color: job.isSaved!
                                    ? OurNearColors.hireOrange
                                    : context.hireSecondaryText,
                              ),
                      ),
                  ],
                ),
                if (_badges.isNotEmpty) ...[
                  const SizedBox(height: OurNearTokens.space8),
                  Wrap(
                    spacing: OurNearTokens.space6,
                    runSpacing: OurNearTokens.space6,
                    children: _badges,
                  ),
                ],
                if (job.location != null &&
                    job.location!.trim().isNotEmpty) ...[
                  const SizedBox(height: OurNearTokens.space8),
                  Row(
                    children: [
                      Icon(
                        TablerIcons.map_pin,
                        size: OurNearTokens.iconSm,
                        color: context.hireSecondaryText,
                      ),
                      const SizedBox(width: OurNearTokens.space6),
                      Expanded(
                        child: Text(
                          job.location!,
                          style: Theme.of(context).textTheme.bodySmall,
                        ),
                      ),
                    ],
                  ),
                ],
                if (_meta.isNotEmpty) ...[
                  const SizedBox(height: OurNearTokens.space4),
                  Text(_meta, style: Theme.of(context).textTheme.bodySmall),
                ],
                if (job.salary != null && job.salary!.trim().isNotEmpty) ...[
                  const SizedBox(height: OurNearTokens.space8),
                  Text(
                    job.salary!,
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      color: Theme.of(context).colorScheme.primary,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
                if (job.postedLabel != null &&
                    job.postedLabel!.trim().isNotEmpty) ...[
                  const SizedBox(height: OurNearTokens.space6),
                  Text(
                    job.postedLabel!,
                    style: Theme.of(context).textTheme.labelSmall?.copyWith(
                      color: context.hireSecondaryText,
                    ),
                  ),
                ],
              ],
            ),
          ),
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
    return badges;
  }

  String get _meta {
    final parts = <String>[];
    if (job.employmentType != null && job.employmentType!.trim().isNotEmpty) {
      parts.add(job.employmentType!.trim());
    }
    if (job.experience != null && job.experience!.trim().isNotEmpty) {
      parts.add(job.experience!.trim());
    }
    return parts.join(' · ');
  }
}
