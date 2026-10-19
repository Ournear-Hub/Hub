import 'package:flutter/material.dart';
import 'package:flutter_tabler_icons/flutter_tabler_icons.dart';
import 'package:ournear_mobile/core/theme/hire_theme_context.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';
import 'package:ournear_mobile/core/widgets/platform_empty_state.dart';
import 'package:ournear_mobile/core/widgets/platform_loading_state.dart';
import 'package:ournear_mobile/features/hire/jobs/data/jobs_repository.dart';
import 'package:ournear_mobile/features/hire/jobs/job_card.dart';
import 'package:ournear_mobile/features/hire/hire_job_detail_screen.dart';

class SavedJobsScreen extends StatefulWidget {
  const SavedJobsScreen({super.key, required this.repository});

  final JobsRepository repository;

  @override
  State<SavedJobsScreen> createState() => _SavedJobsScreenState();
}

class _SavedJobsScreenState extends State<SavedJobsScreen> {
  String? _message;
  bool _loading = true;
  JobsPage? _page;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _message = null;
      _page = null;
    });
    try {
      final page = await widget.repository.fetchSavedJobs();
      if (mounted) {
        setState(() {
          _loading = false;
          _page = page;
        });
      }
    } on JobsRepositoryException catch (error) {
      if (mounted) {
        setState(() {
          _loading = false;
          _message = error.message;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: context.hireCanvas,
      appBar: AppBar(title: const Text('Saved jobs')),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_loading) {
      return const Center(
        child: PlatformLoadingState(message: 'Loading saved jobs'),
      );
    }

    final jobs = _page?.jobs ?? [];
    if (jobs.isNotEmpty) {
      return ListView.separated(
        padding: const EdgeInsets.all(OurNearTokens.pageHorizontal),
        itemCount: jobs.length,
        separatorBuilder: (context, index) =>
            const SizedBox(height: OurNearTokens.space12),
        itemBuilder: (context, index) {
          final job = jobs[index];
          return JobCard(
            job: job,
            onTap: () {
              Navigator.of(context).push(
                MaterialPageRoute<void>(
                  builder: (_) => HireJobDetailScreen(
                    slug: job.slug,
                    repository: widget.repository,
                  ),
                ),
              );
            },
          );
        },
      );
    }

    return ListView(
      padding: const EdgeInsets.all(OurNearTokens.pageHorizontal),
      children: [
        PlatformEmptyState(
          key: const Key('saved-jobs-unavailable'),
          icon: TablerIcons.bookmark_off,
          title: jobs.isEmpty && _page != null
              ? 'No saved jobs yet'
              : 'Saved jobs unavailable',
          message: _message ??
              (jobs.isEmpty && _page != null
                  ? 'Roles you bookmark while signed in appear here.'
                  : LiveJobsRepository.savedJobsUnavailable),
          action: OutlinedButton(
            key: const Key('saved-jobs-retry'),
            onPressed: _load,
            child: const Text('Retry'),
          ),
        ),
      ],
    );
  }
}
