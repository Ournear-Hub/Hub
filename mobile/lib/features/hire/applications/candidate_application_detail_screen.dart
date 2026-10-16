import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';
import 'package:ournear_mobile/core/widgets/platform_empty_state.dart';
import 'package:ournear_mobile/core/widgets/platform_error_state.dart';
import 'package:ournear_mobile/features/hire/applications/data/applications_repository.dart';
import 'package:ournear_mobile/features/hire/applications/models/candidate_application.dart';
import 'package:ournear_mobile/features/hire/applications/widgets/application_status_badge.dart';
import 'package:ournear_mobile/features/hire/applications/widgets/applications_demo_banner.dart';
import 'package:ournear_mobile/features/hire/hire_job_detail_screen.dart';

class CandidateApplicationDetailScreen extends StatefulWidget {
  CandidateApplicationDetailScreen({
    super.key,
    required CandidateApplication application,
    required this.dataSource,
  }) : initialApplication = application,
       applicationId = application.id,
       repository = null;

  const CandidateApplicationDetailScreen.fetch({
    super.key,
    required this.applicationId,
    required this.repository,
    this.dataSource,
    this.initialApplication,
  });

  final CandidateApplication? initialApplication;
  final String applicationId;
  final ApplicationsDataSource? dataSource;
  final ApplicationsRepository? repository;

  @override
  State<CandidateApplicationDetailScreen> createState() =>
      _CandidateApplicationDetailScreenState();
}

class _CandidateApplicationDetailScreenState
    extends State<CandidateApplicationDetailScreen> {
  CandidateApplication? _application;
  ApplicationsRepositoryException? _error;
  var _loading = false;

  @override
  void initState() {
    super.initState();
    _application = widget.initialApplication;
    if (widget.repository != null) {
      _loading = _application == null;
      _load();
    }
  }

  Future<void> _load() async {
    final repository = widget.repository;
    if (repository == null) {
      setState(() {
        _loading = false;
        _error = ApplicationsRepositoryException.unavailable(
          'Application details are not available.',
        );
      });
      return;
    }
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final application = await repository.fetchApplication(
        widget.applicationId,
      );
      if (!mounted) {
        return;
      }
      setState(() {
        _application = application;
        _loading = false;
      });
    } on ApplicationsRepositoryException catch (error) {
      if (!mounted) {
        return;
      }
      setState(() {
        _error = error;
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final application = _application;
    return Scaffold(
      appBar: AppBar(title: Text(application?.roleTitle ?? 'Application')),
      body: _loading
          ? const Center(
              child: CircularProgressIndicator(
                semanticsLabel: 'Loading application',
              ),
            )
          : application == null
          ? _missing(context)
          : _body(context, application),
    );
  }

  Widget _missing(BuildContext context) {
    final error = _error;
    if (error?.kind == ApplicationsFailureKind.notFound) {
      return PlatformEmptyState(
        title: 'Application not found',
        message: error!.message,
      );
    }
    return PlatformErrorState(
      title: 'Could not load this application',
      message: error?.message ?? 'This application is not available.',
      onRetry: _load,
    );
  }

  Widget _body(BuildContext context, CandidateApplication application) {
    final dataSource = widget.dataSource;
    return ListView(
      padding: OurNearTokens.pagePadding(context),
      children: [
        if (dataSource == ApplicationsDataSource.mockDemo)
          const ApplicationsDemoBanner(),
        if (dataSource == ApplicationsDataSource.mockDemo)
          const SizedBox(height: OurNearTokens.space16),
        ApplicationStatusBadge(application: application),
        const SizedBox(height: OurNearTokens.space12),
        Text(
          application.roleTitle,
          style: Theme.of(context).textTheme.headlineSmall,
        ),
        const SizedBox(height: OurNearTokens.space8),
        Text(
          application.companyName,
          style: Theme.of(context).textTheme.bodyMedium,
        ),
        if (application.location != null &&
            application.location!.isNotEmpty) ...[
          const SizedBox(height: OurNearTokens.space6),
          Text(
            application.location!,
            style: Theme.of(context).textTheme.bodySmall,
          ),
        ],
        const SizedBox(height: OurNearTokens.space16),
        _DetailRow(
          label: 'Submitted',
          value: _formatDateTime(application.submittedAt),
        ),
        if (application.updatedAt != null) ...[
          const SizedBox(height: OurNearTokens.space8),
          _DetailRow(
            label: 'Last updated',
            value: _formatDateTime(application.updatedAt!),
          ),
        ],
        const SizedBox(height: OurNearTokens.space16),
        Text('Timeline', style: Theme.of(context).textTheme.titleMedium),
        const SizedBox(height: OurNearTokens.space8),
        if (application.timeline.isEmpty)
          const Text('No timeline events were provided with this application.')
        else
          for (final event in application.timeline) ...[
            Text('${event.label}: ${_formatDateTime(event.occurredAt)}'),
            const SizedBox(height: OurNearTokens.space6),
          ],
        const SizedBox(height: OurNearTokens.space16),
        Text(
          'Message threads, interview schedules, and employer decisions appear only when this record includes them.',
          style: Theme.of(context).textTheme.bodySmall,
        ),
        if (application.hasResume &&
            widget.repository is LiveApplicationsRepository) ...[
          const SizedBox(height: OurNearTokens.space16),
          OutlinedButton(
            key: const Key('application-resume-download'),
            onPressed: () => _downloadResume(application),
            child: const Text('Download application resume'),
          ),
        ],
        if (application.jobSlug != null && application.jobSlug!.isNotEmpty) ...[
          const SizedBox(height: OurNearTokens.space16),
          OutlinedButton(
            onPressed: () {
              Navigator.of(context).push(
                MaterialPageRoute<void>(
                  builder: (_) =>
                      HireJobDetailScreen(slug: application.jobSlug!),
                ),
              );
            },
            child: const Text('View job'),
          ),
        ],
      ],
    );
  }

  Future<void> _downloadResume(CandidateApplication application) async {
    final repository = widget.repository;
    if (repository is! LiveApplicationsRepository) {
      return;
    }
    try {
      final response = await repository.downloadResume(application.id);
      if (!mounted) {
        return;
      }
      final kb = (response.bodyBytes.length / 1024).toStringAsFixed(1);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            'Resume downloaded from OurNear ($kb KB). Open it with your device file viewer when saved to storage is enabled.',
          ),
        ),
      );
    } on ApplicationsRepositoryException catch (error) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(error.message)),
      );
    }
  }

  static String _formatDateTime(DateTime value) {
    final local = value.toLocal();
    return '${local.year}-${local.month.toString().padLeft(2, '0')}-${local.day.toString().padLeft(2, '0')} '
        '${local.hour.toString().padLeft(2, '0')}:${local.minute.toString().padLeft(2, '0')}';
  }
}

class _DetailRow extends StatelessWidget {
  const _DetailRow({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: 110,
          child: Text(label, style: Theme.of(context).textTheme.labelSmall),
        ),
        Expanded(
          child: Text(value, style: Theme.of(context).textTheme.bodyMedium),
        ),
      ],
    );
  }
}
