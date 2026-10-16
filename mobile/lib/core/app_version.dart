/// Runtime app version label. Keep aligned with `pubspec.yaml` `version:` field.
abstract final class AppVersion {
  static const name = '1.0.0';
  static const buildNumber = '1';

  static String get label => '$name+$buildNumber';
}
