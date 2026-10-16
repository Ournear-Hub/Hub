import 'package:flutter/material.dart';
import 'package:flutter_tabler_icons/flutter_tabler_icons.dart';
import 'package:ournear_mobile/core/api/models/public_job.dart';
import 'package:ournear_mobile/core/navigation/root_tab_shell.dart';
import 'package:ournear_mobile/core/widgets/platform_empty_state.dart';
import 'package:ournear_mobile/core/widgets/platform_error_state.dart';
import 'package:ournear_mobile/core/widgets/platform_offline_state.dart';
import 'package:ournear_mobile/features/hire/communication/communication_routes.dart';
import 'package:ournear_mobile/features/hire/hire_job_detail_screen.dart';
import 'package:ournear_mobile/features/hire/jobs/data/jobs_repository.dart';
import 'package:ournear_mobile/features/hire/jobs/job_card.dart';
import 'package:ournear_mobile/features/hire/jobs/jobs_discovery_controller.dart';
import 'package:ournear_mobile/features/hire/widgets/hire_notice_banner.dart';
import 'package:ournear_mobile/features/hire/widgets/hire_welcome_banner.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';
import 'package:provider/provider.dart';

class CandidateHomeScreen extends StatefulWidget {
  const CandidateHomeScreen({super.key, this.repository});

  final JobsRepository? repository;

  @override
  State<CandidateHomeScreen> createState() => _CandidateHomeScreenState();
}

class _CandidateHomeScreenState extends State<CandidateHomeScreen> {
  final _searchController = TextEditingController();
  final _locationController = TextEditingController();
  JobsRepository? _repository;
  List<PublicJob> _jobs = [];
  JobsDataSource? _source;
  List<JobCatalogueShortcut> _filters = [];
  String? _filtersMessage;
  JobsRepositoryException? _jobsError;
  bool _loading = true;
  int _requestId = 0;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    final next = _resolveRepository();
    if (!identical(next, _repository)) {
      _repository = next;
      _load();
    }
  }

  @override
  void dispose() {
    _searchController.dispose();
    _locationController.dispose();
    super.dispose();
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

  JobsDiscoveryController? get _discovery {
    try {
      return Provider.of<JobsDiscoveryController>(context, listen: false);
    } on ProviderNotFoundException {
      return null;
    }
  }

  Future<void> _load() async {
    final repository = _repository;
    if (repository == null) {
      return;
    }
    final requestId = ++_requestId;
    setState(() {
      _loading = true;
      _jobsError = null;
    });

    var filters = <JobCatalogueShortcut>[];
    String? filtersMessage;
    try {
      filters = await repository.fetchIndustryShortcuts();
    } on JobsRepositoryException catch (error) {
      filtersMessage = error.message;
    }

    List<PublicJob> jobs = [];
    JobsDataSource? source;
    JobsRepositoryException? jobsError;
    try {
      final page = await repository.fetchJobs(page: 1, perPage: 10);
      jobs = page.jobs;
      source = page.source;
    } on JobsRepositoryException catch (error) {
      jobsError = error;
    }

    if (!mounted || requestId != _requestId) {
      return;
    }
    setState(() {
      _filters = filters;
      _filtersMessage = filtersMessage;
      _jobs = jobs;
      _source = source;
      _jobsError = jobsError;
      _loading = false;
    });
  }

  String _combinedKeyword() {
    final parts = [
      _searchController.text.trim(),
      _locationController.text.trim(),
    ].where((part) => part.isNotEmpty);
    return parts.join(' ');
  }

  void _submitSearch() {
    _discovery?.search(_combinedKeyword());
    context.findAncestorStateOfType<RootTabShellState>()?.goToId('jobs');
  }

  void _applyFilter(JobCatalogueShortcut shortcut) {
    _searchController.text = shortcut.keyword;
    _discovery?.search(shortcut.keyword);
    context.findAncestorStateOfType<RootTabShellState>()?.goToId('jobs');
  }

  void _openJob(PublicJob job) {
    final repository = _repository;
    if (repository == null) {
      return;
    }
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (context) => HireJobDetailScreen(
          slug: job.slug,
          initialSummary: job,
          repository: repository,
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('OurNear Hire'),
        actions: [
          IconButton(
            tooltip: 'Notifications',
            icon: const Icon(TablerIcons.bell),
            onPressed: () => openCandidateNotifications(context),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _load,
        child: ListView(
          key: const Key('candidate-home-scroll'),
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(OurNearTokens.pageHorizontal),
          children: [
            const HireWelcomeBanner(
              message:
                  'Discover published roles, search by keyword, and review details before you apply.',
            ),
            const SizedBox(height: OurNearTokens.space16),
            TextField(
              key: const Key('home-search-field'),
              controller: _searchController,
              textInputAction: TextInputAction.search,
              decoration: const InputDecoration(
                labelText: 'Keyword',
                helperText: 'Sent as one keyword to the verified jobs API.',
              ),
              onSubmitted: (_) => _submitSearch(),
            ),
            const SizedBox(height: 12),
            TextField(
              key: const Key('home-location-field'),
              controller: _locationController,
              textInputAction: TextInputAction.search,
              decoration: const InputDecoration(
                labelText: 'Location',
                helperText:
                    'Combined with keyword in one search term. Not a separate API field.',
              ),
              onSubmitted: (_) => _submitSearch(),
            ),
            const SizedBox(height: 12),
            FilledButton(
              key: const Key('home-search-submit'),
              style: FilledButton.styleFrom(
                minimumSize: const Size.fromHeight(48),
              ),
              onPressed: _submitSearch,
              child: const Text('Search jobs'),
            ),
            const SizedBox(height: 20),
            Text('Filters', style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: 8),
            if (_filters.isEmpty)
              Text(
                _filtersMessage ??
                    'No verified filter catalogue is available. Use keyword search on Jobs.',
                style: Theme.of(context).textTheme.bodySmall,
              )
            else
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  for (final shortcut in _filters)
                    FilterChip(
                      key: Key('home-filter-${shortcut.keyword}'),
                      label: Text(shortcut.label),
                      onSelected: (_) => _applyFilter(shortcut),
                    ),
                ],
              ),
            const SizedBox(height: 20),
            Row(
              children: [
                Expanded(
                  child: Text(
                    'Latest Jobs',
                    key: const Key('latest-jobs-heading'),
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                ),
                TextButton(
                  key: const Key('home-view-all-jobs'),
                  onPressed: () {
                    context
                        .findAncestorStateOfType<RootTabShellState>()
                        ?.goToId('jobs');
                  },
                  child: const Text('View All'),
                ),
              ],
            ),
            const SizedBox(height: 4),
            Text(
              'Published jobs from the jobs list. Not personalized recommendations.',
              style: Theme.of(context).textTheme.bodySmall,
            ),
            const SizedBox(height: 12),
            ..._latestSection(context),
          ],
        ),
      ),
    );
  }

  List<Widget> _latestSection(BuildContext context) {
    if (_loading) {
      return [
        Semantics(
          label: 'Loading latest jobs',
          child: const Column(
            children: [_JobSkeleton(), SizedBox(height: 10), _JobSkeleton()],
          ),
        ),
      ];
    }

    if (_source == JobsDataSource.mockPreview) {
      return [
        const HireNoticeBanner(
          message:
              'Demo sample jobs. These rows are not from the OurNear server.',
        ),
        const SizedBox(height: OurNearTokens.space12),
        ..._jobCards(),
      ];
    }

    final error = _jobsError;
    if (error != null) {
      if (error.kind == JobsFailureKind.notConfigured) {
        return [
          PlatformEmptyState(
            icon: TablerIcons.plug,
            title: 'Jobs are not configured',
            message: error.message,
            action: FilledButton(onPressed: _load, child: const Text('Retry')),
          ),
        ];
      }
      if (error.kind == JobsFailureKind.offline) {
        return [PlatformOfflineState(message: error.message, onRetry: _load)];
      }
      return [
        PlatformErrorState(
          title: 'Could not load jobs',
          message: error.message,
          onRetry: _load,
        ),
      ];
    }

    if (_jobs.isEmpty) {
      return const [
        PlatformEmptyState(
          icon: TablerIcons.briefcase,
          title: 'No published jobs',
          message: 'The jobs list responded and did not include any jobs.',
        ),
      ];
    }

    return _jobCards();
  }

  List<Widget> _jobCards() {
    return [
      for (final job in _jobs)
        Padding(
          padding: const EdgeInsets.only(bottom: 10),
          child: JobCard(job: job, onTap: () => _openJob(job)),
        ),
    ];
  }
}

class _JobSkeleton extends StatelessWidget {
  const _JobSkeleton();

  @override
  Widget build(BuildContext context) {
    final color = Theme.of(context).colorScheme.primary.withValues(alpha: 0.08);
    return Container(
      height: 72,
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(12),
      ),
    );
  }
}
