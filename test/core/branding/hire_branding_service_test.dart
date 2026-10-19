import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:ournear_mobile/core/api/hire_api_service.dart';
import 'package:ournear_mobile/core/branding/hire_branding_service.dart';
import '../../helpers/fake_http_client.dart';

void main() {
  test('load returns fallback when no branding API is wired', () async {
    final service = HireBrandingService();
    final data = await service.load();
    expect(data.source, HireBrandingSource.fallback);
    expect(data.appName, 'OurNear Hire');
    expect(data.logoUrl, isNull);
  });

  test('load returns live branding from public endpoint', () async {
    final service = HireBrandingService(
      apiService: HireApiService(
        apiBaseOverride: 'http://localhost',
        client: FakeHttpClient((request) async {
          expect(request.url.path, '/api/v1/site/branding');
          return http.Response(
            jsonEncode({
              'data': {
                'app_name': 'Staging Hire',
                'logo_url': 'https://example.test/logo.png',
              },
            }),
            200,
          );
        }),
      ),
    );
    final data = await service.load();
    expect(data.source, HireBrandingSource.liveApi);
    expect(data.appName, 'Staging Hire');
    expect(data.logoUrl, 'https://example.test/logo.png');
  });
}
