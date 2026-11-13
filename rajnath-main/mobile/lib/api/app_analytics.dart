import 'dart:async';

import 'package:flutter/widgets.dart';

import 'api_client.dart';

/// Screen views for Admin › Analytics › Mobile app. Batched and sent to /analytics/events at most
/// every 30 seconds; failures are ignored (analytics must never disturb the app).
class AppAnalytics {
  AppAnalytics._();
  static final AppAnalytics instance = AppAnalytics._();

  ApiClient? api;
  final List<Map<String, dynamic>> _queue = [];
  Timer? _timer;

  void screen(String name) => _add({'type': 'screen', 'name': name});

  void tap(String name) => _add({'type': 'tap', 'name': name});

  void _add(Map<String, dynamic> e) {
    if (api == null || name(e).isEmpty) return;
    _queue.add(e);
    if (_queue.length >= 20) {
      flush();
    } else {
      _timer ??= Timer(const Duration(seconds: 30), flush);
    }
  }

  static String name(Map<String, dynamic> e) => '${e['name'] ?? ''}';

  Future<void> flush() async {
    _timer?.cancel();
    _timer = null;
    if (_queue.isEmpty || api == null) return;
    final batch = List<Map<String, dynamic>>.from(_queue.take(50));
    _queue.removeRange(0, batch.length);
    try {
      await api!.post('/analytics/events', {'events': batch});
    } catch (_) {/* dropped */}
  }
}

/// Records each pushed screen by its widget name (for example "NotificationsScreen").
class ScreenObserver extends NavigatorObserver {
  @override
  void didPush(Route<dynamic> route, Route<dynamic>? previousRoute) {
    super.didPush(route, previousRoute);
    final name = route.settings.name ?? _widgetName(route);
    if (name != null && name.isNotEmpty) AppAnalytics.instance.screen(name.replaceAll(RegExp(r'Screen$'), ''));
  }

  String? _widgetName(Route<dynamic> route) {
    final ctx = navigator?.context;
    if (route is MaterialPageRoute && ctx != null) {
      try {
        return route.builder(ctx).runtimeType.toString();
      } catch (_) {
        return null;
      }
    }
    return null;
  }
}
