import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:ournear_mobile/app/ournear_app.dart';

Future<void> _pumpApp(WidgetTester tester) async {
  await tester.pumpWidget(const OurNearApp());
  await tester.pump();
  for (var i = 0; i < 30; i++) {
    if (find.text('One platform').evaluate().isNotEmpty) {
      break;
    }
    await tester.pump(const Duration(milliseconds: 100));
  }
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  setUp(() {
    FlutterSecureStorage.setMockInitialValues({});
  });

  testWidgets('OurNear app launches home', (tester) async {
    await _pumpApp(tester);
    expect(find.text('One platform'), findsOneWidget);
  });
}
