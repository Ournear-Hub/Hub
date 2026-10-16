import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/api/models/public_job.dart';
import 'package:ournear_mobile/core/widgets/platform_empty_state.dart';
import 'package:ournear_mobile/core/widgets/platform_error_state.dart';
import 'package:ournear_mobile/core/widgets/platform_offline_state.dart';
import 'package:ournear_mobile/features/hire/applications/application_readiness_screen.dart';
import 'package:ournear_mobile/features/hire/applications/data/applications_repository.dart';
import 'package:ournear_mobile/features/hire/jobs/company_details_screen.dart';
import 'package:ournear_mobile/features/hire/jobs/data/jobs_repository.dart';
import 'package:ournear_mobile/features/hire/jobs/widgets/job_detail_header.dart';
import 'package:ournear_mobile/features/hire/profile/widgets/profile_section_card.dart';
import 'package:provider/provider.dart';

class HireJobDetailScreen extends StatefulWidget {
  const HireJobDetailScreen({
    super.key,
    required this.slug,
    this.initialSummary,
    this.repository,
  });

  final String slug;
  final PublicJob? initialSummary;
  final JobsRepository? repository;

  @override
  State<HireJobDetailScreen> createState() => _HireJobDetailScreenState();
}

class _HireJobDetailScreenState extends State<HireJobDetailScreen> {
  JobsRepository? _repository;
  PublicJobDetail? _job;
  JobsRepositoryException? _error;
  bool _loading = true;
  bool _started = false;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _repository ??= _resolveRepository();
    if (!_started) {
      _started = true;
      _load();
    }
  }

  JobsRepository _resolveRepository() {
    if (widget.repository != null) {
      return widget.repository!;
    }
    try {
      return Provider.of<JobsRepository>(context, listen: false);
    } on ProviderNotFoundException {
      return LiveJobsRepository();
    }
  }

  Future<void> _load() async {
    final repository = _repository;
    if (repository == null) {
      return;
    }
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final job = await repository.fetchJobDetail(widget.slug);
      if (mounted) {
        setState(() {
          _job = job;
          _loading = false;
        });
      }
    } on JobsRepositoryException catch (error) {
      if (mounted) {
        setState(() {
          _error = error;
          _loading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final title = _job?.title ?? widget.initialSummary?.title ?? 'Job';
    return Scaffold(
      appBar: AppBar(title: Text(title)),
      body: _buildBody(context),
    );
  }

  Widget _buildBody(BuildContext context) {
    if (_loading) {
      return const Center(
        child: CircularProgressIndicator(semanticsLabel: 'Loading job'),
      );
    }

    final error = _error;
    if (error != null) {
      if (error.kind == JobsFailureKind.notFound) {
        return PlatformEmptyState(
          title: 'Job not available',
          message: error.message,
        );
      }
      if (error.kind == JobsFailureKind.offline) {
        return PlatformOfflineState(message: error.message, onRetry: _load);
      }
      return PlatformErrorState(
        title: 'Could not load job',
        message: error.message,
        onRetry: _load,
      );
    }

    final job = _job!;
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        JobDetailHeader(
          job: job,
          acceptsApplications: job.acceptsApplications,
        ),
        if (_filled(job.companyName)) ...[
          const SizedBox(height: 8),
          Align(
            alignment: Alignment.centerLeft,
            child: TextButton(
              onPressed: () {
                Navigator.of(context).push(
                  MaterialPageRoute<void>(
                    builder: (context) => CompanyDetailsScreen(job: job),
                  ),
                );
              },
              child: const Text('Company details'),
            ),
          ),
        ],
        ..._fact(context, 'Location', job.location),
        ..._fact(context, 'Experience', job.experience),
        ..._fact(context, 'Employment type', job.employmentType),
        ..._fact(context, 'Industry', job.industry),
        ..._fact(context, 'Category', job.category),
        ..._fact(context, 'Posted', job.postedLabel),
        ..._fact(context, 'Closing', job.closingLabel),
        ..._sectionCard(context, 'Description', job.description),
        ..._sectionCard(context, 'Responsibilities', job.responsibilities),
        ..._sectionCard(context, 'Requirements', job.requirements),
        ..._sectionCard(context, 'What we offer', job.whatWeOffer),
        const SizedBox(height: 20),
        _applySection(context, job),
      ],
    );
  }

  List<Widget> _fact(BuildContext context, String label, String? value) {
    if (!_filled(value)) {
      return const [];
    }
    return [
      const SizedBox(height: 8),
      Text('$label: $value', style: Theme.of(context).textTheme.bodyMedium),
    ];
  }

  List<Widget> _sectionCard(BuildContext context, String title, String? body) {
    if (!_filled(body)) {
      return const [];
    }
    return [
      const SizedBox(height: 12),
      ProfileSectionCard(title: title, body: body!),
    ];
  }

  Widget _applySection(BuildContext context, PublicJobDetail job) {
    final closed = job.isClosed || job.acceptsApplications == false;
    if (closed) {
      return const Text(
        'Apply is hidden because this job is closed or is not accepting applications.',
      );
    }
    return FilledButton(
      style: FilledButton.styleFrom(minimumSize: const Size.fromHeight(48)),
      onPressed: () {
        ApplicationsRepository? repository;
        try {
          repository = context.read<ApplicationsRepository>();
        } on ProviderNotFoundException {
          repository = null;
        }
        Navigator.of(context).push(
          MaterialPageRoute<void>(
            builder: (context) =>
                ApplicationReadinessScreen(job: job, repository: repository),
          ),
        );
      },
      child: const Text('Apply Now'),
    );
  }

  bool _filled(String? value) => value != null && value.trim().isNotEmpty;
}
