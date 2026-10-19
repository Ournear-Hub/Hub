import 'dart:async';

import 'package:http/http.dart' as http;
import 'package:ournear_mobile/core/api/hire_api_exception.dart';
import 'package:ournear_mobile/core/api/hire_api_service.dart';

enum DocumentDataSource { liveApi, mockDemo }

enum DocumentFailureKind { offline, unavailable, validation, unknown }

class DocumentRepositoryException implements Exception {
  const DocumentRepositoryException(this.kind, this.message);

  final DocumentFailureKind kind;
  final String message;

  factory DocumentRepositoryException.unavailable([String? message]) {
    return DocumentRepositoryException(
      DocumentFailureKind.unavailable,
      message ?? 'Documents are not available.',
    );
  }

  factory DocumentRepositoryException.validation(String message) {
    return DocumentRepositoryException(DocumentFailureKind.validation, message);
  }

  @override
  String toString() => message;
}

/// Metadata for one uploaded resume. File bytes are not kept here.
class UploadedResumeRecord {
  const UploadedResumeRecord({
    required this.fileName,
    this.title,
    this.id,
    this.isPrimary = false,
  });

  final String fileName;
  final String? title;
  final int? id;
  final bool isPrimary;
}

class ResumeLibraryItem {
  const ResumeLibraryItem({
    required this.id,
    required this.fileName,
    this.title,
    this.isPrimary = false,
  });

  final int id;
  final String fileName;
  final String? title;
  final bool isPrimary;
}

class DocumentsSnapshot {
  const DocumentsSnapshot({
    required this.source,
    required this.contractAvailable,
    this.hirePreviewText,
    this.uploadedResume,
    this.library = const [],
    required this.persisted,
  });

  final DocumentDataSource source;

  /// False when no resume generation or upload contract exists.
  final bool contractAvailable;
  final String? hirePreviewText;
  final UploadedResumeRecord? uploadedResume;
  final List<ResumeLibraryItem> library;

  /// True only after a verified documents endpoint confirms a write.
  final bool persisted;
}

abstract class DocumentsRepository {
  Future<DocumentsSnapshot> fetchDocuments();

  Future<DocumentsSnapshot> replaceUploadedResume(
    String fileName, {
    List<int>? bytes,
  });

  Future<DocumentsSnapshot> deleteResume(int id);

  Future<DocumentsSnapshot> makeResumePrimary(int id);
}

class LiveDocumentsRepository implements DocumentsRepository {
  LiveDocumentsRepository({HireApiService? api}) : _api = api ?? HireApiService();

  final HireApiService _api;

  static const readUnavailable =
      'Resume and documents are not connected. Configure OURNEAR_API_BASE and sign in.';

  static const writeUnavailable =
      'Resume upload is not connected. No file was uploaded.';

  static const bytesRequired =
      'Choose a PDF or Word file to upload. A file name alone is not sent.';

  static const allowedExtensions = ['pdf', 'doc', 'docx'];

  @override
  Future<DocumentsSnapshot> fetchDocuments() async {
    if (!_api.isConfigured) {
      throw DocumentRepositoryException.unavailable(readUnavailable);
    }
    return _guard(() async {
      final preview = await _api.fetchResumeBuilderPreviewText();
      final resumes = await _api.fetchResumeLibrary();
      final library = <ResumeLibraryItem>[];
      UploadedResumeRecord? fallback;
      for (final row in resumes) {
        final id = row['id'] is int
            ? row['id'] as int
            : int.tryParse('${row['id']}');
        final title = row['title'] as String?;
        final name = row['original_filename'] as String? ??
            row['filename'] as String? ??
            title;
        if (name == null || name.trim().isEmpty) {
          continue;
        }
        final isPrimary = row['is_primary'] as bool? ?? false;
        if (id != null) {
          library.add(
            ResumeLibraryItem(
              id: id,
              fileName: name.trim(),
              title: title?.trim(),
              isPrimary: isPrimary,
            ),
          );
        }
        fallback ??= UploadedResumeRecord(
          fileName: name.trim(),
          title: title?.trim(),
          id: id,
          isPrimary: isPrimary,
        );
      }
      ResumeLibraryItem? primary;
      for (final row in library) {
        if (row.isPrimary) {
          primary = row;
          break;
        }
      }
      return DocumentsSnapshot(
        source: DocumentDataSource.liveApi,
        contractAvailable: true,
        hirePreviewText: preview,
        uploadedResume: primary == null
            ? fallback
            : UploadedResumeRecord(
                fileName: primary.fileName,
                title: primary.title,
                id: primary.id,
                isPrimary: primary.isPrimary,
              ),
        library: library,
        persisted: false,
      );
    });
  }

  @override
  Future<DocumentsSnapshot> replaceUploadedResume(
    String fileName, {
    List<int>? bytes,
  }) async {
    if (!_api.isConfigured) {
      throw DocumentRepositoryException.unavailable(writeUnavailable);
    }
    if (bytes == null || bytes.isEmpty) {
      throw DocumentRepositoryException.validation(bytesRequired);
    }
    final trimmed = fileName.trim();
    if (trimmed.isEmpty) {
      throw DocumentRepositoryException.validation('Enter a file name.');
    }
    final extension = trimmed.contains('.')
        ? trimmed.split('.').last.toLowerCase()
        : '';
    if (!allowedExtensions.contains(extension)) {
      throw DocumentRepositoryException.validation(
        'Supported file types: PDF, DOC, DOCX (max 5 MB on OurNear).',
      );
    }
    return _guard(() async {
      await _api.uploadResumeFile(
        bytes: bytes,
        filename: trimmed,
        title: trimmed,
      );
      final refreshed = await fetchDocuments();
      return DocumentsSnapshot(
        source: DocumentDataSource.liveApi,
        contractAvailable: true,
        hirePreviewText: refreshed.hirePreviewText,
        uploadedResume: refreshed.uploadedResume,
        persisted: true,
      );
    });
  }

  @override
  Future<DocumentsSnapshot> deleteResume(int id) async {
    if (!_api.isConfigured) {
      throw DocumentRepositoryException.unavailable(writeUnavailable);
    }
    return _guard(() async {
      await _api.deleteResume(id);
      final refreshed = await fetchDocuments();
      return DocumentsSnapshot(
        source: DocumentDataSource.liveApi,
        contractAvailable: true,
        hirePreviewText: refreshed.hirePreviewText,
        uploadedResume: refreshed.uploadedResume,
        library: refreshed.library,
        persisted: true,
      );
    });
  }

  @override
  Future<DocumentsSnapshot> makeResumePrimary(int id) async {
    if (!_api.isConfigured) {
      throw DocumentRepositoryException.unavailable(writeUnavailable);
    }
    return _guard(() async {
      await _api.makeResumePrimary(id);
      final refreshed = await fetchDocuments();
      return DocumentsSnapshot(
        source: DocumentDataSource.liveApi,
        contractAvailable: true,
        hirePreviewText: refreshed.hirePreviewText,
        uploadedResume: refreshed.uploadedResume,
        library: refreshed.library,
        persisted: true,
      );
    });
  }

  Future<T> _guard<T>(Future<T> Function() action) async {
    try {
      return await action();
    } on DocumentRepositoryException {
      rethrow;
    } on HireApiException catch (error) {
      if (error.statusCode == 422) {
        throw DocumentRepositoryException.validation(error.message);
      }
      throw DocumentRepositoryException.unavailable(error.message);
    } on TimeoutException {
      throw DocumentRepositoryException.unavailable(
        'Could not reach OurNear. Check your connection and try again.',
      );
    } on http.ClientException {
      throw DocumentRepositoryException.unavailable(
        'Could not reach OurNear. Check your connection and try again.',
      );
    } catch (error) {
      throw DocumentRepositoryException(
        DocumentFailureKind.unknown,
        'Could not complete the documents request. $error',
      );
    }
  }
}
