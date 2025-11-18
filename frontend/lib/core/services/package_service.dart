import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter/foundation.dart';

/// Service for managing service packages and subscriptions
class PackageService {
  final String baseUrl;
  final String? authToken;

  PackageService({
    required this.baseUrl,
    this.authToken,
  });

  /// Get all available packages
  Future<List<ServicePackage>> getPackages({int? serviceId}) async {
    try {
      final queryParams = serviceId != null ? '?service_id=$serviceId' : '';
      final response = await http.get(
        Uri.parse('$baseUrl/api/packages$queryParams'),
        headers: {
          if (authToken != null) 'Authorization': 'Bearer $authToken',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        final packages = (data['data'] as List)
            .map((json) => ServicePackage.fromJson(json))
            .toList();
        return packages;
      }

      debugPrint('Failed to load packages: ${response.statusCode}');
      return [];
    } catch (e) {
      debugPrint('Error loading packages: $e');
      return [];
    }
  }

  /// Get package details
  Future<ServicePackage?> getPackage(int packageId) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api/packages/$packageId'),
        headers: {
          if (authToken != null) 'Authorization': 'Bearer $authToken',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        return ServicePackage.fromJson(data['data']);
      }

      debugPrint('Failed to load package: ${response.statusCode}');
      return null;
    } catch (e) {
      debugPrint('Error loading package: $e');
      return null;
    }
  }

  /// Subscribe to a package
  Future<PackageSubscription?> subscribe(int packageId) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/api/packages/$packageId/subscribe'),
        headers: {
          'Authorization': 'Bearer $authToken',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      );

      if (response.statusCode == 201) {
        final data = json.decode(response.body);
        return PackageSubscription.fromJson(data['data']);
      }

      debugPrint('Failed to subscribe: ${response.statusCode}');
      return null;
    } catch (e) {
      debugPrint('Error subscribing: $e');
      return null;
    }
  }

  /// Get user's subscriptions
  Future<List<PackageSubscription>> getMySubscriptions() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api/subscriptions'),
        headers: {
          'Authorization': 'Bearer $authToken',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        final subscriptions = (data['data'] as List)
            .map((json) => PackageSubscription.fromJson(json))
            .toList();
        return subscriptions;
      }

      debugPrint('Failed to load subscriptions: ${response.statusCode}');
      return [];
    } catch (e) {
      debugPrint('Error loading subscriptions: $e');
      return [];
    }
  }

  /// Pause subscription
  Future<bool> pauseSubscription(int subscriptionId) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/api/subscriptions/$subscriptionId/pause'),
        headers: {
          'Authorization': 'Bearer $authToken',
          'Accept': 'application/json',
        },
      );

      return response.statusCode == 200;
    } catch (e) {
      debugPrint('Error pausing subscription: $e');
      return false;
    }
  }

  /// Resume subscription
  Future<bool> resumeSubscription(int subscriptionId) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/api/subscriptions/$subscriptionId/resume'),
        headers: {
          'Authorization': 'Bearer $authToken',
          'Accept': 'application/json',
        },
      );

      return response.statusCode == 200;
    } catch (e) {
      debugPrint('Error resuming subscription: $e');
      return false;
    }
  }

  /// Cancel subscription
  Future<bool> cancelSubscription(int subscriptionId) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/api/subscriptions/$subscriptionId/cancel'),
        headers: {
          'Authorization': 'Bearer $authToken',
          'Accept': 'application/json',
        },
      );

      return response.statusCode == 200;
    } catch (e) {
      debugPrint('Error cancelling subscription: $e');
      return false;
    }
  }

  /// Renew subscription
  Future<bool> renewSubscription(int subscriptionId) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/api/subscriptions/$subscriptionId/renew'),
        headers: {
          'Authorization': 'Bearer $authToken',
          'Accept': 'application/json',
        },
      );

      return response.statusCode == 200;
    } catch (e) {
      debugPrint('Error renewing subscription: $e');
      return false;
    }
  }
}

/// Service Package model
class ServicePackage {
  final int id;
  final int serviceId;
  final String name;
  final String type;
  final int sessionsIncluded;
  final double price;
  final double originalPrice;
  final int? validityDays;
  final List<String> features;
  final bool isActive;
  final String? serviceName;

  ServicePackage({
    required this.id,
    required this.serviceId,
    required this.name,
    required this.type,
    required this.sessionsIncluded,
    required this.price,
    required this.originalPrice,
    this.validityDays,
    required this.features,
    required this.isActive,
    this.serviceName,
  });

  factory ServicePackage.fromJson(Map<String, dynamic> json) {
    return ServicePackage(
      id: json['id'],
      serviceId: json['service_id'],
      name: json['name'],
      type: json['type'],
      sessionsIncluded: json['sessions_included'],
      price: (json['price'] ?? 0).toDouble(),
      originalPrice: (json['original_price'] ?? 0).toDouble(),
      validityDays: json['validity_days'],
      features: List<String>.from(json['features'] ?? []),
      isActive: json['is_active'] ?? true,
      serviceName: json['service_name'],
    );
  }

  double get savings => originalPrice - price;
  double get savingsPercentage => ((savings / originalPrice) * 100);

  String get typeLabel {
    switch (type) {
      case 'one_time':
        return 'Forfait unique';
      case 'monthly':
        return 'Mensuel';
      case 'quarterly':
        return 'Trimestriel';
      case 'annual':
        return 'Annuel';
      default:
        return type;
    }
  }

  bool get isSubscription => ['monthly', 'quarterly', 'annual'].contains(type);
}

/// Package Subscription model
class PackageSubscription {
  final int id;
  final int packageId;
  final String clientId;
  final String status;
  final int sessionsRemaining;
  final int sessionsUsed;
  final DateTime startsAt;
  final DateTime? expiresAt;
  final DateTime? cancelledAt;
  final double paidAmount;
  final ServicePackage? package;

  PackageSubscription({
    required this.id,
    required this.packageId,
    required this.clientId,
    required this.status,
    required this.sessionsRemaining,
    required this.sessionsUsed,
    required this.startsAt,
    this.expiresAt,
    this.cancelledAt,
    required this.paidAmount,
    this.package,
  });

  factory PackageSubscription.fromJson(Map<String, dynamic> json) {
    return PackageSubscription(
      id: json['id'],
      packageId: json['package_id'],
      clientId: json['client_id'],
      status: json['status'],
      sessionsRemaining: json['sessions_remaining'] ?? 0,
      sessionsUsed: json['sessions_used'] ?? 0,
      startsAt: DateTime.parse(json['starts_at']),
      expiresAt: json['expires_at'] != null ? DateTime.parse(json['expires_at']) : null,
      cancelledAt: json['cancelled_at'] != null ? DateTime.parse(json['cancelled_at']) : null,
      paidAmount: (json['paid_amount'] ?? 0).toDouble(),
      package: json['package'] != null ? ServicePackage.fromJson(json['package']) : null,
    );
  }

  bool get isActive => status == 'active';
  bool get isPaused => status == 'paused';
  bool get isCancelled => status == 'cancelled';
  bool get isExpired => status == 'expired';

  String get statusLabel {
    switch (status) {
      case 'active':
        return 'Actif';
      case 'paused':
        return 'En pause';
      case 'cancelled':
        return 'Annulé';
      case 'expired':
        return 'Expiré';
      default:
        return status;
    }
  }

  int get totalSessions => sessionsUsed + sessionsRemaining;
  double get usagePercentage => totalSessions > 0 ? (sessionsUsed / totalSessions) * 100 : 0;

  int? get daysUntilExpiry {
    if (expiresAt == null) return null;
    return expiresAt!.difference(DateTime.now()).inDays;
  }

  bool get isExpiringSoon {
    final days = daysUntilExpiry;
    return days != null && days <= 7 && days > 0;
  }
}
