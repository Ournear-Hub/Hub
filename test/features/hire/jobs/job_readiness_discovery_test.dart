import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:ournear_mobile/core/api/models/public_job.dart';
import 'package:ournear_mobile/core/theme/ournear_theme.dart';
import 'package:ournear_mobile/features/hire/applications/application_readiness_screen.dart';

const _job = PublicJobDetail(
  title: 'Ward Nurse',
  slug: 'ward-nurse',
  companyName: 'City Clinic',
  acceptsApplications: true,
);

void main() {
  testWidgets('readiness states live submit is unavailable', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: OurNearTheme.hireLight(),
        home: const ApplicationReadinessScreen(job: _job),
      ),
    );
    await tester.pumpAndSettle();

    expect(
      find.byKey(const Key('readiness-live-submit-note')),
      findsOneWidget,
    );
    expect(find.textContaining('Submit sends your profile application'), findsOneWidget);
  });
}
