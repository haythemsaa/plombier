import 'package:flutter/material.dart';

/// App configuration constants
class AppConfig {
  // App Info
  static const String appName = 'ServiceHub Tunisie';
  static const String appVersion = '2.2.0';
  static const int buildNumber = 220;

  // API Configuration
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://api.servicehub.tn',
  );

  static const String socketUrl = String.fromEnvironment(
    'SOCKET_URL',
    defaultValue: 'wss://api.servicehub.tn',
  );

  // API Timeouts
  static const Duration connectionTimeout = Duration(seconds: 30);
  static const Duration receiveTimeout = Duration(seconds: 30);

  // Pagination
  static const int defaultPageSize = 20;
  static const int maxPageSize = 100;

  // Cache
  static const Duration cacheExpiration = Duration(minutes: 15);
  static const int maxCacheSize = 50 * 1024 * 1024; // 50 MB

  // Location
  static const double defaultLatitude = 36.8065; // Tunis
  static const double defaultLongitude = 10.1815;
  static const double searchRadius = 50.0; // km

  // GPS Tracking
  static const Duration trackingUpdateInterval = Duration(seconds: 10);
  static const double trackingAccuracyThreshold = 20.0; // meters

  // Images
  static const int maxImageSize = 5 * 1024 * 1024; // 5 MB
  static const List<String> allowedImageTypes = ['jpg', 'jpeg', 'png', 'webp'];

  // Booking
  static const Duration minBookingNotice = Duration(hours: 2);
  static const Duration maxBookingAdvance = Duration(days: 90);

  // Chat
  static const int maxMessageLength = 1000;
  static const Duration typingIndicatorTimeout = Duration(seconds: 3);

  // Firebase
  static const String firebaseAndroidAppId = String.fromEnvironment(
    'FIREBASE_ANDROID_APP_ID',
    defaultValue: '',
  );

  static const String firebaseIosAppId = String.fromEnvironment(
    'FIREBASE_IOS_APP_ID',
    defaultValue: '',
  );

  // Payment
  static const String paymentGateway = 'PayTech';
  static const List<String> supportedPaymentMethods = [
    'card',
    'd17',
    'wallet',
    'cash',
  ];

  // Feature Flags
  static const bool enableChat = true;
  static const bool enableGpsTracking = true;
  static const bool enablePackages = true;
  static const bool enableLoyalty = true;
  static const bool enableRecurringBookings = true;
  static const bool enableAnalytics = true;
  static const bool enableReferrals = true;

  // Social
  static const String facebookUrl = 'https://facebook.com/servicehub.tn';
  static const String instagramUrl = 'https://instagram.com/servicehub.tn';
  static const String linkedinUrl = 'https://linkedin.com/company/servicehub-tunisie';

  // Support
  static const String supportEmail = 'support@servicehub.tn';
  static const String supportPhone = '+216 XX XXX XXX';
  static const String privacyPolicyUrl = 'https://servicehub.tn/privacy';
  static const String termsUrl = 'https://servicehub.tn/terms';

  // Environment
  static bool get isProduction => const String.fromEnvironment(
    'ENVIRONMENT',
    defaultValue: 'development',
  ) == 'production';

  static bool get isDevelopment => !isProduction;

  // Debug
  static bool get enableLogging => isDevelopment;
  static bool get enablePerformanceMonitoring => isProduction;
}

/// API Endpoints
class ApiEndpoints {
  // Auth
  static const String login = '/api/auth/login';
  static const String register = '/api/auth/register';
  static const String logout = '/api/auth/logout';
  static const String user = '/api/auth/user';
  static const String updateProfile = '/api/auth/profile';
  static const String changePassword = '/api/auth/change-password';
  static const String verifyPhone = '/api/auth/verify-phone';

  // Services
  static const String services = '/api/services';
  static const String serviceCategories = '/api/service-categories';

  // Providers
  static const String providers = '/api/providers';
  static const String providerSearch = '/api/providers/search';

  // Bookings
  static const String bookings = '/api/bookings';
  static String bookingDetails(int id) => '/api/bookings/$id';
  static String cancelBooking(int id) => '/api/bookings/$id/cancel';
  static String confirmBooking(int id) => '/api/bookings/$id/confirm';
  static String startBooking(int id) => '/api/bookings/$id/start';
  static String completeBooking(int id) => '/api/bookings/$id/complete';

  // Reviews
  static const String reviews = '/api/reviews';
  static String reviewResponse(int id) => '/api/reviews/$id/response';

  // Addresses
  static const String addresses = '/api/addresses';

  // Payments
  static const String initiatePayment = '/api/payments/initiate';
  static String paymentStatus(int id) => '/api/payments/$id/status';

  // Notifications
  static const String notifications = '/api/notifications';
  static const String notificationsUnreadCount = '/api/notifications/unread-count';
  static String markNotificationRead(int id) => '/api/notifications/$id/read';
  static const String markAllNotificationsRead = '/api/notifications/read-all';

  // Chat
  static const String conversations = '/api/chat/conversations';
  static String conversationMessages(int id) => '/api/chat/conversations/$id/messages';
  static String sendMessage(int id) => '/api/chat/conversations/$id/messages';
  static String markConversationRead(int id) => '/api/chat/conversations/$id/mark-read';

  // Packages
  static const String packages = '/api/packages';
  static String subscribePackage(int id) => '/api/packages/$id/subscribe';
  static const String subscriptions = '/api/subscriptions';
  static String pauseSubscription(int id) => '/api/subscriptions/$id/pause';
  static String resumeSubscription(int id) => '/api/subscriptions/$id/resume';
  static String cancelSubscription(int id) => '/api/subscriptions/$id/cancel';

  // Loyalty
  static const String loyaltyStatus = '/api/loyalty/status';
  static const String loyaltyLeaderboard = '/api/loyalty/leaderboard';
  static const String loyaltyTiers = '/api/loyalty/tiers';

  // Recurring Bookings
  static const String recurringBookings = '/api/recurring-bookings';
  static String pauseRecurringBooking(int id) => '/api/recurring-bookings/$id/pause';
  static String resumeRecurringBooking(int id) => '/api/recurring-bookings/$id/resume';

  // Tracking
  static const String updateLocation = '/api/tracking/update-location';
  static String bookingLocation(int id) => '/api/tracking/bookings/$id/location';

  // Analytics
  static const String clientAnalytics = '/api/analytics/client/dashboard';
  static const String providerAnalytics = '/api/analytics/provider/dashboard';
  static const String adminAnalytics = '/api/analytics/admin/dashboard';

  // Referrals
  static const String myReferralCode = '/api/referrals/my-code';
  static const String referralStats = '/api/referrals/stats';

  // Wallet
  static const String walletBalance = '/api/wallet/balance';
  static const String walletTransactions = '/api/wallet/transactions';
}

/// Storage Keys
class StorageKeys {
  static const String authToken = 'auth_token';
  static const String userId = 'user_id';
  static const String userType = 'user_type';
  static const String userName = 'user_name';
  static const String userEmail = 'user_email';
  static const String userPhone = 'user_phone';
  static const String fcmToken = 'fcm_token';
  static const String language = 'language';
  static const String theme = 'theme';
  static const String onboardingComplete = 'onboarding_complete';
  static const String lastLoginDate = 'last_login_date';
}

/// App Colors
class AppColors {
  // Primary
  static const primary = Color(0xFF1976D2); // Blue 700
  static const primaryDark = Color(0xFF0D47A1); // Blue 900
  static const primaryLight = Color(0xFF42A5F5); // Blue 400

  // Secondary
  static const secondary = Color(0xFFFF9800); // Orange 500
  static const secondaryDark = Color(0xFFF57C00); // Orange 700
  static const secondaryLight = Color(0xFFFFB74D); // Orange 300

  // Status
  static const success = Color(0xFF4CAF50); // Green 500
  static const warning = Color(0xFFFF9800); // Orange 500
  static const error = Color(0xFFF44336); // Red 500
  static const info = Color(0xFF2196F3); // Blue 500

  // Loyalty Tiers
  static const bronze = Color(0xFF8D6E63); // Brown 400
  static const silver = Color(0xFF9E9E9E); // Grey 500
  static const gold = Color(0xFFFFA000); // Amber 700
  static const platinum = Color(0xFF424242); // Grey 800

  // Neutral
  static const black = Color(0xFF000000);
  static const white = Color(0xFFFFFFFF);
  static const grey = Color(0xFF9E9E9E);
  static const greyLight = Color(0xFFE0E0E0);
  static const greyDark = Color(0xFF616161);

  // Background
  static const background = Color(0xFFFAFAFA);
  static const surface = Color(0xFFFFFFFF);
  static const divider = Color(0xFFBDBDBD);
}

/// App Strings (Default - French)
class AppStrings {
  // Common
  static const appName = 'ServiceHub';
  static const loading = 'Chargement...';
  static const error = 'Erreur';
  static const success = 'Succès';
  static const cancel = 'Annuler';
  static const confirm = 'Confirmer';
  static const save = 'Enregistrer';
  static const delete = 'Supprimer';
  static const edit = 'Modifier';
  static const search = 'Rechercher';
  static const filter = 'Filtrer';
  static const apply = 'Appliquer';
  static const reset = 'Réinitialiser';

  // Auth
  static const login = 'Connexion';
  static const register = 'Inscription';
  static const logout = 'Déconnexion';
  static const email = 'Email';
  static const password = 'Mot de passe';
  static const forgotPassword = 'Mot de passe oublié ?';

  // Navigation
  static const home = 'Accueil';
  static const bookings = 'Réservations';
  static const profile = 'Profil';
  static const notifications = 'Notifications';
  static const chat = 'Messages';

  // Errors
  static const errorGeneric = 'Une erreur est survenue';
  static const errorNetwork = 'Erreur de connexion';
  static const errorAuth = 'Erreur d\'authentification';
  static const errorNotFound = 'Non trouvé';
}

/// Validation
class Validators {
  static String? email(String? value) {
    if (value == null || value.isEmpty) {
      return 'Email requis';
    }
    final emailRegex = RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$');
    if (!emailRegex.hasMatch(value)) {
      return 'Email invalide';
    }
    return null;
  }

  static String? phone(String? value) {
    if (value == null || value.isEmpty) {
      return 'Téléphone requis';
    }
    final phoneRegex = RegExp(r'^\+216\s?\d{2}\s?\d{3}\s?\d{3}$');
    if (!phoneRegex.hasMatch(value)) {
      return 'Format: +216 XX XXX XXX';
    }
    return null;
  }

  static String? password(String? value) {
    if (value == null || value.isEmpty) {
      return 'Mot de passe requis';
    }
    if (value.length < 8) {
      return 'Minimum 8 caractères';
    }
    return null;
  }

  static String? required(String? value, [String fieldName = 'Ce champ']) {
    if (value == null || value.isEmpty) {
      return '$fieldName est requis';
    }
    return null;
  }
}
