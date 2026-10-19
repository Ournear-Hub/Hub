import 'package:flutter_test/flutter_test.dart';
import 'package:ournear_mobile/features/hire/applications/applications_controller.dart';
import 'package:ournear_mobile/features/hire/applications/data/applications_repository.dart';
import 'package:ournear_mobile/features/hire/applications/data/mock_applications_repository.dart';
void main() {
  test('refresh populates demo applications', () async {
    final controller = ApplicationsController(
      MockApplicationsRepository(artificialDelay: Duration.zero),
    );
    await controller.refresh();
    expect(controller.viewState, ApplicationsViewState.populated);
    expect(controller.applications.length, 3);
    expect(controller.dataSource, ApplicationsDataSource.mockDemo);
    controller.dispose();
  });

  test('refresh empty shows empty state', () async {
    final controller = ApplicationsController(
      MockApplicationsRepository(artificialDelay: Duration.zero, returnEmpty: true),
    );
    await controller.refresh();
    expect(controller.viewState, ApplicationsViewState.empty);
    controller.dispose();
  });

  test('refresh offline shows offline state when list empty', () async {
    final controller = ApplicationsController(
      MockApplicationsRepository(artificialDelay: Duration.zero, simulateOffline: true),
    );
    await controller.refresh();
    expect(controller.viewState, ApplicationsViewState.offline);
    controller.dispose();
  });

  test('findById returns matching application', () async {
    final controller = ApplicationsController(
      MockApplicationsRepository(artificialDelay: Duration.zero),
    );
    await controller.refresh();
    expect(controller.findById('demo-app-002')?.roleTitle, 'Field Operations Coordinator');
    controller.dispose();
  });
}
