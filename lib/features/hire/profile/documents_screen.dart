import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/providers/candidate_repository_resolver.dart';
import 'package:ournear_mobile/core/theme/hire_theme_context.dart';
import 'package:ournear_mobile/features/hire/profile/data/documents_repository.dart';
import 'package:ournear_mobile/features/hire/profile/document_preview_screen.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';
import 'package:ournear_mobile/core/widgets/platform_loading_state.dart';
import 'package:ournear_mobile/features/hire/profile/profile_demo_banner.dart';
import 'package:ournear_mobile/features/hire/profile/widgets/profile_section_card.dart';
import 'package:ournear_mobile/features/hire/widgets/hire_notice_banner.dart';

class DocumentsScreen extends StatefulWidget {
  const DocumentsScreen({super.key, this.repository});

  final DocumentsRepository? repository;

  @override
  State<DocumentsScreen> createState() => _DocumentsScreenState();
}

class _DocumentsScreenState extends State<DocumentsScreen> {
  DocumentsSnapshot? _snapshot;
  DocumentRepositoryException? _error;
  var _loading = true;
  var _replacing = false;
  final _fileName = TextEditingController();
  List<int>? _pickedBytes;

  DocumentsRepository? _repository;
  var _initialized = false;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _repository ??= CandidateRepositoryResolver.documents(
      context,
      injected: widget.repository,
    );
    if (!_initialized) {
      _initialized = true;
      _load();
    }
  }

  @override
  void dispose() {
    _fileName.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final snapshot = await _repository!.fetchDocuments();
      if (!mounted) {
        return;
      }
      setState(() {
        _snapshot = snapshot;
        _loading = false;
      });
    } on DocumentRepositoryException catch (error) {
      if (!mounted) {
        return;
      }
      setState(() {
        _error = error;
        _loading = false;
      });
    }
  }

  Future<void> _deleteResume(int id) async {
    setState(() {
      _replacing = true;
      _error = null;
    });
    try {
      final snapshot = await _repository!.deleteResume(id);
      if (!mounted) {
        return;
      }
      setState(() {
        _snapshot = snapshot;
        _replacing = false;
      });
    } on DocumentRepositoryException catch (error) {
      if (!mounted) {
        return;
      }
      setState(() {
        _error = error;
        _replacing = false;
      });
    }
  }

  Future<void> _makePrimary(int id) async {
    setState(() {
      _replacing = true;
      _error = null;
    });
    try {
      final snapshot = await _repository!.makeResumePrimary(id);
      if (!mounted) {
        return;
      }
      setState(() {
        _snapshot = snapshot;
        _replacing = false;
      });
    } on DocumentRepositoryException catch (error) {
      if (!mounted) {
        return;
      }
      setState(() {
        _error = error;
        _replacing = false;
      });
    }
  }

  Future<void> _pickFile() async {
    final result = await FilePicker.platform.pickFiles(
      type: FileType.custom,
      allowedExtensions: LiveDocumentsRepository.allowedExtensions,
      withData: true,
    );
    if (result == null || result.files.isEmpty) {
      return;
    }
    final file = result.files.single;
    final bytes = file.bytes;
    if (bytes == null || bytes.isEmpty) {
      return;
    }
    setState(() {
      _pickedBytes = bytes;
      _fileName.text = file.name;
    });
  }

  Future<void> _replace() async {
    if (_replacing) {
      return;
    }
    setState(() => _replacing = true);
    try {
      final snapshot = await _repository!.replaceUploadedResume(
        _fileName.text,
        bytes: _pickedBytes,
      );
      if (!mounted) {
        return;
      }
      setState(() {
        _snapshot = snapshot;
        _error = null;
      });
    } on DocumentRepositoryException catch (error) {
      if (!mounted) {
        return;
      }
      setState(() => _error = error);
    } finally {
      if (mounted) {
        setState(() => _replacing = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final snapshot = _snapshot;
    final demo = snapshot?.source == DocumentDataSource.mockDemo;
    return Scaffold(
      backgroundColor: context.hireCanvas,
      appBar: AppBar(title: const Text('Resume and documents')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (_loading)
            const Center(
              child: PlatformLoadingState(message: 'Loading documents'),
            )
          else ...[
          if (demo) ...[
            const ProfileDemoBanner(),
            const SizedBox(height: OurNearTokens.space12),
          ],
          if (_error != null && snapshot == null) ...[
            HireNoticeBanner(
              tone: HireNoticeTone.neutral,
              message: _error!.message,
            ),
            const SizedBox(height: OurNearTokens.space12),
          ],
          ProfileSectionCard(
            title: 'Hire Resume',
            body: _hireCopy(snapshot),
          ),
          const SizedBox(height: OurNearTokens.space12),
          OutlinedButton(
            style: OutlinedButton.styleFrom(
              minimumSize: const Size.fromHeight(48),
            ),
            onPressed: () {
              Navigator.of(context).push(
                MaterialPageRoute<void>(
                  builder: (_) => DocumentPreviewScreen(
                    title: 'Hire Resume',
                    body: snapshot?.hirePreviewText,
                    demo: demo,
                  ),
                ),
              );
            },
            child: const Text('Preview Hire Resume'),
          ),
          const SizedBox(height: OurNearTokens.space20),
          ProfileSectionCard(
            title: 'My Resume',
            body:
                'My Resume is a separate uploaded file. It is not the generated Hire Resume.\n\n'
                '${_uploadedCopy(snapshot)}\n\n'
                'OurNear accepts PDF, DOC, or DOCX up to 5 MB when connected.',
          ),
          const SizedBox(height: OurNearTokens.space12),
          if (snapshot?.source != DocumentDataSource.liveApi)
            const HireNoticeBanner(
              tone: HireNoticeTone.info,
              message:
                  'Configure OURNEAR_API_BASE and sign in to upload to OurNear.',
            ),
          if (snapshot?.library.isNotEmpty == true) ...[
            for (final resume in snapshot!.library)
              ListTile(
                key: Key('resume-row-${resume.id}'),
                title: Text(resume.title ?? resume.fileName),
                subtitle: Text(
                  resume.isPrimary ? 'Primary resume' : resume.fileName,
                ),
                trailing: Wrap(
                  spacing: 8,
                  children: [
                    if (!resume.isPrimary)
                      TextButton(
                        onPressed: _replacing
                            ? null
                            : () => _makePrimary(resume.id),
                        child: const Text('Set primary'),
                      ),
                    TextButton(
                      onPressed: _replacing ? null : () => _deleteResume(resume.id),
                      child: const Text('Delete'),
                    ),
                  ],
                ),
              ),
            const SizedBox(height: OurNearTokens.space12),
          ],
          OutlinedButton(
            key: const Key('resume-pick-file'),
            onPressed: _replacing ? null : _pickFile,
            child: const Text('Choose file'),
          ),
          const SizedBox(height: 8),
          TextField(
            key: const Key('resume-file-name'),
            controller: _fileName,
            readOnly: snapshot?.source == DocumentDataSource.liveApi,
            decoration: const InputDecoration(labelText: 'Selected file'),
          ),
          const SizedBox(height: 8),
          FilledButton(
            key: const Key('resume-replace'),
            style: FilledButton.styleFrom(
              minimumSize: const Size.fromHeight(48),
            ),
            onPressed: _replacing ? null : _replace,
            child: Text(_replacing ? 'Please wait' : 'Replace My Resume'),
          ),
          if (_error != null && snapshot != null) ...[
            const SizedBox(height: 12),
            Text(_error!.message, key: const Key('documents-message')),
          ],
          if (snapshot != null && !snapshot.persisted && demo) ...[
            const SizedBox(height: 12),
            const Text('Nothing was uploaded to OurNear.'),
          ],
          ],
        ],
      ),
    );
  }

  String _hireCopy(DocumentsSnapshot? snapshot) {
    final preview = snapshot?.hirePreviewText;
    if (preview != null && preview.trim().isNotEmpty) {
      return 'A demo Hire Resume preview is available. It was not generated by OurNear.';
    }
    if (snapshot?.source == DocumentDataSource.liveApi) {
      return 'Hire Resume preview is loaded from OurNear when available.';
    }
    return 'Hire Resume generation is not connected. No resume was generated.';
  }

  String _uploadedCopy(DocumentsSnapshot? snapshot) {
    if (snapshot == null) {
      return 'My Resume status is not available until a documents service is connected.';
    }
    final file = snapshot.uploadedResume;
    if (file == null) {
      return 'No My Resume file is on record.';
    }
    if (snapshot.source == DocumentDataSource.liveApi) {
      return 'Uploaded: ${file.fileName}${file.title != null ? ' (${file.title})' : ''}.';
    }
    return 'Demo file name: ${file.fileName}. This file was not uploaded to OurNear.';
  }
}
