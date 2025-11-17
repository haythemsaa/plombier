import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter/foundation.dart';

/// Advanced analytics service for business intelligence
class AnalyticsService {
  final String baseUrl;
  final String? authToken;

  AnalyticsService({
    required this.baseUrl,
    this.authToken,
  });

  /// Get client analytics dashboard
  Future<ClientAnalytics?> getClientDashboard({String period = 'month'}) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api/analytics/client/dashboard?period=$period'),
        headers: {
          'Authorization': 'Bearer $authToken',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        return ClientAnalytics.fromJson(data['data']);
      }

      debugPrint('Failed to load client analytics: ${response.statusCode}');
      return null;
    } catch (e) {
      debugPrint('Error loading client analytics: $e');
      return null;
    }
  }

  /// Get provider analytics dashboard
  Future<ProviderAnalytics?> getProviderDashboard({String period = 'month'}) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api/analytics/provider/dashboard?period=$period'),
        headers: {
          'Authorization': 'Bearer $authToken',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        return ProviderAnalytics.fromJson(data['data']);
      }

      debugPrint('Failed to load provider analytics: ${response.statusCode}');
      return null;
    } catch (e) {
      debugPrint('Error loading provider analytics: $e');
      return null;
    }
  }

  /// Get admin dashboard (admin only)
  Future<AdminAnalytics?> getAdminDashboard({String period = 'month'}) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api/analytics/admin/dashboard?period=$period'),
        headers: {
          'Authorization': 'Bearer $authToken',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        return AdminAnalytics.fromJson(data['data']);
      }

      debugPrint('Failed to load admin analytics: ${response.statusCode}');
      return null;
    } catch (e) {
      debugPrint('Error loading admin analytics: $e');
      return null;
    }
  }
}

/// Client analytics model
class ClientAnalytics {
  final SpendingData spending;
  final SavingsData savings;
  final BookingStats bookings;
  final LoyaltyInfo loyalty;
  final FavoritesData favorites;
  final PatternData patterns;
  final String period;

  ClientAnalytics({
    required this.spending,
    required this.savings,
    required this.bookings,
    required this.loyalty,
    required this.favorites,
    required this.patterns,
    required this.period,
  });

  factory ClientAnalytics.fromJson(Map<String, dynamic> json) {
    return ClientAnalytics(
      spending: SpendingData.fromJson(json['spending']),
      savings: SavingsData.fromJson(json['savings']),
      bookings: BookingStats.fromJson(json['bookings']),
      loyalty: LoyaltyInfo.fromJson(json['loyalty']),
      favorites: FavoritesData.fromJson(json['favorites']),
      patterns: PatternData.fromJson(json['patterns']),
      period: json['period'],
    );
  }
}

class SpendingData {
  final double total;
  final double averagePerBooking;
  final List<MonthlySpending> byMonth;
  final List<CategorySpending> byCategory;

  SpendingData({
    required this.total,
    required this.averagePerBooking,
    required this.byMonth,
    required this.byCategory,
  });

  factory SpendingData.fromJson(Map<String, dynamic> json) {
    return SpendingData(
      total: (json['total'] ?? 0).toDouble(),
      averagePerBooking: (json['average_per_booking'] ?? 0).toDouble(),
      byMonth: (json['by_month'] as List?)
              ?.map((e) => MonthlySpending.fromJson(e))
              .toList() ??
          [],
      byCategory: (json['by_service_category'] as List?)
              ?.map((e) => CategorySpending.fromJson(e))
              .toList() ??
          [],
    );
  }
}

class MonthlySpending {
  final String month;
  final double total;

  MonthlySpending({required this.month, required this.total});

  factory MonthlySpending.fromJson(Map<String, dynamic> json) {
    return MonthlySpending(
      month: json['month'],
      total: (json['total'] ?? 0).toDouble(),
    );
  }
}

class CategorySpending {
  final String category;
  final double total;

  CategorySpending({required this.category, required this.total});

  factory CategorySpending.fromJson(Map<String, dynamic> json) {
    return CategorySpending(
      category: json['category'],
      total: (json['total'] ?? 0).toDouble(),
    );
  }
}

class SavingsData {
  final double fromPackages;
  final double fromLoyalty;
  final double totalSaved;

  SavingsData({
    required this.fromPackages,
    required this.fromLoyalty,
    required this.totalSaved,
  });

  factory SavingsData.fromJson(Map<String, dynamic> json) {
    return SavingsData(
      fromPackages: (json['from_packages'] ?? 0).toDouble(),
      fromLoyalty: (json['from_loyalty'] ?? 0).toDouble(),
      totalSaved: (json['total_saved'] ?? 0).toDouble(),
    );
  }
}

class BookingStats {
  final int total;
  final int completed;
  final int cancelled;
  final int recurringActive;

  BookingStats({
    required this.total,
    required this.completed,
    required this.cancelled,
    required this.recurringActive,
  });

  factory BookingStats.fromJson(Map<String, dynamic> json) {
    return BookingStats(
      total: json['total'] ?? 0,
      completed: json['completed'] ?? 0,
      cancelled: json['cancelled'] ?? 0,
      recurringActive: json['recurring_active'] ?? 0,
    );
  }
}

class LoyaltyInfo {
  final String currentTier;
  final int totalPoints;
  final int? pointsToNextTier;
  final List<String> tierBenefits;

  LoyaltyInfo({
    required this.currentTier,
    required this.totalPoints,
    this.pointsToNextTier,
    required this.tierBenefits,
  });

  factory LoyaltyInfo.fromJson(Map<String, dynamic> json) {
    return LoyaltyInfo(
      currentTier: json['current_tier'] ?? 'Bronze',
      totalPoints: json['total_points'] ?? 0,
      pointsToNextTier: json['points_to_next_tier'],
      tierBenefits: List<String>.from(json['tier_benefits'] ?? []),
    );
  }
}

class FavoritesData {
  final List<ServiceItem> services;
  final List<ProviderItem> providers;
  final List<String> timeSlots;

  FavoritesData({
    required this.services,
    required this.providers,
    required this.timeSlots,
  });

  factory FavoritesData.fromJson(Map<String, dynamic> json) {
    return FavoritesData(
      services: (json['services'] as List?)
              ?.map((e) => ServiceItem.fromJson(e))
              .toList() ??
          [],
      providers: (json['providers'] as List?)
              ?.map((e) => ProviderItem.fromJson(e))
              .toList() ??
          [],
      timeSlots: List<String>.from(json['time_slots'] ?? []),
    );
  }
}

class ServiceItem {
  final String serviceName;
  final int bookings;

  ServiceItem({required this.serviceName, required this.bookings});

  factory ServiceItem.fromJson(Map<String, dynamic> json) {
    return ServiceItem(
      serviceName: json['service_name'] ?? '',
      bookings: json['bookings'] ?? 0,
    );
  }
}

class ProviderItem {
  final String providerName;
  final int bookings;

  ProviderItem({required this.providerName, required this.bookings});

  factory ProviderItem.fromJson(Map<String, dynamic> json) {
    return ProviderItem(
      providerName: json['provider_name'] ?? '',
      bookings: json['bookings'] ?? 0,
    );
  }
}

class PatternData {
  final String bookingFrequency;
  final List<String> preferredDays;
  final String spendingTrend;

  PatternData({
    required this.bookingFrequency,
    required this.preferredDays,
    required this.spendingTrend,
  });

  factory PatternData.fromJson(Map<String, dynamic> json) {
    return PatternData(
      bookingFrequency: json['booking_frequency'] ?? 'occasional',
      preferredDays: List<String>.from(json['preferred_days'] ?? []),
      spendingTrend: json['spending_trend'] ?? 'stable',
    );
  }
}

/// Provider analytics model
class ProviderAnalytics {
  final EarningsData earnings;
  final ProviderBookingStats bookings;
  final PerformanceData performance;
  final List<PeakHour> peakHours;
  final List<TopService> topServices;
  final double clientRetention;
  final BenchmarkData benchmark;
  final String period;

  ProviderAnalytics({
    required this.earnings,
    required this.bookings,
    required this.performance,
    required this.peakHours,
    required this.topServices,
    required this.clientRetention,
    required this.benchmark,
    required this.period,
  });

  factory ProviderAnalytics.fromJson(Map<String, dynamic> json) {
    return ProviderAnalytics(
      earnings: EarningsData.fromJson(json['earnings']),
      bookings: ProviderBookingStats.fromJson(json['bookings']),
      performance: PerformanceData.fromJson(json['performance']),
      peakHours: (json['peak_hours'] as List?)
              ?.map((e) => PeakHour.fromJson(e))
              .toList() ??
          [],
      topServices: (json['top_services'] as List?)
              ?.map((e) => TopService.fromJson(e))
              .toList() ??
          [],
      clientRetention: (json['client_retention'] ?? 0).toDouble(),
      benchmark: BenchmarkData.fromJson(json['benchmark']),
      period: json['period'],
    );
  }
}

class EarningsData {
  final double total;
  final double platformFees;
  final double grossRevenue;
  final double averagePerBooking;
  final List<WeeklyEarning> byWeek;

  EarningsData({
    required this.total,
    required this.platformFees,
    required this.grossRevenue,
    required this.averagePerBooking,
    required this.byWeek,
  });

  factory EarningsData.fromJson(Map<String, dynamic> json) {
    return EarningsData(
      total: (json['total'] ?? 0).toDouble(),
      platformFees: (json['platform_fees'] ?? 0).toDouble(),
      grossRevenue: (json['gross_revenue'] ?? 0).toDouble(),
      averagePerBooking: (json['average_per_booking'] ?? 0).toDouble(),
      byWeek: (json['by_week'] as List?)
              ?.map((e) => WeeklyEarning.fromJson(e))
              .toList() ??
          [],
    );
  }
}

class WeeklyEarning {
  final String week;
  final double earnings;
  final int bookings;

  WeeklyEarning({
    required this.week,
    required this.earnings,
    required this.bookings,
  });

  factory WeeklyEarning.fromJson(Map<String, dynamic> json) {
    return WeeklyEarning(
      week: json['week'].toString(),
      earnings: (json['earnings'] ?? 0).toDouble(),
      bookings: json['bookings'] ?? 0,
    );
  }
}

class ProviderBookingStats {
  final int total;
  final int completed;
  final int cancelled;
  final double completionRate;
  final Map<String, int> byStatus;

  ProviderBookingStats({
    required this.total,
    required this.completed,
    required this.cancelled,
    required this.completionRate,
    required this.byStatus,
  });

  factory ProviderBookingStats.fromJson(Map<String, dynamic> json) {
    return ProviderBookingStats(
      total: json['total'] ?? 0,
      completed: json['completed'] ?? 0,
      cancelled: json['cancelled'] ?? 0,
      completionRate: (json['completion_rate'] ?? 0).toDouble(),
      byStatus: Map<String, int>.from(json['by_status'] ?? {}),
    );
  }
}

class PerformanceData {
  final double averageRating;
  final int totalReviews;
  final List<RatingPoint> ratingTrend;
  final int? responseTimeAvg;

  PerformanceData({
    required this.averageRating,
    required this.totalReviews,
    required this.ratingTrend,
    this.responseTimeAvg,
  });

  factory PerformanceData.fromJson(Map<String, dynamic> json) {
    return PerformanceData(
      averageRating: (json['average_rating'] ?? 0).toDouble(),
      totalReviews: json['total_reviews'] ?? 0,
      ratingTrend: (json['rating_trend'] as List?)
              ?.map((e) => RatingPoint.fromJson(e))
              .toList() ??
          [],
      responseTimeAvg: json['response_time_avg'],
    );
  }
}

class RatingPoint {
  final String date;
  final double avgRating;
  final int count;

  RatingPoint({
    required this.date,
    required this.avgRating,
    required this.count,
  });

  factory RatingPoint.fromJson(Map<String, dynamic> json) {
    return RatingPoint(
      date: json['date'],
      avgRating: (json['avg_rating'] ?? 0).toDouble(),
      count: json['count'] ?? 0,
    );
  }
}

class PeakHour {
  final String hour;
  final int bookings;

  PeakHour({required this.hour, required this.bookings});

  factory PeakHour.fromJson(Map<String, dynamic> json) {
    return PeakHour(
      hour: json['hour'],
      bookings: json['bookings'] ?? 0,
    );
  }
}

class TopService {
  final String serviceName;
  final int bookings;
  final double earnings;

  TopService({
    required this.serviceName,
    required this.bookings,
    required this.earnings,
  });

  factory TopService.fromJson(Map<String, dynamic> json) {
    return TopService(
      serviceName: json['service_name'] ?? '',
      bookings: json['bookings'] ?? 0,
      earnings: (json['earnings'] ?? 0).toDouble(),
    );
  }
}

class BenchmarkData {
  final int yourBookings;
  final double averageBookings;
  final double yourRating;
  final double averageRating;
  final int percentile;

  BenchmarkData({
    required this.yourBookings,
    required this.averageBookings,
    required this.yourRating,
    required this.averageRating,
    required this.percentile,
  });

  factory BenchmarkData.fromJson(Map<String, dynamic> json) {
    return BenchmarkData(
      yourBookings: json['your_bookings'] ?? 0,
      averageBookings: (json['average_bookings'] ?? 0).toDouble(),
      yourRating: (json['your_rating'] ?? 0).toDouble(),
      averageRating: (json['average_rating'] ?? 0).toDouble(),
      percentile: json['percentile'] ?? 0,
    );
  }
}

/// Admin analytics model
class AdminAnalytics {
  final OverviewData overview;
  final GrowthData growth;
  final ConversionFunnel conversion;
  final List<TopService> topServices;
  final List<GeographicDistribution> geographicDistribution;
  final RevenueBySource revenueBySource;
  final String period;

  AdminAnalytics({
    required this.overview,
    required this.growth,
    required this.conversion,
    required this.topServices,
    required this.geographicDistribution,
    required this.revenueBySource,
    required this.period,
  });

  factory AdminAnalytics.fromJson(Map<String, dynamic> json) {
    return AdminAnalytics(
      overview: OverviewData.fromJson(json['overview']),
      growth: GrowthData.fromJson(json['growth']),
      conversion: ConversionFunnel.fromJson(json['conversion']),
      topServices: (json['top_services'] as List?)
              ?.map((e) => TopService.fromJson(e))
              .toList() ??
          [],
      geographicDistribution: (json['geographic_distribution'] as List?)
              ?.map((e) => GeographicDistribution.fromJson(e))
              .toList() ??
          [],
      revenueBySource: RevenueBySource.fromJson(json['revenue_by_source']),
      period: json['period'],
    );
  }
}

class OverviewData {
  final double totalRevenue;
  final int totalBookings;
  final int activeClients;
  final int activeProviders;
  final double averageBookingValue;

  OverviewData({
    required this.totalRevenue,
    required this.totalBookings,
    required this.activeClients,
    required this.activeProviders,
    required this.averageBookingValue,
  });

  factory OverviewData.fromJson(Map<String, dynamic> json) {
    return OverviewData(
      totalRevenue: (json['total_revenue'] ?? 0).toDouble(),
      totalBookings: json['total_bookings'] ?? 0,
      activeClients: json['active_clients'] ?? 0,
      activeProviders: json['active_providers'] ?? 0,
      averageBookingValue: (json['average_booking_value'] ?? 0).toDouble(),
    );
  }
}

class GrowthData {
  final double revenueGrowth;
  final double bookingsGrowth;
  final double clientsGrowth;

  GrowthData({
    required this.revenueGrowth,
    required this.bookingsGrowth,
    required this.clientsGrowth,
  });

  factory GrowthData.fromJson(Map<String, dynamic> json) {
    return GrowthData(
      revenueGrowth: (json['revenue_growth'] ?? 0).toDouble(),
      bookingsGrowth: (json['bookings_growth'] ?? 0).toDouble(),
      clientsGrowth: (json['clients_growth'] ?? 0).toDouble(),
    );
  }
}

class ConversionFunnel {
  final int registrations;
  final int firstBooking;
  final int completedBooking;
  final int repeatCustomer;
  final Map<String, double> conversionRates;

  ConversionFunnel({
    required this.registrations,
    required this.firstBooking,
    required this.completedBooking,
    required this.repeatCustomer,
    required this.conversionRates,
  });

  factory ConversionFunnel.fromJson(Map<String, dynamic> json) {
    return ConversionFunnel(
      registrations: json['registrations'] ?? 0,
      firstBooking: json['first_booking'] ?? 0,
      completedBooking: json['completed_booking'] ?? 0,
      repeatCustomer: json['repeat_customer'] ?? 0,
      conversionRates: Map<String, double>.from(
        (json['conversion_rates'] ?? {}).map(
          (key, value) => MapEntry(key, (value ?? 0).toDouble()),
        ),
      ),
    );
  }
}

class GeographicDistribution {
  final String governorate;
  final int bookings;
  final double revenue;

  GeographicDistribution({
    required this.governorate,
    required this.bookings,
    required this.revenue,
  });

  factory GeographicDistribution.fromJson(Map<String, dynamic> json) {
    return GeographicDistribution(
      governorate: json['governorate'] ?? '',
      bookings: json['bookings'] ?? 0,
      revenue: (json['revenue'] ?? 0).toDouble(),
    );
  }
}

class RevenueBySource {
  final double oneTimeBookings;
  final double packageSubscriptions;
  final double total;

  RevenueBySource({
    required this.oneTimeBookings,
    required this.packageSubscriptions,
    required this.total,
  });

  factory RevenueBySource.fromJson(Map<String, dynamic> json) {
    return RevenueBySource(
      oneTimeBookings: (json['one_time_bookings'] ?? 0).toDouble(),
      packageSubscriptions: (json['package_subscriptions'] ?? 0).toDouble(),
      total: (json['total'] ?? 0).toDouble(),
    );
  }
}
