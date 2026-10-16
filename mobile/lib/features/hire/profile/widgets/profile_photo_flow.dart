import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:flutter_tabler_icons/flutter_tabler_icons.dart';
import 'package:image_picker/image_picker.dart';
import 'package:ournear_mobile/features/hire/profile/data/profile_repository.dart';

/// Pick, preview, and upload a candidate profile photo through [ProfileRepository].
class ProfilePhotoFlow {
  ProfilePhotoFlow._();

  static final ImagePicker _picker = ImagePicker();

  static Future<void> start(
    BuildContext context, {
    required ProfileRepository repository,
    required VoidCallback onUploaded,
  }) async {
    final source = await showModalBottomSheet<ImageSource>(
      context: context,
      showDragHandle: true,
      builder: (ctx) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(TablerIcons.camera),
              title: const Text('Take photo'),
              onTap: () => Navigator.of(ctx).pop(ImageSource.camera),
            ),
            ListTile(
              leading: const Icon(TablerIcons.photo),
              title: const Text('Choose from gallery'),
              onTap: () => Navigator.of(ctx).pop(ImageSource.gallery),
            ),
          ],
        ),
      ),
    );
    if (!context.mounted || source == null) {
      return;
    }

    XFile? picked;
    try {
      picked = await _picker.pickImage(
        source: source,
        maxWidth: 2048,
        maxHeight: 2048,
        imageQuality: 88,
      );
    } on Exception {
      if (!context.mounted) {
        return;
      }
      _showMessage(
        context,
        'Could not open the photo picker. Check app permissions and try again.',
      );
      return;
    }
    if (!context.mounted || picked == null) {
      return;
    }

    final bytes = await picked.readAsBytes();
    final filename = picked.name.trim().isNotEmpty ? picked.name : 'photo.jpg';
    if (!context.mounted) {
      return;
    }

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Save profile photo?'),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(8),
                child: Image.memory(
                  bytes,
                  height: 180,
                  width: 280,
                  fit: BoxFit.cover,
                ),
              ),
              const SizedBox(height: 12),
              const Text(
                'This replaces your current OurNear profile photo after upload succeeds.',
              ),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            child: const Text('Upload'),
          ),
        ],
      ),
    );
    if (!context.mounted || confirmed != true) {
      return;
    }

    await _upload(
      context,
      repository: repository,
      bytes: bytes,
      filename: filename,
      onUploaded: onUploaded,
    );
  }

  static Future<void> _upload(
    BuildContext context, {
    required ProfileRepository repository,
    required Uint8List bytes,
    required String filename,
    required VoidCallback onUploaded,
  }) async {
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const PopScope(
        canPop: false,
        child: Center(child: CircularProgressIndicator()),
      ),
    );
    try {
      await repository.uploadProfilePhoto(bytes: bytes, filename: filename);
      if (!context.mounted) {
        return;
      }
      Navigator.of(context, rootNavigator: true).pop();
      onUploaded();
      _showMessage(context, 'Profile photo saved on OurNear.');
    } on ProfileRepositoryException catch (error) {
      if (!context.mounted) {
        return;
      }
      Navigator.of(context, rootNavigator: true).pop();
      _showMessage(context, error.message);
    } catch (_) {
      if (!context.mounted) {
        return;
      }
      Navigator.of(context, rootNavigator: true).pop();
      _showMessage(
        context,
        'Upload failed. Check your connection and try again.',
      );
    }
  }

  static void _showMessage(BuildContext context, String message) {
    ScaffoldMessenger.of(
      context,
    ).showSnackBar(SnackBar(content: Text(message)));
  }
}
