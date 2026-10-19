import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/api/models/public_job.dart';
import 'package:ournear_mobile/core/widgets/platform_empty_state.dart';

/// Shows only the company fields already published on a job.
class CompanyDetailsScreen extends StatelessWidget {
  const CompanyDetailsScreen({super.key, required this.job});

  final PublicJob job;

  @override
  Widget build(BuildContext context) {
    final name = job.companyName?.trim();
    final hasName = name != null && name.isNotEmpty;

    return Scaffold(
      appBar: AppBar(title: const Text('Company')),
      body: hasName
          ? ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Text(name, style: Theme.of(context).textTheme.headlineSmall),
                const SizedBox(height: 12),
                Text(
                  'A company profile is not available from the jobs API. This screen shows only the company name published on the job.',
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
                if (job.companyLogoUrl != null &&
                    job.companyLogoUrl!.trim().isNotEmpty) ...[
                  const SizedBox(height: 12),
                  Text(
                    'Published logo URL: ${job.companyLogoUrl}',
                    style: Theme.of(context).textTheme.bodySmall,
                  ),
                ],
              ],
            )
          : const PlatformEmptyState(
              title: 'Company details are unavailable',
              message:
                  'This job did not include a company name, and the app does not request a separate company profile.',
            ),
    );
  }
}
