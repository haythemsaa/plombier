import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter/foundation.dart';

/// Service for managing recurring bookings
class RecurringBookingService {
  final String baseUrl;
  final String? authToken;

  RecurringBookingService({
    required this.baseUrl,
    this.authToken,
  });

  /// Get all recurring bookings for current user
  Future<List<RecurringBooking>> getMyRecurringBookings() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api/recurring-bookings'),
        headers: {
          'Authorization': 'Bearer $authToken',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        return (data['data'] as List)
            .map((json) => RecurringBooking.fromJson(json))
            .toList();
      }

      debugPrint('Failed to load recurring bookings: ${response.statusCode}');
      return [];
    } catch (e) {
      debugPrint('Error loading recurring bookings: $e');
      return [];
    }
  }

  /// Create a recurring booking
  Future<RecurringBooking?> create({
    required int serviceId,
    required int addressId,
    required String frequency,
    required String dayOfWeek,
    required String startTime,
    required int durationMinutes,
    required DateTime startDate,
    String? preferredProviderId,
    DateTime? endDate,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/api/recurring-bookings'),
        headers: {
          'Authorization': 'Bearer $authToken',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: json.encode({
          'service_id': serviceId,
          'address_id': addressId,
          'frequency': frequency,
          'day_of_week': dayOfWeek,
          'start_time': startTime,
          'duration_minutes': durationMinutes,
          'start_date': startDate.toIso8601String(),
          if (preferredProviderId != null) 'preferred_provider_id': preferredProviderId,
          if (endDate != null) 'end_date': endDate.toIso8601String(),
        }),
      );

      if (response.statusCode == 201) {
        final data = json.decode(response.body);
        return RecurringBooking.fromJson(data['data']);
      }

      debugPrint('Failed to create recurring booking: ${response.statusCode}');
      return null;
    } catch (e) {
      debugPrint('Error creating recurring booking: $e');
      return null;
    }
  }

  /// Pause recurring booking
  Future<bool> pause(int recurringBookingId) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/api/recurring-bookings/$recurringBookingId/pause'),
        headers: {
          'Authorization': 'Bearer $authToken',
          'Accept': 'application/json',
        },
      );

      return response.statusCode == 200;
    } catch (e) {
      debugPrint('Error pausing recurring booking: $e');
      return false;
    }
  }

  /// Resume recurring booking
  Future<bool> resume(int recurringBookingId) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/api/recurring-bookings/$recurringBookingId/resume'),
        headers: {
          'Authorization': 'Bearer $authToken',
          'Accept': 'application/json',
        },
      );

      return response.statusCode == 200;
    } catch (e) {
      debugPrint('Error resuming recurring booking: $e');
      return false;
    }
  }

  /// Delete/Cancel recurring booking
  Future<bool> cancel(int recurringBookingId) async {
    try {
      final response = await http.delete(
        Uri.parse('$baseUrl/api/recurring-bookings/$recurringBookingId'),
        headers: {
          'Authorization': 'Bearer $authToken',
          'Accept': 'application/json',
        },
      );

      return response.statusCode == 200;
    } catch (e) {
      debugPrint('Error cancelling recurring booking: $e');
      return false;
    }
  }

  /// Update recurring booking
  Future<RecurringBooking?> update(
    int recurringBookingId, {
    String? dayOfWeek,
    String? startTime,
    int? durationMinutes,
  }) async {
    try {
      final response = await http.put(
        Uri.parse('$baseUrl/api/recurring-bookings/$recurringBookingId'),
        headers: {
          'Authorization': 'Bearer $authToken',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: json.encode({
          if (dayOfWeek != null) 'day_of_week': dayOfWeek,
          if (startTime != null) 'start_time': startTime,
          if (durationMinutes != null) 'duration_minutes': durationMinutes,
        }),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        return RecurringBooking.fromJson(data['data']);
      }

      debugPrint('Failed to update recurring booking: ${response.statusCode}');
      return null;
    } catch (e) {
      debugPrint('Error updating recurring booking: $e');
      return null;
    }
  }
}

/// Recurring Booking model
class RecurringBooking {
  final int id;
  final String clientId;
  final int serviceId;
  final int addressId;
  final String? preferredProviderId;
  final String frequency;
  final String dayOfWeek;
  final String startTime;
  final int durationMinutes;
  final DateTime startDate;
  final DateTime? endDate;
  final String status;
  final DateTime? nextOccurrenceDate;
  final String? serviceName;
  final String? addressLabel;
  final String? providerName;

  RecurringBooking({
    required this.id,
    required this.clientId,
    required this.serviceId,
    required this.addressId,
    this.preferredProviderId,
    required this.frequency,
    required this.dayOfWeek,
    required this.startTime,
    required this.durationMinutes,
    required this.startDate,
    this.endDate,
    required this.status,
    this.nextOccurrenceDate,
    this.serviceName,
    this.addressLabel,
    this.providerName,
  });

  factory RecurringBooking.fromJson(Map<String, dynamic> json) {
    return RecurringBooking(
      id: json['id'],
      clientId: json['client_id'],
      serviceId: json['service_id'],
      addressId: json['address_id'],
      preferredProviderId: json['preferred_provider_id'],
      frequency: json['frequency'],
      dayOfWeek: json['day_of_week'],
      startTime: json['start_time'],
      durationMinutes: json['duration_minutes'],
      startDate: DateTime.parse(json['start_date']),
      endDate: json['end_date'] != null ? DateTime.parse(json['end_date']) : null,
      status: json['status'],
      nextOccurrenceDate: json['next_occurrence_date'] != null
          ? DateTime.parse(json['next_occurrence_date'])
          : null,
      serviceName: json['service_name'],
      addressLabel: json['address_label'],
      providerName: json['provider_name'],
    );
  }

  bool get isActive => status == 'active';
  bool get isPaused => status == 'paused';
  bool get isCancelled => status == 'cancelled';

  String get frequencyLabel {
    switch (frequency) {
      case 'weekly':
        return 'Hebdomadaire';
      case 'biweekly':
        return 'Bi-hebdomadaire';
      case 'monthly':
        return 'Mensuel';
      default:
        return frequency;
    }
  }

  String get statusLabel {
    switch (status) {
      case 'active':
        return 'Actif';
      case 'paused':
        return 'En pause';
      case 'cancelled':
        return 'Annulé';
      default:
        return status;
    }
  }
}
