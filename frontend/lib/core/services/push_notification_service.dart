import 'package:flutter/foundation.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'dart:io';

/// Service for handling push notifications via Firebase Cloud Messaging
class PushNotificationService {
  static final FirebaseMessaging _firebaseMessaging = FirebaseMessaging.instance;
  static final FlutterLocalNotificationsPlugin _localNotifications =
      FlutterLocalNotificationsPlugin();

  static String? _fcmToken;
  static Function(Map<String, dynamic>)? _onMessageCallback;
  static Function(Map<String, dynamic>)? _onMessageOpenedCallback;

  /// Initialize Firebase Cloud Messaging
  static Future<void> initialize({
    Function(Map<String, dynamic>)? onMessage,
    Function(Map<String, dynamic>)? onMessageOpened,
  }) async {
    _onMessageCallback = onMessage;
    _onMessageOpenedCallback = onMessageOpened;

    // Request permission for iOS
    await _requestPermission();

    // Initialize local notifications
    await _initializeLocalNotifications();

    // Get FCM token
    _fcmToken = await _firebaseMessaging.getToken();
    debugPrint('FCM Token: $_fcmToken');

    // Listen for token refresh
    _firebaseMessaging.onTokenRefresh.listen((newToken) {
      debugPrint('FCM Token refreshed: $newToken');
      _fcmToken = newToken;
      // TODO: Send new token to backend
    });

    // Handle foreground messages
    FirebaseMessaging.onMessage.listen(_handleForegroundMessage);

    // Handle background/terminated messages when app is opened
    FirebaseMessaging.onMessageOpenedApp.listen(_handleMessageOpened);

    // Check if app was opened from a terminated state by clicking notification
    final initialMessage = await _firebaseMessaging.getInitialMessage();
    if (initialMessage != null) {
      _handleMessageOpened(initialMessage);
    }

    debugPrint('Push notification service initialized');
  }

  /// Request notification permission (iOS)
  static Future<void> _requestPermission() async {
    NotificationSettings settings = await _firebaseMessaging.requestPermission(
      alert: true,
      badge: true,
      sound: true,
      provisional: false,
    );

    if (settings.authorizationStatus == AuthorizationStatus.authorized) {
      debugPrint('User granted notification permission');
    } else if (settings.authorizationStatus == AuthorizationStatus.provisional) {
      debugPrint('User granted provisional notification permission');
    } else {
      debugPrint('User declined notification permission');
    }
  }

  /// Initialize local notifications for displaying when app is in foreground
  static Future<void> _initializeLocalNotifications() async {
    const androidSettings = AndroidInitializationSettings('@mipmap/ic_launcher');
    const iosSettings = DarwinInitializationSettings(
      requestAlertPermission: false,
      requestBadgePermission: false,
      requestSoundPermission: false,
    );

    const initSettings = InitializationSettings(
      android: androidSettings,
      iOS: iosSettings,
    );

    await _localNotifications.initialize(
      initSettings,
      onDidReceiveNotificationResponse: (NotificationResponse response) {
        if (response.payload != null) {
          // Handle notification tap
          final data = Map<String, dynamic>.from(
            Uri.splitQueryString(response.payload!),
          );
          _onMessageOpenedCallback?.call(data);
        }
      },
    );

    // Create notification channel for Android
    if (Platform.isAndroid) {
      const channel = AndroidNotificationChannel(
        'servicehub_notifications', // id
        'ServiceHub Notifications', // name
        description: 'Notifications from ServiceHub',
        importance: Importance.high,
      );

      await _localNotifications
          .resolvePlatformSpecificImplementation<
              AndroidFlutterLocalNotificationsPlugin>()
          ?.createNotificationChannel(channel);
    }
  }

  /// Handle foreground messages
  static Future<void> _handleForegroundMessage(RemoteMessage message) async {
    debugPrint('Received foreground message: ${message.messageId}');
    debugPrint('Title: ${message.notification?.title}');
    debugPrint('Body: ${message.notification?.body}');
    debugPrint('Data: ${message.data}');

    // Call callback
    _onMessageCallback?.call(message.data);

    // Show local notification
    if (message.notification != null) {
      await _showLocalNotification(
        message.notification!.title ?? 'ServiceHub',
        message.notification!.body ?? '',
        message.data,
      );
    }
  }

  /// Handle notification tap (background or terminated)
  static void _handleMessageOpened(RemoteMessage message) {
    debugPrint('Notification opened: ${message.messageId}');
    debugPrint('Data: ${message.data}');

    _onMessageOpenedCallback?.call(message.data);
  }

  /// Show local notification
  static Future<void> _showLocalNotification(
    String title,
    String body,
    Map<String, dynamic> data,
  ) async {
    const androidDetails = AndroidNotificationDetails(
      'servicehub_notifications',
      'ServiceHub Notifications',
      channelDescription: 'Notifications from ServiceHub',
      importance: Importance.high,
      priority: Priority.high,
      showWhen: true,
    );

    const iosDetails = DarwinNotificationDetails(
      presentAlert: true,
      presentBadge: true,
      presentSound: true,
    );

    const notificationDetails = NotificationDetails(
      android: androidDetails,
      iOS: iosDetails,
    );

    await _localNotifications.show(
      DateTime.now().millisecondsSinceEpoch ~/ 1000,
      title,
      body,
      notificationDetails,
      payload: Uri(queryParameters: data.map((k, v) => MapEntry(k, v.toString()))).query,
    );
  }

  /// Get FCM token
  static String? get fcmToken => _fcmToken;

  /// Subscribe to topic
  static Future<void> subscribeToTopic(String topic) async {
    await _firebaseMessaging.subscribeToTopic(topic);
    debugPrint('Subscribed to topic: $topic');
  }

  /// Unsubscribe from topic
  static Future<void> unsubscribeFromTopic(String topic) async {
    await _firebaseMessaging.unsubscribeFromTopic(topic);
    debugPrint('Unsubscribed from topic: $topic');
  }

  /// Delete FCM token (on logout)
  static Future<void> deleteToken() async {
    await _firebaseMessaging.deleteToken();
    _fcmToken = null;
    debugPrint('FCM token deleted');
  }

  /// Get notification permission status
  static Future<bool> hasPermission() async {
    final settings = await _firebaseMessaging.getNotificationSettings();
    return settings.authorizationStatus == AuthorizationStatus.authorized;
  }

  /// Request permission if not granted
  static Future<bool> requestPermissionIfNeeded() async {
    if (await hasPermission()) {
      return true;
    }

    final settings = await _firebaseMessaging.requestPermission();
    return settings.authorizationStatus == AuthorizationStatus.authorized;
  }

  /// Clear all notifications
  static Future<void> clearAllNotifications() async {
    await _localNotifications.cancelAll();
  }

  /// Handle notification actions based on data
  static void handleNotificationData(Map<String, dynamic> data) {
    final type = data['type'] as String?;

    switch (type) {
      case 'booking_confirmed':
        // Navigate to booking details
        final bookingId = data['booking_id'];
        debugPrint('Navigate to booking: $bookingId');
        break;

      case 'booking_cancelled':
        final bookingId = data['booking_id'];
        debugPrint('Booking cancelled: $bookingId');
        break;

      case 'new_message':
        final conversationId = data['conversation_id'];
        debugPrint('New message in conversation: $conversationId');
        break;

      case 'payment_success':
        final paymentId = data['payment_id'];
        debugPrint('Payment successful: $paymentId');
        break;

      case 'review_reminder':
        final bookingId = data['booking_id'];
        debugPrint('Review reminder for booking: $bookingId');
        break;

      default:
        debugPrint('Unknown notification type: $type');
    }
  }
}

/// Background message handler (must be top-level function)
@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  debugPrint('Handling background message: ${message.messageId}');
  debugPrint('Title: ${message.notification?.title}');
  debugPrint('Body: ${message.notification?.body}');
  debugPrint('Data: ${message.data}');
}
