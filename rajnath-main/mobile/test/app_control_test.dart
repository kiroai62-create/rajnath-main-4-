import 'dart:io';

import 'package:app/api/api_client.dart';
import 'package:app/main.dart';
import 'package:app/screens/store_screens.dart';
import 'package:flutter/material.dart' show MaterialApp;
import 'package:app/state/app_state.dart';
import 'package:app/util/version.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_test/flutter_test.dart';

import 'widget_test.dart' show FakeStorage, fakeApi;

/// Admin › Mobile app settings arrive in GET /meta → app.
void main() {
  test('appVersion matches pubspec.yaml', () {
    final v = RegExp(r'^version:\s*([0-9.]+)', multiLine: true).firstMatch(File('pubspec.yaml').readAsStringSync())!.group(1);
    expect(appVersion, v);
    expect(compareVersions('1.9.0', '1.10.0'), lessThan(0));
    expect(compareVersions('1.11.0', '1.11'), 0);
  });

  test('defaults keep everything on; admin settings hide, reorder and switch off', () {
    final s = AppState(ApiClient());
    const defaults = ['greeting', 'quick', 'hero', 'signs'];
    expect(s.homeSections(defaults), defaults);
    expect(s.featureOn('store'), true);
    expect(s.maintenanceMessage, isNull);
    expect(s.updateRequired, false);

    s.meta = {
      'min_app_version': '1.0.0',
      'app': {
        'maintenance': {'on': false},
        'update': {'min_version': '1.0.0', 'latest_version': '9.0.0'},
        'home': {'order': ['signs', 'hero', 'greeting', 'quick'], 'hidden': ['quick']},
        'features': {'store': false, 'assistant': false},
      },
    };
    expect(s.homeSections(defaults), ['signs', 'hero', 'greeting']);
    expect(s.featureOn('store'), false);
    expect(s.assistantEnabled, false);
    expect(s.updateAvailable, true);
  });

  testWidgets('maintenance and forced update replace the app screens', (tester) async {
    final state = AppState(fakeApi({}, FakeStorage()));
    await state.bootstrap();
    state.meta = {'app': {'maintenance': {'on': true, 'message': 'Back at 6 pm'}}};
    await tester.pumpWidget(ArjApp(state: state));
    await tester.pump();
    expect(find.text('We will be back soon'), findsOneWidget);
    expect(find.text('Back at 6 pm'), findsOneWidget);

    state.meta = {'app': {'update': {'min_version': '99.0.0', 'message': 'Please update'}}};
    state.notifyListeners();
    await tester.pump();
    expect(find.text('Please update the app'), findsOneWidget);
    expect(find.text('Update now'), findsOneWidget);
    await tester.pumpWidget(const SizedBox());
    await tester.pump(const Duration(seconds: 5));
  });

  testWidgets('product page shows benefits, how to wear, cautions and FAQs', (tester) async {
    tester.view.physicalSize = const Size(1200, 6000);
    addTearDown(tester.view.reset);
    final api = fakeApi({
      'GET /store/products/ruby-manik': {
        'data': {
          'slug': 'ruby-manik', 'name': 'Ruby (Manik)', 'summary': 'For the Sun', 'price_minor': 1200000, 'in_stock': true,
          'guide': {
            'category_label': 'Gemstone', 'highlights': ['100% natural'],
            'sections': [
              {'title': 'Benefits', 'kind': 'list', 'items': ['Confidence']},
              {'title': 'How to wear', 'kind': 'table', 'items': [['Finger', 'Ring finger, right hand']]},
              {'title': 'Possible side effects and cautions', 'kind': 'list', 'items': ['Chart check first']},
            ],
            'specs': [['Category', 'Gemstone']], 'faqs': [{'q': 'Is the stone natural?', 'a': 'Yes.'}],
          },
        },
      },
    }, FakeStorage());
    await tester.pumpWidget(MaterialApp(home: ProductDetailScreen(state: AppState(api), slug: 'ruby-manik')));
    await tester.pumpAndSettle();
    for (final t in ['Ruby (Manik)', 'Benefits', 'Confidence', 'Ring finger, right hand', 'Possible side effects and cautions', 'Is the stone natural?', 'Buy now']) {
      expect(find.text(t), findsWidgets, reason: t);
    }
  });
}
