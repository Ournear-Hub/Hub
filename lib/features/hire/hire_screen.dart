import 'package:flutter/material.dart';
import 'package:flutter_tabler_icons/flutter_tabler_icons.dart';
import 'package:ournear_mobile/core/api/api_config.dart';
import 'package:ournear_mobile/core/api/models/public_job.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/features/auth/login_screen.dart';
import 'package:ournear_mobile/core/widgets/platform_empty_state.dart';
import 'package:ournear_mobile/core/widgets/platform_error_state.dart';
import 'package:ournear_mobile/core/widgets/platform_offline_state.dart';
import 'package:ournear_mobile/features/hire/hire_job_detail_screen.dart';
import 'package:ournear_mobile/features/hire/jobs/data/jobs_repository.dart';
import 'package:ournear_mobile/features/hire/jobs/job_card.dart';
import 'package:ournear_mobile/features/hire/jobs/jobs_discovery_controller.dart';
import 'package:ournear_mobile/features/hire/widgets/hire_notice_banner.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

class HireScreen extends StatefulWidget {
  const HireScreen({
    super.key,
    this.appBarTitle = 'Hire',
    this.repository,
    this.showDiscoveryControls = false,
  });

  final String appBarTitle;
  final JobsRepository? repository;

  /// Candidate Jobs tab. The unified Hire tab leaves this false.
  final bool showDiscoveryControls;

  @override
  State<HireScreen> createState() => _HireScreenState();
}

class _HireScreenState extends State<HireScreen> {
  final _searchController = TextEditingController();
  final _scrollController = ScrollController();
  JobsRepository? _repository;
  JobsDiscoveryController? _discovery;
  final List<PublicJob> _jobs = [];
  JobsDataSource? _source;
  bool _loading = true;
  bool _loadingMore = false;
  JobsRepositoryException? _error;
  int _page = 1;
  bool _hasMore = false;
  int? _total;
  String _keyword = '';
  String? _filterIndustry;
  String? _filterLocation;
  String? _filterCategory;
  String? _filterEmploymentType;
  String? _filterExperience;
  String? _filterSort;
  List<String> _industryOptions = const [];
  List<String> _locationOptions = const [];
  List<String> _categoryOptions = const [];
  List<String> _employmentTypeOptions = const [];
  List<String> _experienceOptions = const [];
  int _requestId = 0;
  bool _started = false;
  AuthController? _auth;
  String? _savingSlug;

  @override
  void initState() {
    super.initState();
    _searchController.addListener(_onSearchTextChanged);
  }

  void _onSearchTextChanged() {
    if (mounted) {
      setState(() {});
    }
    final discovery = _discovery;
    if (discovery != null) {
      discovery.searchDebounced(_searchController.text);
    }
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _repository = _resolveRepository();
    final discovery = _resolveDiscovery();
    if (!identical(discovery, _discovery)) {
      _discovery?.removeListener(_onDiscoveryChanged);
      _discovery = discovery;
      _discovery?.addListener(_onDiscoveryChanged);
      final shared = _discovery?.keyword ?? '';
      if (shared != _keyword) {
        _keyword = shared;
        _searchController.text = shared;
      }
    }
    final auth = _readAuth();
    if (!identical(auth, _auth)) {
      _auth?.removeListener(_onAuthChanged);
      _auth = auth;
      _auth?.addListener(_onAuthChanged);
    }
    if (!_started) {
      _started = true;
      _refresh();
    }
  }

  AuthController? _readAuth() {
    try {
      return Provider.of<AuthController>(context, listen: false);
    } on ProviderNotFoundException {
      return null;
    }
  }

  void _onAuthChanged() {
    if (_started) {
      _refresh();
    }
  }

  @override
  void dispose() {
    _auth?.removeListener(_onAuthChanged);
    _discovery?.removeListener(_onDiscoveryChanged);
    _searchController.removeListener(_onSearchTextChanged);
    _searchController.dispose();
    _scrollController.dispose();
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

  JobsDiscoveryController? _resolveDiscovery() {
    if (!widget.showDiscoveryControls) {
      return null;
    }
    try {
      return Provider.of<JobsDiscoveryController>(context, listen: false);
    } on ProviderNotFoundException {
      return null;
    }
  }

  void _onDiscoveryChanged() {
    final next = _discovery?.keyword ?? '';
    if (_searchController.text != next) {
      _searchController.text = next;
    }
    _keyword = next;
    _refresh();
  }

  Future<void> _refresh() async {
    await _loadIndustryOptions();
    _page = 1;
    await _loadPage(reset: true);
  }

  Future<void> _loadIndustryOptions() async {
    if (!widget.showDiscoveryControls) {
      return;
    }
    final repository = _repository;
    if (repository == null || !repository.isConfigured) {
      return;
    }
    try {
      final filters = await repository.fetchPublicFilters();
      if (!mounted) {
        return;
      }
      setState(() {
        _industryOptions = List<String>.from(filters['industries'] ?? const []);
        _locationOptions = List<String>.from(filters['locations'] ?? const []);
        _categoryOptions = List<String>.from(filters['categories'] ?? const []);
        _employmentTypeOptions = List<String>.from(
          filters['employment_types'] ?? const [],
        );
        _experienceOptions = List<String>.from(filters['experiences'] ?? const []);
      });
    } on JobsRepositoryException {
      // Keep empty; UI shows honest unavailable copy.
    }
  }

  Future<void> _loadPage({required bool reset}) async {
    final repository = _repository;
    if (repository == null) {
      return;
    }
    final requestId = ++_requestId;
    setState(() {
      if (reset) {
        _loading = true;
        _error = null;
      } else {
        _loadingMore = true;
      }
    });

    try {
      final result = await repository.fetchJobs(
        page: _page,
        keyword: _keyword.isEmpty ? null : _keyword,
        industry: _filterIndustry,
        location: _filterLocation,
        category: _filterCategory,
        employmentType: _filterEmploymentType,
        experience: _filterExperience,
        sort: _filterSort,
      );
      if (!mounted || requestId != _requestId) {
        return;
      }
      setState(() {
        if (reset) {
          _jobs
            ..clear()
            ..addAll(result.jobs);
        } else {
          _jobs.addAll(result.jobs);
        }
        _source = result.source;
        _hasMore = result.hasMore;
        _total = result.total;
        _loading = false;
        _loadingMore = false;
        _error = null;
      });
    } on JobsRepositoryException catch (error) {
      if (!mounted || requestId != _requestId) {
        return;
      }
      setState(() {
        if (!reset) {
          _page = _page > 1 ? _page - 1 : 1;
        }
        _error = error;
        _loading = false;
        _loadingMore = false;
        if (reset) {
          _jobs.clear();
          _total = null;
          _hasMore = false;
        }
      });
    }
  }

  Future<void> _loadMore() async {
    if (!_hasMore || _loadingMore || _loading) {
      return;
    }
    _page += 1;
    await _loadPage(reset: false);
  }

  void _submitSearch() {
    final next = _searchController.text.trim();
    if (_discovery != null) {
      _discovery!.search(next);
      return;
    }
    _keyword = next;
    _refresh();
  }

  void _clearSearch() {
    _searchController.clear();
    if (_discovery != null) {
      _discovery!.clear();
      return;
    }
    _keyword = '';
    _refresh();
  }

  Future<void> _openWeb(Uri uri) async {
    final ok = await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!ok && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Could not open OurNear Hire in the browser.'),
        ),
      );
    }
  }

  Future<void> _toggleSaved(PublicJob job) async {
    final repository = _repository;
    if (repository is! LiveJobsRepository) {
      return;
    }
    if (_auth?.status != AuthStatus.authenticated) {
      if (!mounted) {
        return;
      }
      await Navigator.of(context).push(
        MaterialPageRoute<void>(builder: (_) => const LoginScreen()),
      );
      return;
    }
    if (_savingSlug != null) {
      return;
    }
    final save = !(job.isSaved ?? false);
    setState(() => _savingSlug = job.slug);
    try {
      final saved = await repository.setJobSaved(job.slug, save: save);
      if (!mounted) {
        return;
      }
      setState(() {
        final index = _jobs.indexWhere((item) => item.slug == job.slug);
        if (index >= 0) {
          _jobs[index] = _jobs[index].copyWithSaved(saved);
        }
        _savingSlug = null;
      });
    } on JobsRepositoryException catch (error) {
      if (!mounted) {
        return;
      }
      setState(() => _savingSlug = null);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(error.message)),
      );
    }
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
        title: Text(widget.appBarTitle),
        actions: [
          IconButton(
            onPressed: _loading ? null : _refresh,
            tooltip: 'Refresh jobs',
            icon: const Icon(TablerIcons.refresh),
          ),
        ],
      ),
      body: RefreshIndicator(onRefresh: _refresh, child: _buildBody(context)),
    );
  }

  Widget _buildBody(BuildContext context) {
    if (_loading && _jobs.isEmpty) {
      return ListView(
        key: const Key('jobs-results'),
        controller: _scrollController,
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        children: [
          if (widget.showDiscoveryControls) ..._searchControls(context),
          const SizedBox(height: 24),
          Semantics(
            label: 'Loading jobs',
            child: const Center(child: CircularProgressIndicator()),
          ),
        ],
      );
    }

    return ListView(
      key: const Key('jobs-results'),
      controller: _scrollController,
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(16),
      children: [
        if (widget.showDiscoveryControls) ...[
          ..._searchControls(context),
          const SizedBox(height: 12),
          if (_industryOptions.isEmpty)
            Text(
              'Industry filters load from OurNear when the API is configured.',
              key: const Key('jobs-filters-unavailable'),
              style: Theme.of(context).textTheme.bodySmall,
            )
          else
            DropdownButtonFormField<String?>(
              key: const Key('jobs-industry-filter'),
              initialValue: _filterIndustry,
              decoration: const InputDecoration(labelText: 'Industry'),
              items: [
                const DropdownMenuItem<String?>(
                  value: null,
                  child: Text('All industries'),
                ),
                for (final option in _industryOptions)
                  DropdownMenuItem<String?>(
                    value: option,
                    child: Text(option),
                  ),
              ],
              onChanged: (value) {
                setState(() => _filterIndustry = value);
                _refresh();
              },
            ),
          if (_locationOptions.isNotEmpty)
            _catalogueDropdown(
              keyName: 'jobs-location-filter',
              label: 'Location',
              emptyLabel: 'All locations',
              value: _filterLocation,
              options: _locationOptions,
              onChanged: (value) => _filterLocation = value,
            ),
          if (_categoryOptions.isNotEmpty)
            _catalogueDropdown(
              keyName: 'jobs-category-filter',
              label: 'Category',
              emptyLabel: 'All categories',
              value: _filterCategory,
              options: _categoryOptions,
              onChanged: (value) => _filterCategory = value,
            ),
          if (_employmentTypeOptions.isNotEmpty)
            _catalogueDropdown(
              keyName: 'jobs-employment-type-filter',
              label: 'Employment type',
              emptyLabel: 'All employment types',
              value: _filterEmploymentType,
              options: _employmentTypeOptions,
              onChanged: (value) => _filterEmploymentType = value,
            ),
          if (_experienceOptions.isNotEmpty)
            _catalogueDropdown(
              keyName: 'jobs-experience-filter',
              label: 'Experience',
              emptyLabel: 'All experience levels',
              value: _filterExperience,
              options: _experienceOptions,
              onChanged: (value) => _filterExperience = value,
            ),
          _catalogueDropdown(
            keyName: 'jobs-sort',
            label: 'Sort',
            emptyLabel: 'Newest',
            value: _filterSort,
            options: const ['oldest', 'title'],
            onChanged: (value) => _filterSort = value,
          ),
          const SizedBox(height: 12),
        ],
        ..._results(context),
        const SizedBox(height: 16),
        Text(
          'Web fallback (separate from JSON API)',
          style: Theme.of(context).textTheme.labelSmall,
        ),
        const SizedBox(height: 8),
        if (ApiConfig.hasWebBase)
          OutlinedButton.icon(
            onPressed: () {
              final uri = ApiConfig.jobsIndexUri();
              if (uri != null) {
                _openWeb(uri);
              }
            },
            icon: const Icon(TablerIcons.external_link),
            label: const Text('Browse jobs on web'),
          )
        else
          Text(
            'Optional: --dart-define=OURNEAR_WEB_BASE=… for browser fallback.',
            style: Theme.of(context).textTheme.bodySmall,
          ),
      ],
    );
  }

  Widget _catalogueDropdown({
    required String keyName,
    required String label,
    required String emptyLabel,
    required String? value,
    required List<String> options,
    required void Function(String? value) onChanged,
  }) {
    return Padding(
      padding: const EdgeInsets.only(top: 8),
      child: DropdownButtonFormField<String?>(
        key: Key(keyName),
        initialValue: options.contains(value) || value == null ? value : null,
        decoration: InputDecoration(labelText: label),
        items: [
          DropdownMenuItem<String?>(value: null, child: Text(emptyLabel)),
          for (final option in options)
            DropdownMenuItem<String?>(value: option, child: Text(option)),
        ],
        onChanged: (next) {
          setState(() => onChanged(next));
          _refresh();
        },
      ),
    );
  }

  List<Widget> _searchControls(BuildContext context) {
    return [
      TextField(
        key: const Key('job-search-field'),
        controller: _searchController,
        textInputAction: TextInputAction.search,
        decoration: const InputDecoration(
          labelText: 'Search jobs',
          helperText: 'Sent as one keyword to the jobs API.',
        ),
        onSubmitted: (_) => _submitSearch(),
      ),
      const SizedBox(height: 8),
      Wrap(
        spacing: 8,
        runSpacing: 8,
        children: [
          FilledButton(onPressed: _submitSearch, child: const Text('Search')),
          OutlinedButton(onPressed: _clearSearch, child: const Text('Clear')),
        ],
      ),
    ];
  }

  List<Widget> _results(BuildContext context) {
    final error = _error;
    if (error != null && _jobs.isEmpty) {
      if (error.kind == JobsFailureKind.notConfigured) {
        return [
          PlatformEmptyState(
            icon: TablerIcons.plug,
            title: 'Jobs are not configured',
            message: error.message,
            action: FilledButton(
              onPressed: _refresh,
              child: const Text('Retry'),
            ),
          ),
        ];
      }
      if (error.kind == JobsFailureKind.offline) {
        return [
          PlatformOfflineState(message: error.message, onRetry: _refresh),
        ];
      }
      return [
        PlatformErrorState(
          title: 'Could not load jobs',
          message: error.message,
          onRetry: _refresh,
        ),
      ];
    }

    if (_jobs.isEmpty) {
      return [
        PlatformEmptyState(
          icon: TablerIcons.briefcase,
          title: 'No published jobs',
          message: _keyword.isEmpty
              ? 'The jobs list responded and did not include any jobs.'
              : 'No published jobs match this keyword.',
        ),
      ];
    }

    return [
      if (_source == JobsDataSource.mockPreview) ...[
        const HireNoticeBanner(
          message:
              'Demo sample jobs. These rows are not from the OurNear server.',
        ),
        const SizedBox(height: OurNearTokens.space12),
      ],
      if (_total != null)
        Text(
          '$_total published jobs',
          style: Theme.of(context).textTheme.bodySmall,
        ),
      const SizedBox(height: 12),
      for (final job in _jobs)
        Padding(
          padding: const EdgeInsets.only(bottom: 10),
          child: JobCard(
            job: job,
            onTap: () => _openJob(job),
            onSaveToggle: job.isSaved != null ? () => _toggleSaved(job) : null,
            saveBusy: _savingSlug == job.slug,
          ),
        ),
      if (_hasMore)
        OutlinedButton(
          onPressed: _loadingMore ? null : _loadMore,
          child: _loadingMore
              ? const SizedBox(
                  width: 18,
                  height: 18,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : const Text('Load more'),
        ),
      if (error != null)
        PlatformErrorState(
          title: 'Could not load more jobs',
          message: error.message,
          onRetry: _loadMore,
        ),
    ];
  }
}
