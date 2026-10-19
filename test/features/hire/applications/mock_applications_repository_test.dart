import 'package:flutter_test/flutter_test.dart';
import 'package:ournear_mobile/features/hire/applications/data/applications_repository.dart';
import 'package:ournear_mobile/features/hire/applications/data/mock_applications_repository.dart';

void main() {
  test('mock repository returns stable demo ids and mockDemo source', () async {
    final repo = MockApplicationsRepository(artificialDelay: Duration.zero);
    final result = await repo.fetchApplications();

    expect(result.source, ApplicationsDataSource.mockDemo);
    expect(result.applications.length, 3);
    expect(result.applications.first.id, 'demo-app-001');
  });

  test('mock repository empty configuration', () async {
    final repo = MockApplicationsRepository(artificialDelay: Duration.zero, returnEmpty: true);
    final result = await repo.fetchApplications();
    expect(result.applications, isEmpty);
    expect(result.source, ApplicationsDataSource.mockDemo);
  });

  test('mock repository offline simulation', () async {
    final repo = MockApplicationsRepository(artificialDelay: Duration.zero, simulateOffline: true);
    expect(repo.fetchApplications(), throwsA(isA<ApplicationsRepositoryException>()));
  });
}
