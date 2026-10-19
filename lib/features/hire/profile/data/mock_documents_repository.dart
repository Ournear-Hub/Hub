import 'package:ournear_mobile/features/hire/profile/data/documents_repository.dart';

/// One in-memory uploaded resume for tests. It is not an OurNear file.
class MockDocumentsRepository implements DocumentsRepository {
  MockDocumentsRepository({
    this.hirePreviewText,
    UploadedResumeRecord? uploadedResume,
    this.artificialDelay = Duration.zero,
  }) : _uploaded = uploadedResume;

  final String? hirePreviewText;
  final Duration artificialDelay;
  UploadedResumeRecord? _uploaded;

  DocumentsSnapshot _snapshot() {
    return DocumentsSnapshot(
      source: DocumentDataSource.mockDemo,
      contractAvailable: false,
      hirePreviewText: hirePreviewText,
      uploadedResume: _uploaded,
      persisted: false,
    );
  }

  @override
  Future<DocumentsSnapshot> fetchDocuments() async {
    if (artificialDelay > Duration.zero) {
      await Future<void>.delayed(artificialDelay);
    }
    return _snapshot();
  }

  @override
  Future<DocumentsSnapshot> replaceUploadedResume(
    String fileName, {
    List<int>? bytes,
  }) async {
    if (artificialDelay > Duration.zero) {
      await Future<void>.delayed(artificialDelay);
    }
    final trimmed = fileName.trim();
    if (trimmed.isEmpty) {
      throw DocumentRepositoryException.validation(
        'Enter a file name or cancel. File type and size rules are not available, and this check is not a server policy.',
      );
    }
    _uploaded = UploadedResumeRecord(fileName: trimmed);
    return _snapshot();
  }

  @override
  Future<DocumentsSnapshot> deleteResume(int id) async {
    throw DocumentRepositoryException.validation(
      'Demo resumes are not stored on OurNear. Nothing was deleted.',
    );
  }

  @override
  Future<DocumentsSnapshot> makeResumePrimary(int id) async {
    throw DocumentRepositoryException.validation(
      'Demo resumes are not stored on OurNear. The primary resume was not changed.',
    );
  }
}
