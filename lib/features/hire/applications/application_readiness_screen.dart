import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/api/models/public_job.dart';
import 'package:ournear_mobile/features/hire/applications/application_wizard_screen.dart';
import 'package:ournear_mobile/features/hire/applications/data/applications_repository.dart';
import 'package:ournear_mobile/features/hire/profile/profile_readiness_note.dart';

/// Checks the published job before the wizard. It does not submit.
class ApplicationReadinessScreen extends StatelessWidget {
  const ApplicationReadinessScreen({
    super.key,
    required this.job,
    this.repository,
  });

  final PublicJobDetail job;
  final ApplicationsRepository? repository;

  bool get _blocked => job.isClosed || job.acceptsApplications == false;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Application readiness')),
      body: ListView(
        key: const Key('application-readiness'),
        padding: const EdgeInsets.all(16),
        children: [
          Text(job.title, style: Theme.of(context).textTheme.titleLarge),
          if (_filled(job.companyName)) ...[
            const SizedBox(height: 8),
            Text(job.companyName!),
          ],
          if (_filled(job.location)) ...[
            const SizedBox(height: 8),
            Text(job.location!),
          ],
          const SizedBox(height: 16),
          Text(_availability),
          const SizedBox(height: 12),
          const Text(
            'Review your professional profile on the Profile tab or use guided profile setup. '
            'Live profile data is not loaded from OurNear, so this screen cannot confirm what would be sent.',
          ),
          const SizedBox(height: 12),
          const Text(
            'Hire Resume and My Resume are separate. Generation and upload are not connected, so no resume can be attached to a live submission.',
          ),
          const SizedBox(height: 12),
          const Text(
            'When you are signed in with a verified email, Submit sends your profile application through OurNear Hire. Unverified accounts can still review readiness here.',
            key: Key('readiness-live-submit-note'),
          ),
          const ProfileReadinessNote(),
          const SizedBox(height: 20),
          if (_blocked)
            const Text(
              'Continue is hidden because this job is closed or is not accepting applications. Nothing has been submitted.',
            )
          else
            FilledButton(
              key: const Key('readiness-continue'),
              style: FilledButton.styleFrom(
                minimumSize: const Size.fromHeight(48),
              ),
              onPressed: () {
                Navigator.of(context).push(
                  MaterialPageRoute<void>(
                    builder: (_) => ApplicationWizardScreen(
                      job: job,
                      repository: repository,
                    ),
                  ),
                );
              },
              child: const Text('Continue'),
            ),
        ],
      ),
    );
  }

  String get _availability {
    if (job.isClosed) {
      return 'This job is closed.';
    }
    if (job.acceptsApplications == false) {
      return 'This job is not accepting applications.';
    }
    if (job.acceptsApplications == true) {
      return 'This published job says it is accepting applications. Continuing does not submit an application.';
    }
    return 'This job does not say whether it accepts applications. Continuing does not submit an application.';
  }

  bool _filled(String? value) => value != null && value.trim().isNotEmpty;
}
