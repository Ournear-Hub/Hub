import 'package:flutter_test/flutter_test.dart';
import 'package:ournear_mobile/core/app_config/hire_app_config.dart';

void main() {
  test(
    'candidate root tabs are Home, Jobs, Applications, Profile in order',
    () {
      final tabs = HireAppConfigs.candidate.rootDestinations;
      expect(tabs.length, 4);
      expect(tabs.map((t) => t.label).toList(), [
        'Home',
        'Jobs',
        'Applications',
        'Profile',
      ]);
      expect(HireAppConfigs.candidate.displayName, 'OurNear Hire');
    },
  );

  test(
    'employer root tabs are Home, My Jobs, Applicants, Profile in order',
    () {
      final tabs = HireAppConfigs.employer.rootDestinations;
      expect(tabs.length, 4);
      expect(tabs.map((t) => t.label).toList(), [
        'Home',
        'My Jobs',
        'Applicants',
        'Profile',
      ]);
      expect(HireAppConfigs.employer.displayName, 'ON Hire: Employer');
    },
  );
}
