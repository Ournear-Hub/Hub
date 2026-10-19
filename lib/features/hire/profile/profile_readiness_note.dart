import 'package:flutter/material.dart';
import 'package:ournear_mobile/features/hire/profile/data/documents_repository.dart';
import 'package:ournear_mobile/features/hire/profile/data/profile_repository.dart';
import 'package:provider/provider.dart';

/// Shows canonical profile or document facts when a repository is already in scope.
class ProfileReadinessNote extends StatefulWidget {
  const ProfileReadinessNote({super.key});

  @override
  State<ProfileReadinessNote> createState() => _ProfileReadinessNoteState();
}

class _ProfileReadinessNoteState extends State<ProfileReadinessNote> {
  String? _note;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _load();
  }

  Future<void> _load() async {
    final lines = <String>[];
    ProfileRepository? profiles;
    DocumentsRepository? documents;
    try {
      profiles = context.read<ProfileRepository>();
    } on ProviderNotFoundException {
      profiles = null;
    }
    try {
      documents = context.read<DocumentsRepository>();
    } on ProviderNotFoundException {
      documents = null;
    }
    if (profiles != null) {
      try {
        final snapshot = await profiles.fetchProfile();
        final headline = snapshot.profile.headline;
        if (snapshot.source == ProfileDataSource.mockDemo) {
          lines.add(
            headline == null || headline.trim().isEmpty
                ? 'A demo profile is loaded. It is not saved on OurNear and will not be attached to an application.'
                : 'Demo headline: $headline. It is not saved on OurNear and will not be attached to an application.',
          );
        }
      } on ProfileRepositoryException {
        // Live profile stays on the static limitation sentence.
      }
    }
    if (documents != null) {
      try {
        final snapshot = await documents.fetchDocuments();
        final file = snapshot.uploadedResume;
        if (snapshot.source == DocumentDataSource.mockDemo && file != null) {
          lines.add(
            'Demo My Resume file name: ${file.fileName}. It is not attached to this application.',
          );
        }
      } on DocumentRepositoryException {
        // Live documents stay on the static resume sentence.
      }
    }
    if (!mounted) {
      return;
    }
    final next = lines.isEmpty ? null : lines.join('\n');
    if (next != _note) {
      setState(() => _note = next);
    }
  }

  @override
  Widget build(BuildContext context) {
    final note = _note;
    if (note == null) {
      return const SizedBox.shrink();
    }
    return Padding(padding: const EdgeInsets.only(top: 12), child: Text(note));
  }
}
