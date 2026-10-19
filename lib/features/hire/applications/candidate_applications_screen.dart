import 'package:flutter/material.dart';
import 'package:flutter_tabler_icons/flutter_tabler_icons.dart';
import 'package:ournear_mobile/core/auth/auth_controller.dart';
import 'package:ournear_mobile/core/navigation/root_tab_shell.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';
import 'package:ournear_mobile/core/widgets/ournear_page_header.dart';
import 'package:ournear_mobile/features/auth/login_screen.dart';
import 'package:ournear_mobile/features/hire/communication/communication_routes.dart';
import 'package:ournear_mobile/core/widgets/ournear_top_app_bar.dart';
import 'package:ournear_mobile/core/widgets/platform_empty_state.dart';
import 'package:ournear_mobile/core/widgets/platform_error_state.dart';
import 'package:ournear_mobile/core/widgets/platform_loading_state.dart';
import 'package:ournear_mobile/core/widgets/platform_offline_state.dart';
import 'package:ournear_mobile/core/widgets/ournear_primary_button.dart';
import 'package:ournear_mobile/features/hire/applications/applications_controller.dart';
import 'package:ournear_mobile/features/hire/applications/candidate_application_detail_screen.dart';
import 'package:ournear_mobile/features/hire/applications/data/applications_repository.dart';
import 'package:ournear_mobile/features/hire/applications/models/candidate_application.dart';
import 'package:ournear_mobile/features/hire/applications/models/candidate_application_status.dart';
import 'package:ournear_mobile/features/hire/applications/widgets/application_list_tile.dart';
import 'package:ournear_mobile/features/hire/applications/widgets/applications_demo_banner.dart';
import 'package:provider/provider.dart';

/// Candidate root-tab screen for tracking job applications.
class CandidateApplicationsScreen extends StatefulWidget {
  const CandidateApplicationsScreen({super.key, this.repository});

  final ApplicationsRepository? repository;

  @override
  State<CandidateApplicationsScreen> createState() =>
      _CandidateApplicationsScreenState();
}

class _CandidateApplicationsScreenState
    extends State<CandidateApplicationsScreen> {
  ApplicationsController? _controller;
  AuthController? _auth;
  var _bootstrapped = false;

  ApplicationsRepository _resolveRepository() {
    final injected = widget.repository;
    if (injected != null) {
      return injected;
    }
    return context.read<ApplicationsRepository>();
  }

  ApplicationsController get _controllerInstance {
    final controller = _controller;
    if (controller != null) {
      return controller;
    }
    throw StateError('ApplicationsController not initialized.');
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _controller ??= ApplicationsController(_resolveRepository());
    final auth = _readAuth();
    if (!identical(auth, _auth)) {
      _auth?.removeListener(_onAuthChanged);
      _auth = auth;
      _auth?.addListener(_onAuthChanged);
      _syncWithAuth();
    }
    if (!_bootstrapped) {
      _bootstrapped = true;
      _maybeInitialLoad();
    }
  }

  bool get _usesLiveRepository {
    final repo = widget.repository;
    if (repo != null) {
      return repo is LiveApplicationsRepository;
    }
    try {
      return context.read<ApplicationsRepository>() is LiveApplicationsRepository;
    } on ProviderNotFoundException {
      return true;
    }
  }

  void _maybeInitialLoad() {
    if (_usesLiveRepository) {
      final auth = _readAuth();
      if (auth == null) {
        _controllerInstance.refresh();
        return;
      }
      if (auth.bootstrapSettled &&
          auth.status == AuthStatus.authenticated) {
        _controllerInstance.refresh();
      }
      return;
    }
    _controllerInstance.refresh();
  }

  ApplicationsRepository _readRepository() => _resolveRepository();

  AuthController? _readAuth() {
    try {
      return context.read<AuthController>();
    } on ProviderNotFoundException {
      return null;
    }
  }

  void _onAuthChanged() {
    _syncWithAuth();
  }

  void _syncWithAuth() {
    final auth = _auth;
    if (auth == null) {
      return;
    }
    if (auth.status == AuthStatus.authenticated) {
      if (_controllerInstance.viewState == ApplicationsViewState.initial ||
          _controllerInstance.dataSource == null) {
        _controllerInstance.refresh();
      }
      return;
    }
    if (auth.bootstrapSettled) {
      _controllerInstance.clearForSignOut();
    }
  }

  @override
  void dispose() {
    _auth?.removeListener(_onAuthChanged);
    _controller?.dispose();
    super.dispose();
  }

  void _openJobsTab() {
    context.findAncestorStateOfType<RootTabShellState>()?.goToId('jobs');
  }

  Future<void> _openSignIn() async {
    await Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => const LoginScreen(initialTabIndex: 0),
      ),
    );
    if (_auth?.status == AuthStatus.authenticated) {
      await _controllerInstance.refresh(userInitiated: true);
    }
  }

  void _openDetail(CandidateApplication application) {
    final repository = widget.repository ?? _readRepository();
    final live =
        _controllerInstance.dataSource == ApplicationsDataSource.liveApi;
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (context) => live
            ? CandidateApplicationDetailScreen.fetch(
                applicationId: application.id,
                repository: repository,
                dataSource: ApplicationsDataSource.liveApi,
                initialApplication: application,
              )
            : CandidateApplicationDetailScreen(
                application: application,
                dataSource: _controllerInstance.dataSource,
              ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final controller = _controller;
    if (controller == null) {
      return const Scaffold(
        body: Center(child: PlatformLoadingState(message: 'Loading applications…')),
      );
    }

    return ChangeNotifierProvider.value(
      value: controller,
      child: Scaffold(
        appBar: OurNearTopAppBar(
          title: 'Applications',
          onMessagesTap: () => openCandidateMessages(context),
          onNotificationsTap: () => openCandidateNotifications(context),
          actions: [
            Consumer<ApplicationsController>(
              builder: (context, controller, _) {
                return IconButton(
                  tooltip: 'Refresh applications',
                  onPressed: controller.refreshing
                      ? null
                      : () => controller.refresh(userInitiated: true),
                  icon: controller.refreshing
                      ? const SizedBox(
                          width: OurNearTokens.iconSm,
                          height: OurNearTokens.iconSm,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Icon(TablerIcons.refresh),
                );
              },
            ),
          ],
        ),
        body: Consumer<ApplicationsController>(
          builder: (context, controller, _) {
            final auth = _readAuth();
            if (_usesLiveRepository &&
                auth?.bootstrapSettled == true &&
                auth?.status != AuthStatus.authenticated &&
                controller.viewState == ApplicationsViewState.initial) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: OurNearTokens.pagePadding(context),
                children: [
                  const OurNearPageHeader(
                    title: 'Your applications',
                    subtitle:
                        'Sign in to load application history from OurNear Hire.',
                  ),
                  const SizedBox(height: OurNearTokens.space16),
                  OurNearPrimaryButton(
                    key: const Key('applications-sign-in'),
                    label: 'Sign in',
                    icon: TablerIcons.login,
                    onPressed: _openSignIn,
                  ),
                ],
              );
            }
            return RefreshIndicator(
              onRefresh: () => controller.refresh(userInitiated: true),
              child: _ApplicationsBody(
                onBrowseJobs: _openJobsTab,
                onOpenDetail: _openDetail,
                onRetry: () => controller.refresh(userInitiated: true),
                onSignIn: _openSignIn,
              ),
            );
          },
        ),
      ),
    );
  }
}

class _ApplicationsBody extends StatelessWidget {
  const _ApplicationsBody({
    required this.onBrowseJobs,
    required this.onOpenDetail,
    required this.onRetry,
    required this.onSignIn,
  });

  final VoidCallback onBrowseJobs;
  final void Function(CandidateApplication application) onOpenDetail;
  final VoidCallback onRetry;
  final VoidCallback onSignIn;

  @override
  Widget build(BuildContext context) {
    final controller = context.watch<ApplicationsController>();

    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: OurNearTokens.pagePadding(context),
      children: [
        OurNearPageHeader(
          title: 'Your applications',
          subtitle: controller.dataSource == ApplicationsDataSource.liveApi
              ? 'Loaded from your OurNear Hire account.'
              : 'History appears here when you apply to jobs while signed in.',
        ),
        const SizedBox(height: OurNearTokens.space12),
        if (controller.errorMessage != null &&
            controller.viewState == ApplicationsViewState.populated) ...[
          MaterialBanner(
            content: Text(controller.errorMessage!),
            actions: [
              TextButton(onPressed: onRetry, child: const Text('Retry')),
            ],
          ),
          const SizedBox(height: OurNearTokens.space12),
        ],
        switch (controller.viewState) {
          ApplicationsViewState.initial || ApplicationsViewState.loading =>
            const PlatformLoadingState(message: 'Loading applications…'),
          ApplicationsViewState.offline => PlatformOfflineState(
            onRetry: onRetry,
          ),
          ApplicationsViewState.sessionExpired => PlatformErrorState(
            title: 'Sign in again',
            message:
                controller.errorMessage ??
                'Your session has expired. Nothing was submitted.',
            onRetry: onSignIn,
          ),
          ApplicationsViewState.error => PlatformErrorState(
            title: controller.failureKind == ApplicationsFailureKind.unavailable
                ? 'Could not load applications'
                : 'Could not load applications',
            message: controller.errorMessage ?? 'Something went wrong.',
            onRetry: onRetry,
          ),
          ApplicationsViewState.empty => PlatformEmptyState(
            icon: TablerIcons.file_description,
            title: 'No applications yet',
            message:
                'When you apply to roles from Jobs, your submissions appear here.',
            action: OurNearPrimaryButton(
              label: 'Browse jobs',
              icon: TablerIcons.briefcase,
              onPressed: onBrowseJobs,
            ),
          ),
          ApplicationsViewState.populated => _PopulatedApplicationsList(
            applications: controller.visibleApplications,
            showDemoBanner:
                controller.dataSource == ApplicationsDataSource.mockDemo,
            showDemoFilters:
                controller.dataSource == ApplicationsDataSource.mockDemo,
            statusFilter: controller.statusFilter,
            onStatusFilter: controller.setStatusFilter,
            onOpenDetail: onOpenDetail,
          ),
        },
      ],
    );
  }
}

class _PopulatedApplicationsList extends StatelessWidget {
  const _PopulatedApplicationsList({
    required this.applications,
    required this.showDemoBanner,
    required this.showDemoFilters,
    required this.statusFilter,
    required this.onStatusFilter,
    required this.onOpenDetail,
  });

  final List<CandidateApplication> applications;
  final bool showDemoBanner;
  final bool showDemoFilters;
  final CandidateApplicationStatus? statusFilter;
  final ValueChanged<CandidateApplicationStatus?> onStatusFilter;
  final void Function(CandidateApplication application) onOpenDetail;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (showDemoBanner) const ApplicationsDemoBanner(),
        if (showDemoBanner) const SizedBox(height: OurNearTokens.space12),
        if (showDemoFilters) ...[
          Text(
            'These labels filter this complete demo list. They are not server statuses.',
            key: const Key('demo-status-filters'),
            style: Theme.of(context).textTheme.bodySmall,
          ),
          const SizedBox(height: OurNearTokens.space8),
          Wrap(
            spacing: OurNearTokens.space8,
            runSpacing: OurNearTokens.space8,
            children: [
              FilterChip(
                label: const Text('All'),
                selected: statusFilter == null,
                onSelected: (_) => onStatusFilter(null),
              ),
              for (final status in CandidateApplicationStatus.values)
                FilterChip(
                  label: Text(status.label),
                  selected: statusFilter == status,
                  onSelected: (_) => onStatusFilter(status),
                ),
            ],
          ),
          const SizedBox(height: OurNearTokens.space12),
        ],
        if (applications.isEmpty)
          const Text('No demo applications use this label.')
        else
          for (final application in applications) ...[
            ApplicationListTile(
              application: application,
              onTap: () => onOpenDetail(application),
            ),
            const SizedBox(height: OurNearTokens.listGap),
          ],
      ],
    );
  }
}
