import 'package:flutter/foundation.dart';
import 'package:ournear_mobile/features/hire/applications/data/applications_repository.dart';
import 'package:ournear_mobile/features/hire/applications/models/candidate_application.dart';
import 'package:ournear_mobile/features/hire/applications/models/candidate_application_status.dart';

enum ApplicationsViewState {
  initial,
  loading,
  populated,
  empty,
  error,
  offline,
  sessionExpired,
}

class ApplicationsController extends ChangeNotifier {
  ApplicationsController(this._repository);

  final ApplicationsRepository _repository;

  ApplicationsViewState viewState = ApplicationsViewState.initial;
  List<CandidateApplication> applications = const [];
  ApplicationsDataSource? dataSource;
  ApplicationsFailureKind? failureKind;
  CandidateApplicationStatus? statusFilter;
  String? errorMessage;
  bool refreshing = false;

  /// Local view of [applications]. Filtering is valid for the complete demo list only.
  List<CandidateApplication> get visibleApplications {
    final filter = statusFilter;
    if (filter == null || dataSource != ApplicationsDataSource.mockDemo) {
      return applications;
    }
    return [
      for (final application in applications)
        if (application.status == filter) application,
    ];
  }

  void setStatusFilter(CandidateApplicationStatus? status) {
    statusFilter = status;
    notifyListeners();
  }

  Future<void> refresh({bool userInitiated = false}) async {
    if (viewState == ApplicationsViewState.initial || userInitiated) {
      if (applications.isEmpty) {
        viewState = ApplicationsViewState.loading;
      } else {
        refreshing = true;
      }
      errorMessage = null;
      notifyListeners();
    }

    try {
      final result = await _repository.fetchApplications();
      applications = result.applications;
      dataSource = result.source;
      failureKind = null;
      viewState = applications.isEmpty
          ? ApplicationsViewState.empty
          : ApplicationsViewState.populated;
      errorMessage = null;
    } on ApplicationsRepositoryException catch (error) {
      errorMessage = error.message;
      failureKind = error.kind;
      if (applications.isNotEmpty) {
        viewState = ApplicationsViewState.populated;
      } else if (error.kind == ApplicationsFailureKind.offline) {
        viewState = ApplicationsViewState.offline;
      } else if (error.kind == ApplicationsFailureKind.sessionExpired) {
        viewState = ApplicationsViewState.sessionExpired;
      } else {
        viewState = ApplicationsViewState.error;
      }
    } finally {
      refreshing = false;
      notifyListeners();
    }
  }

  CandidateApplication? findById(String id) {
    for (final application in applications) {
      if (application.id == id) {
        return application;
      }
    }
    return null;
  }

  void clearForSignOut() {
    applications = const [];
    dataSource = null;
    failureKind = null;
    errorMessage = null;
    statusFilter = null;
    viewState = ApplicationsViewState.initial;
    refreshing = false;
    notifyListeners();
  }
}
