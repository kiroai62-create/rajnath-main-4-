import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:uuid/uuid.dart';

import '../util/version.dart';

/// Base URL of the shared backend. Override at build time:
///   flutter build apk --dart-define=API_BASE=https://staging.example.com/api/v1
const apiBase = String.fromEnvironment('API_BASE', defaultValue: 'https://astrorajnathjha.com/api/v1');

/// Website origin derived from [apiBase] (strips a trailing `/api/v1`).
String siteOriginFor(String base) => base.replaceFirst(RegExp(r'/api/v1/?$'), '').replaceFirst(RegExp(r'/$'), '');
final siteOrigin = siteOriginFor(apiBase);

class ApiException implements Exception {
  ApiException(this.message, {this.code, this.status, this.fieldErrors = const {}});
  final String message;
  final String? code;
  final int? status;
  final Map<String, String> fieldErrors;
  /// The phone itself has no internet (checked, not guessed).
  bool get isOffline => code == 'offline';

  /// Any network-level problem: no internet, server unreachable, or a slow answer.
  bool get isNetwork => code == 'offline' || code == 'server_unreachable' || code == 'timeout';
  @override
  String toString() => message;
}

/// Thin HTTP client. The Bearer token lives only in Android Keystore-backed secure storage.
class ApiClient {
  /// Passing [dio] (tests) turns off the retry pause and the live internet probe.
  ApiClient({Dio? dio, FlutterSecureStorage? storage})
      : _storage = storage ?? const FlutterSecureStorage(),
        retryDelay = dio == null ? const Duration(milliseconds: 800) : Duration.zero,
        hasInternet = dio == null ? _probeInternet : _assumeOffline,
        dio = dio ?? Dio(BaseOptions(baseUrl: apiBase, connectTimeout: const Duration(seconds: 20), receiveTimeout: const Duration(seconds: 45), headers: {'Accept': 'application/json'})) {
    this.dio.interceptors.add(InterceptorsWrapper(onRequest: (o, h) async {
      final t = await token();
      if (t != null) o.headers['Authorization'] = 'Bearer $t';
      if (locale != null) o.headers['Accept-Language'] = locale;
      // Admin › Analytics › Mobile app: a random id made on this phone (not linked to the person), the app version and platform.
      o.headers['X-App-Install'] = await installId();
      o.headers['X-App-Version'] = appVersion;
      o.headers['X-App-Platform'] = Platform.isIOS ? 'ios' : 'android';
      h.next(o);
    }));
  }

  final Dio dio;
  final FlutterSecureStorage _storage;
  static const _tokenKey = 'arj_token';
  static const _localeKey = 'arj_locale';
  static const _installKey = 'arj_install';
  String? _installId;

  /// Random id for this installation, created once and kept in secure storage.
  Future<String> installId() async {
    if (_installId != null) return _installId!;
    try {
      _installId = await _storage.read(key: _installKey);
      if (_installId == null) {
        _installId = const Uuid().v4();
        await _storage.write(key: _installKey, value: _installId);
      }
    } catch (_) {
      _installId ??= const Uuid().v4();
    }
    return _installId!;
  }
  void Function()? onUnauthorized;

  /// App language (e.g. `hi`), sent as `Accept-Language`. Null lets the server decide.
  String? locale;

  Future<String?> token() => _storage.read(key: _tokenKey);
  Future<void> saveToken(String t) => _storage.write(key: _tokenKey, value: t);
  Future<void> clearToken() => _storage.delete(key: _tokenKey);

  /// The saved app language, or null when none was chosen (or storage is unavailable).
  Future<String?> savedLocale() async {
    try {
      return await _storage.read(key: _localeKey);
    } catch (_) {
      return null;
    }
  }

  Future<void> saveLocale(String l) async {
    try {
      await _storage.write(key: _localeKey, value: l);
    } catch (_) {/* the choice still applies for this session */}
  }

  /// GETs are safe to repeat, so a dropped connection or slow answer is retried once before showing an error.
  Future<Map<String, dynamic>> get(String path, {Map<String, dynamic>? query}) => _wrap(() => dio.get(path, queryParameters: query), retry: true);

  /// [timeout] lets slow endpoints (the AI assistant) wait longer than the default 45 s.
  Future<Map<String, dynamic>> post(String path, Map<String, dynamic> body, {Duration? timeout}) =>
      _wrap(() => dio.post(path, data: body, options: timeout == null ? null : Options(receiveTimeout: timeout)));
  Future<Map<String, dynamic>> put(String path, Map<String, dynamic> body) => _wrap(() => dio.put(path, data: body));
  Future<Map<String, dynamic>> patch(String path, Map<String, dynamic> body) => _wrap(() => dio.patch(path, data: body));
  Future<Map<String, dynamic>> delete(String path, [Map<String, dynamic>? body]) => _wrap(() => dio.delete(path, data: body));

  /// Multipart upload (e.g. palm photos). Same error mapping as the JSON calls.
  Future<Map<String, dynamic>> postMultipart(String path, FormData form) =>
      _wrap(() => dio.post(path, data: form, options: Options(sendTimeout: const Duration(minutes: 2), receiveTimeout: const Duration(minutes: 1))));

  Future<Map<String, dynamic>> _wrap(Future<Response<dynamic>> Function() call, {bool retry = false}) async {
    try {
      final r = await call();
      return r.data is Map<String, dynamic> ? r.data as Map<String, dynamic> : <String, dynamic>{};
    } on DioException catch (e) {
      if (retry && _isTransport(e)) {
        if (retryDelay > Duration.zero) await Future<void>.delayed(retryDelay);
        try {
          final r = await call();
          return r.data is Map<String, dynamic> ? r.data as Map<String, dynamic> : <String, dynamic>{};
        } on DioException catch (e2) {
          throw await classify(e2);
        }
      }
      throw await classify(e);
    }
  }

  static bool _isTransport(DioException e) =>
      e.type == DioExceptionType.connectionError ||
      e.type == DioExceptionType.connectionTimeout ||
      e.type == DioExceptionType.receiveTimeout ||
      e.type == DioExceptionType.sendTimeout ||
      (e.type == DioExceptionType.unknown && (e.error is SocketException || e.error is HttpException));

  /// Pause before the single automatic retry of a failed GET.
  final Duration retryDelay;

  /// Checks whether the phone can reach the internet at all, so "You are offline" is only
  /// shown when that is actually true. Overridable in tests.
  Future<bool> Function() hasInternet;

  static Future<bool> _assumeOffline() async => false;

  static Future<bool> _probeInternet() async {
    for (final host in const ['google.com', 'cloudflare.com']) {
      try {
        final r = await InternetAddress.lookup(host).timeout(const Duration(seconds: 3));
        if (r.isNotEmpty && r.first.rawAddress.isNotEmpty) return true;
      } catch (_) {/* try the next host */}
    }
    return false;
  }

  /// Like [toApiException], but tells "no internet on the phone" apart from "our server is
  /// unreachable" by probing the internet when the request failed at the network level.
  Future<ApiException> classify(DioException e) async {
    if (e.type == DioExceptionType.badCertificate || e.error is HandshakeException || e.error is TlsException) {
      return ApiException('A secure connection to our server could not be made. Please check the date and time on your phone and try again.', code: 'tls');
    }
    if (e.type == DioExceptionType.receiveTimeout || e.type == DioExceptionType.sendTimeout) {
      return ApiException('Our server is taking longer than usual. Please try again.', code: 'timeout');
    }
    if (_isTransport(e)) {
      final online = await hasInternet().catchError((_) => false);
      return online
          ? ApiException('We could not reach our server just now. Please try again in a moment.', code: 'server_unreachable')
          : ApiException('No internet connection. Please check and try again.', code: 'offline');
    }
    return toApiException(e);
  }

  ApiException toApiException(DioException e) {
    if (e.type == DioExceptionType.receiveTimeout || e.type == DioExceptionType.sendTimeout) {
      return ApiException('Our server is taking longer than usual. Please try again.', code: 'timeout');
    }
    if (e.type == DioExceptionType.connectionError || e.type == DioExceptionType.connectionTimeout) {
      return ApiException('No internet connection. Please check and try again.', code: 'offline');
    }
    final status = e.response?.statusCode;
    final data = e.response?.data;
    if (status == 401) {
      onUnauthorized?.call();
      return ApiException('Please sign in again.', code: 'unauthorized', status: 401);
    }
    if (status == 429) {
      final err = data is Map && data['error'] is Map ? data['error'] as Map : null;
      return ApiException(err?['message'] != null ? '${err!['message']}' : 'Too many attempts. Please wait a minute and try again.',
          code: err?['code'] != null ? '${err!['code']}' : 'rate_limited', status: 429);
    }
    if (status != null && status >= 500 && !(data is Map && data['error'] is Map)) {
      return ApiException('Our server had a problem. Please try again in a minute.', code: 'server_error', status: status);
    }
    if (data is Map && data['errors'] is Map) {
      final fields = <String, String>{};
      (data['errors'] as Map).forEach((k, v) => fields['$k'] = v is List && v.isNotEmpty ? '${v.first}' : '$v');
      return ApiException(fields.values.first, code: 'validation', status: status, fieldErrors: fields);
    }
    if (data is Map && data['error'] is Map) {
      return ApiException('${data['error']['message']}', code: '${data['error']['code']}', status: status);
    }
    return ApiException('Something went wrong. Please try again.', status: status);
  }
}
