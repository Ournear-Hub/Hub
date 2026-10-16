import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/api/hire_api_service.dart';
import 'package:ournear_mobile/features/hire/applications/data/applications_repository.dart';
import 'package:ournear_mobile/features/hire/profile/data/documents_repository.dart';
import 'package:ournear_mobile/features/hire/profile/data/profile_repository.dart';
import 'package:provider/provider.dart';

/// Resolves candidate repositories from [Provider] so API calls include bearer tokens.
class CandidateRepositoryResolver {
  const CandidateRepositoryResolver._();

  static HireApiService _api(BuildContext context) {
    try {
      return context.read<HireApiService>();
    } on ProviderNotFoundException {
      return HireApiService();
    }
  }

  static ProfileRepository profile(
    BuildContext context, {
    ProfileRepository? injected,
  }) {
    if (injected != null) {
      return injected;
    }
    try {
      return context.read<ProfileRepository>();
    } on ProviderNotFoundException {
      return LiveProfileRepository(api: _api(context));
    }
  }

  static ApplicationsRepository applications(
    BuildContext context, {
    ApplicationsRepository? injected,
  }) {
    if (injected != null) {
      return injected;
    }
    try {
      return context.read<ApplicationsRepository>();
    } on ProviderNotFoundException {
      return LiveApplicationsRepository(api: _api(context));
    }
  }

  static DocumentsRepository documents(
    BuildContext context, {
    DocumentsRepository? injected,
  }) {
    if (injected != null) {
      return injected;
    }
    try {
      return context.read<DocumentsRepository>();
    } on ProviderNotFoundException {
      return LiveDocumentsRepository(api: _api(context));
    }
  }
}
