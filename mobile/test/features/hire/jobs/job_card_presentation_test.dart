import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:ournear_mobile/core/api/models/public_job.dart';
import 'package:ournear_mobile/core/theme/ournear_theme.dart';
import 'package:ournear_mobile/features/hire/jobs/job_card.dart';

void main() {
  const job = PublicJob(
    title: 'Electrician',
    slug: 'electrician',
    companyName: 'Build Co',
    location: 'Pune',
    employmentType: 'Full time',
    experience: '2 years',
    salary: '₹25,000 / month',
    postedLabel: 'Posted 2 days ago',
    featured: true,
    urgent: true,
  );

  testWidgets('job card shows hierarchy badges and company initial', (
    tester,
  ) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: OurNearTheme.hireLight(),
        darkTheme: OurNearTheme.hireDark(),
        home: Scaffold(
          body: JobCard(job: job, onTap: () {}),
        ),
      ),
    );

    expect(find.byKey(const Key('job-card-electrician')), findsOneWidget);
    expect(find.text('Electrician'), findsOneWidget);
    expect(find.text('Build Co'), findsOneWidget);
    expect(find.text('Featured'), findsOneWidget);
    expect(find.text('Urgent'), findsOneWidget);
    expect(find.text('B'), findsOneWidget);
    expect(find.text('₹25,000 / month'), findsOneWidget);
  });

  testWidgets('job card respects dark theme colors', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: OurNearTheme.hireLight(),
        darkTheme: OurNearTheme.hireDark(),
        themeMode: ThemeMode.dark,
        home: Scaffold(
          body: JobCard(job: job, onTap: () {}),
        ),
      ),
    );

    expect(Theme.of(tester.element(find.byType(JobCard))).brightness,
        Brightness.dark);
    expect(find.text('Electrician'), findsOneWidget);
  });
}
