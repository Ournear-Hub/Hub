import 'package:flutter_test/flutter_test.dart';
import 'package:ournear_mobile/core/api/models/public_job.dart';

void main() {
  test('PublicJob.fromJson parses API card payload', () {
    final job = PublicJob.fromJson({
      'title': 'Site Engineer',
      'slug': 'site-engineer',
      'company_name': 'North Build',
      'location': 'Dubai',
      'employment_type': 'Full-time',
      'experience': '3-5 years',
      'salary': 'Competitive',
      'posted_label': '2 days ago',
    });

    expect(job.title, 'Site Engineer');
    expect(job.slug, 'site-engineer');
    expect(job.subtitle, contains('Dubai'));
  });

  test('PaginatedPublicJobs.fromJson parses list envelope', () {
    final page = PaginatedPublicJobs.fromJson({
      'data': [
        {'title': 'A', 'slug': 'a'},
        {'title': 'B', 'slug': 'b'},
      ],
      'meta': {'current_page': 1, 'last_page': 2, 'total': 3},
      'links': {'next': 'http://localhost/api/v1/jobs?page=2'},
    });

    expect(page.jobs.length, 2);
    expect(page.total, 3);
    expect(page.hasMore, isTrue);
  });
}
