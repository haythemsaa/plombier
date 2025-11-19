/// App route names
class AppRoutes {
  // Auth
  static const String splash = '/';
  static const String onboarding = '/onboarding';
  static const String login = '/login';
  static const String register = '/register';
  static const String forgotPassword = '/forgot-password';

  // Main
  static const String home = '/home';
  static const String search = '/search';

  // Bookings
  static const String bookings = '/bookings';
  static const String bookingDetails = '/bookings/:id';
  static const String createBooking = '/bookings/create';

  // Services
  static const String services = '/services';
  static const String serviceDetails = '/services/:id';

  // Providers
  static const String providers = '/providers';
  static const String providerDetails = '/providers/:id';

  // Profile
  static const String profile = '/profile';
  static const String editProfile = '/profile/edit';
  static const String changePassword = '/profile/change-password';
  static const String addresses = '/profile/addresses';
  static const String addAddress = '/profile/addresses/add';

  // Packages
  static const String packages = '/packages';
  static const String subscriptions = '/subscriptions';

  // Loyalty
  static const String loyalty = '/loyalty';

  // Recurring Bookings
  static const String recurringBookings = '/recurring-bookings';
  static const String createRecurringBooking = '/recurring-bookings/create';

  // Chat
  static const String conversations = '/conversations';
  static const String chat = '/chat/:id';

  // Notifications
  static const String notifications = '/notifications';

  // Analytics
  static const String analytics = '/analytics';

  // Tracking
  static const String liveTracking = '/tracking/:bookingId';
  static const String trackingControl = '/tracking/control/:bookingId';

  // Wallet
  static const String wallet = '/wallet';
  static const String walletTransactions = '/wallet/transactions';

  // Referrals
  static const String referrals = '/referrals';

  // Settings
  static const String settings = '/settings';
  static const String language = '/settings/language';
  static const String theme = '/settings/theme';
  static const String about = '/settings/about';
  static const String privacy = '/settings/privacy';
  static const String terms = '/settings/terms';
  static const String help = '/settings/help';

  // Provider specific
  static const String providerDashboard = '/provider/dashboard';
  static const String providerEarnings = '/provider/earnings';
  static const String providerReviews = '/provider/reviews';
  static const String providerAvailability = '/provider/availability';
}
