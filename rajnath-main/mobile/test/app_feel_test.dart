import 'package:app/screens/assistant_screen.dart';
import 'package:app/screens/purchase_screens.dart';
import 'package:app/screens/services_screens.dart';
import 'package:app/state/app_state.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'widget_test.dart' show FakeStorage, fakeApi;

void main() {
  testWidgets(
    'service detail shows vidhi, benefits, booking steps and FAQs from the API',
    (tester) async {
      tester.view.physicalSize = const Size(1080, 4000);
      tester.view.devicePixelRatio = 2;
      addTearDown(tester.view.reset);
      final api = fakeApi({
        'GET /catalogue/offerings/griha-pravesh-puja': {
          'data': {
            'slug': 'griha-pravesh-puja',
            'title': 'Griha Pravesh Puja',
            'summary': 'House-warming puja.',
            'family_name': 'Puja',
            'body': 'First paragraph.\n\nSecond paragraph.',
            'includes': ['Muhurat for your family'],
            'process': ['Ganesh puja', 'Vastu shanti'],
            'benefits': ['Peace at home'],
            'faqs': [
              {'q': 'Which months are good?', 'a': 'Magha and Phalguna.'},
            ],
            'steps': [
              {'title': 'Pay securely online', 'text': 'UPI or card.'},
            ],
            'variants': [
              {
                'ref': 'v1',
                'name': 'At your home — Patna',
                'mode': 'puja',
                'price_minor': 1100000,
              },
            ],
          },
        },
      }, FakeStorage());
      await tester.pumpWidget(
        MaterialApp(
          home: ServiceDetailScreen(
            state: AppState(api),
            slug: 'griha-pravesh-puja',
          ),
        ),
      );
      await tester.pumpAndSettle();
      for (final t in [
        'How it is done',
        'Ganesh puja',
        'Benefits',
        'Peace at home',
        'How booking works',
        'Pay securely online',
        'Questions',
        'Which months are good?',
        'Second paragraph.',
        'Choose an option',
      ]) {
        expect(find.text(t), findsOneWidget, reason: t);
      }
      expect(find.text('Buy'), findsOneWidget);
      await tester.tap(find.text('Which months are good?'));
      await tester.pumpAndSettle();
      expect(find.text('Magha and Phalguna.'), findsOneWidget);
    },
  );

  testWidgets(
    'assistant shows suggestions and turns reply actions into buttons',
    (tester) async {
      final api = fakeApi({
        'POST /assistant/chat': {
          'conversation': 'c1',
          'reply': 'Your puja is ready to pay.',
          'handoff': false,
          'actions': [
            {
              'type': 'pay',
              'label': 'Pay securely ₹11,000',
              'order_ref': 'o1',
              'url': 'https://test/checkout/o1',
            },
            {
              'type': 'service',
              'label': 'Open Griha Pravesh Puja',
              'slug': 'griha-pravesh-puja',
              'url': 'https://test/services/griha-pravesh-puja',
            },
          ],
        },
      }, FakeStorage());
      await tester.pumpWidget(
        MaterialApp(home: AssistantScreen(state: AppState(api))),
      );
      expect(find.text('Online palm reading'), findsOneWidget);
      await tester.enterText(find.byType(TextField), 'Griha pravesh book karo');
      await tester.tap(find.byTooltip('Send'));
      await tester.pumpAndSettle();
      expect(find.text('Your puja is ready to pay.'), findsOneWidget);
      expect(find.text('Pay securely ₹11,000'), findsOneWidget);
      expect(find.text('Open Griha Pravesh Puja'), findsOneWidget);
      expect(
        find.text('Online palm reading'),
        findsNothing,
        reason: 'suggestions hide after the first message',
      );
    },
  );

  testWidgets('site visit asks for the address before the pay button sends the order', (tester) async {
    tester.view.physicalSize = const Size(1080, 3600);
    tester.view.devicePixelRatio = 2;
    addTearDown(tester.view.reset);
    final api = fakeApi({
      'GET /wallet': {'data': {'balance_minor': 0}},
      'POST /checkout/service': {'order': {'ref': 'o1', 'status': 'paid', 'label': 'Vastu visit'}, 'razorpay': null},
    }, FakeStorage());
    await tester.pumpWidget(MaterialApp(home: ServiceBuyScreen(state: AppState(api), title: 'Vastu Consultation',
        variant: const {'ref': 'v1', 'name': 'On-site visit — outside Bihar', 'mode': 'onsite', 'price_minor': 2500000, 'details': 'visit'})));
    await tester.pumpAndSettle();
    expect(find.text('Details and payment'), findsOneWidget);
    expect(find.text('Visit details'), findsOneWidget);
    await tester.tap(find.text('Pay securely'));
    await tester.pumpAndSettle();
    expect(find.text('Enter the property address.'), findsOneWidget);
    expect(find.text('Please complete the details above.'), findsOneWidget);
    await tester.enterText(find.widgetWithText(TextField, 'Mobile number'), '9123456789');
    await tester.enterText(find.widgetWithText(TextField, 'City / town'), 'Noida');
    await tester.enterText(find.widgetWithText(TextField, 'Property address'), 'Sector 5');
    await tester.tap(find.text('Pay securely'));
    await tester.pumpAndSettle();
    expect(find.text('Enter the property address.'), findsNothing);
    expect(find.textContaining('is confirmed'), findsOneWidget);
  });
}
