import 'package:flutter/foundation.dart';
import 'package:sentry_flutter/sentry_flutter.dart';

/// Service for error reporting and monitoring using Sentry
class ErrorReportingService {
  static const String _dsn = String.fromEnvironment(
    'SENTRY_DSN',
    defaultValue: '',
  );

  static const String _environment = String.fromEnvironment(
    'ENVIRONMENT',
    defaultValue: 'development',
  );

  static const String _release = String.fromEnvironment(
    'RELEASE_VERSION',
    defaultValue: '1.3.0',
  );

  /// Initialize Sentry error reporting
  static Future<void> initialize() async {
    // Only initialize Sentry if DSN is provided and not in debug mode
    if (_dsn.isEmpty || kDebugMode) {
      if (kDebugMode) {
        debugPrint('Sentry: Skipping initialization in debug mode');
      }
      return;
    }

    await SentryFlutter.init(
      (options) {
        options.dsn = _dsn;
        options.environment = _environment;
        options.release = _release;

        // Performance monitoring
        options.tracesSampleRate = 0.2; // 20% of transactions

        // Breadcrumbs
        options.maxBreadcrumbs = 100;

        // Attach screenshots on errors (useful for debugging UI issues)
        options.attachScreenshot = true;

        // Attach view hierarchy
        options.attachViewHierarchy = true;

        // Before send callback - filter sensitive data
        options.beforeSend = (event, hint) {
          // Don't send events in development
          if (_environment == 'development') {
            return null;
          }

          // Filter out sensitive information from breadcrumbs
          if (event.breadcrumbs != null) {
            event.breadcrumbs = event.breadcrumbs!.map((breadcrumb) {
              // Remove password fields from data
              if (breadcrumb.data != null) {
                final sanitizedData = Map<String, dynamic>.from(breadcrumb.data!);
                sanitizedData.removeWhere(
                  (key, value) => key.toLowerCase().contains('password') ||
                      key.toLowerCase().contains('token') ||
                      key.toLowerCase().contains('secret'),
                );
                return breadcrumb.copyWith(data: sanitizedData);
              }
              return breadcrumb;
            }).toList();
          }

          return event;
        };

        // Debug mode
        options.debug = kDebugMode;
      },
    );

    debugPrint('Sentry: Initialized successfully');
  }

  /// Report an error to Sentry
  static Future<void> reportError(
    dynamic error,
    StackTrace? stackTrace, {
    String? hint,
    Map<String, dynamic>? extra,
  }) async {
    if (kDebugMode) {
      debugPrint('Error: $error');
      debugPrint('StackTrace: $stackTrace');
      debugPrint('Hint: $hint');
      debugPrint('Extra: $extra');
      return;
    }

    await Sentry.captureException(
      error,
      stackTrace: stackTrace,
      hint: hint != null ? Hint.withMap({'hint': hint}) : null,
      withScope: (scope) {
        if (extra != null) {
          extra.forEach((key, value) {
            scope.setExtra(key, value);
          });
        }
      },
    );
  }

  /// Report a message to Sentry
  static Future<void> reportMessage(
    String message, {
    SentryLevel level = SentryLevel.info,
    Map<String, dynamic>? extra,
  }) async {
    if (kDebugMode) {
      debugPrint('Message [$level]: $message');
      debugPrint('Extra: $extra');
      return;
    }

    await Sentry.captureMessage(
      message,
      level: level,
      withScope: (scope) {
        if (extra != null) {
          extra.forEach((key, value) {
            scope.setExtra(key, value);
          });
        }
      },
    );
  }

  /// Add a breadcrumb for tracking user actions
  static void addBreadcrumb({
    required String message,
    String? category,
    SentryLevel level = SentryLevel.info,
    Map<String, dynamic>? data,
  }) {
    if (_dsn.isEmpty) return;

    Sentry.addBreadcrumb(
      Breadcrumb(
        message: message,
        category: category,
        level: level,
        data: data,
        timestamp: DateTime.now().toUtc(),
      ),
    );
  }

  /// Set user context for error tracking
  static Future<void> setUserContext({
    required String id,
    String? email,
    String? username,
    Map<String, dynamic>? extra,
  }) async {
    if (_dsn.isEmpty) return;

    await Sentry.configureScope((scope) {
      scope.setUser(
        SentryUser(
          id: id,
          email: email,
          username: username,
          data: extra,
        ),
      );
    });
  }

  /// Clear user context (e.g., on logout)
  static Future<void> clearUserContext() async {
    if (_dsn.isEmpty) return;

    await Sentry.configureScope((scope) {
      scope.setUser(null);
    });
  }

  /// Set custom context/tags
  static Future<void> setContext(String key, dynamic value) async {
    if (_dsn.isEmpty) return;

    await Sentry.configureScope((scope) {
      scope.setContexts(key, value);
    });
  }

  /// Set a tag for filtering in Sentry
  static Future<void> setTag(String key, String value) async {
    if (_dsn.isEmpty) return;

    await Sentry.configureScope((scope) {
      scope.setTag(key, value);
    });
  }

  /// Start a performance transaction
  static ISentrySpan? startTransaction({
    required String name,
    required String operation,
  }) {
    if (_dsn.isEmpty) return null;

    return Sentry.startTransaction(
      name,
      operation,
      bindToScope: true,
    );
  }

  /// Track API call performance
  static Future<T> trackApiCall<T>({
    required String endpoint,
    required String method,
    required Future<T> Function() call,
  }) async {
    final transaction = startTransaction(
      name: '$method $endpoint',
      operation: 'http.client',
    );

    try {
      addBreadcrumb(
        message: 'API call: $method $endpoint',
        category: 'http',
        level: SentryLevel.info,
      );

      final result = await call();
      transaction?.status = const SpanStatus.ok();
      return result;
    } catch (e, stackTrace) {
      transaction?.status = const SpanStatus.internalError();
      transaction?.throwable = e;
      await reportError(
        e,
        stackTrace,
        hint: 'API call failed: $method $endpoint',
        extra: {
          'endpoint': endpoint,
          'method': method,
        },
      );
      rethrow;
    } finally {
      await transaction?.finish();
    }
  }

  /// Track screen navigation
  static void trackNavigation(String screenName) {
    addBreadcrumb(
      message: 'Navigated to $screenName',
      category: 'navigation',
      level: SentryLevel.info,
      data: {'screen': screenName},
    );

    setTag('current_screen', screenName);
  }
}
