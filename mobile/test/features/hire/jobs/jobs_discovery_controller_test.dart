import 'package:flutter_test/flutter_test.dart';
import 'package:ournear_mobile/features/hire/jobs/jobs_discovery_controller.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  test('searchDebounced applies keyword after delay', () async {
    final controller = JobsDiscoveryController(
      debounce: const Duration(milliseconds: 50),
    );
    addTearDown(controller.dispose);

    controller.searchDebounced(' nurse ');
    expect(controller.keyword, isEmpty);
    await Future<void>.delayed(const Duration(milliseconds: 60));
    expect(controller.keyword, 'nurse');
  });

  test('clear cancels pending debounced search', () async {
    final controller = JobsDiscoveryController(
      debounce: const Duration(milliseconds: 80),
    );
    addTearDown(controller.dispose);

    controller.searchDebounced('temp');
    controller.clear();
    await Future<void>.delayed(const Duration(milliseconds: 100));
    expect(controller.keyword, isEmpty);
  });
}
