import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter/foundation.dart';

/// Service for managing loyalty program
class LoyaltyService {
  final String baseUrl;
  final String? authToken;

  LoyaltyService({
    required this.baseUrl,
    this.authToken,
  });

  /// Get user's loyalty status
  Future<LoyaltyStatus?> getStatus() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api/loyalty/status'),
        headers: {
          'Authorization': 'Bearer $authToken',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        return LoyaltyStatus.fromJson(data['data']);
      }

      debugPrint('Failed to load loyalty status: ${response.statusCode}');
      return null;
    } catch (e) {
      debugPrint('Error loading loyalty status: $e');
      return null;
    }
  }

  /// Get leaderboard
  Future<List<LeaderboardEntry>> getLeaderboard({int limit = 10}) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api/loyalty/leaderboard?limit=$limit'),
        headers: {
          'Authorization': 'Bearer $authToken',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        final entries = (data['data'] as List)
            .map((json) => LeaderboardEntry.fromJson(json))
            .toList();
        return entries;
      }

      debugPrint('Failed to load leaderboard: ${response.statusCode}');
      return [];
    } catch (e) {
      debugPrint('Error loading leaderboard: $e');
      return [];
    }
  }

  /// Get all loyalty tiers
  Future<List<LoyaltyTier>> getTiers() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api/loyalty/tiers'),
        headers: {
          'Authorization': 'Bearer $authToken',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        final tiers = (data['data'] as List)
            .map((json) => LoyaltyTier.fromJson(json))
            .toList();
        return tiers;
      }

      debugPrint('Failed to load tiers: ${response.statusCode}');
      return [];
    } catch (e) {
      debugPrint('Error loading tiers: $e');
      return [];
    }
  }

  /// Get points history
  Future<List<PointsTransaction>> getPointsHistory() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api/loyalty/points-history'),
        headers: {
          'Authorization': 'Bearer $authToken',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        final transactions = (data['data'] as List)
            .map((json) => PointsTransaction.fromJson(json))
            .toList();
        return transactions;
      }

      debugPrint('Failed to load points history: ${response.statusCode}');
      return [];
    } catch (e) {
      debugPrint('Error loading points history: $e');
      return [];
    }
  }
}

/// Loyalty Status model
class LoyaltyStatus {
  final String currentTier;
  final int tierLevel;
  final int totalPoints;
  final int? pointsToNextTier;
  final double currentDiscount;
  final List<String> benefits;
  final int? nextTierLevel;
  final String? nextTierName;

  LoyaltyStatus({
    required this.currentTier,
    required this.tierLevel,
    required this.totalPoints,
    this.pointsToNextTier,
    required this.currentDiscount,
    required this.benefits,
    this.nextTierLevel,
    this.nextTierName,
  });

  factory LoyaltyStatus.fromJson(Map<String, dynamic> json) {
    return LoyaltyStatus(
      currentTier: json['current_tier'] ?? 'Bronze',
      tierLevel: json['tier_level'] ?? 1,
      totalPoints: json['total_points'] ?? 0,
      pointsToNextTier: json['points_to_next_tier'],
      currentDiscount: (json['current_discount'] ?? 0).toDouble(),
      benefits: List<String>.from(json['benefits'] ?? []),
      nextTierLevel: json['next_tier_level'],
      nextTierName: json['next_tier_name'],
    );
  }

  bool get isMaxTier => pointsToNextTier == null;

  double get progressToNextTier {
    if (pointsToNextTier == null) return 1.0;
    // Calculate based on current tier requirements
    return 0.65; // Simplified - should calculate actual progress
  }
}

/// Loyalty Tier model
class LoyaltyTier {
  final int id;
  final String name;
  final int level;
  final int minPointsRequired;
  final int minBookingsRequired;
  final double minSpendingRequired;
  final double discountPercentage;
  final List<String> benefits;

  LoyaltyTier({
    required this.id,
    required this.name,
    required this.level,
    required this.minPointsRequired,
    required this.minBookingsRequired,
    required this.minSpendingRequired,
    required this.discountPercentage,
    required this.benefits,
  });

  factory LoyaltyTier.fromJson(Map<String, dynamic> json) {
    return LoyaltyTier(
      id: json['id'],
      name: json['name'],
      level: json['level'],
      minPointsRequired: json['min_points_required'] ?? 0,
      minBookingsRequired: json['min_bookings_required'] ?? 0,
      minSpendingRequired: (json['min_spending_required'] ?? 0).toDouble(),
      discountPercentage: (json['discount_percentage'] ?? 0).toDouble(),
      benefits: List<String>.from(json['benefits'] ?? []),
    );
  }
}

/// Leaderboard Entry model
class LeaderboardEntry {
  final int rank;
  final String name;
  final String tier;
  final int points;
  final bool isCurrentUser;

  LeaderboardEntry({
    required this.rank,
    required this.name,
    required this.tier,
    required this.points,
    this.isCurrentUser = false,
  });

  factory LeaderboardEntry.fromJson(Map<String, dynamic> json) {
    return LeaderboardEntry(
      rank: json['rank'],
      name: json['name'],
      tier: json['tier'],
      points: json['points'],
      isCurrentUser: json['is_current_user'] ?? false,
    );
  }
}

/// Points Transaction model
class PointsTransaction {
  final String type;
  final int points;
  final String description;
  final DateTime createdAt;

  PointsTransaction({
    required this.type,
    required this.points,
    required this.description,
    required this.createdAt,
  });

  factory PointsTransaction.fromJson(Map<String, dynamic> json) {
    return PointsTransaction(
      type: json['type'],
      points: json['points'],
      description: json['description'],
      createdAt: DateTime.parse(json['created_at']),
    );
  }

  bool get isEarned => points > 0;
}
