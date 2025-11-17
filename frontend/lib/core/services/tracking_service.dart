import 'package:flutter/foundation.dart';
import 'package:geolocator/geolocator.dart';
import 'package:socket_io_client/socket_io_client.dart' as IO;
import 'dart:async';

/// Real-time GPS tracking service
class TrackingService with ChangeNotifier {
  IO.Socket? _socket;
  bool _isTracking = false;
  Timer? _locationTimer;
  Position? _currentPosition;
  Map<int, ProviderLocation> _providerLocations = {};

  bool get isTracking => _isTracking;
  Position? get currentPosition => _currentPosition;

  /// Start tracking provider's location
  Future<void> startTracking(int bookingId) async {
    if (_isTracking) {
      debugPrint('Already tracking');
      return;
    }

    // Request location permission
    final permission = await _checkPermission();
    if (!permission) {
      debugPrint('Location permission denied');
      return;
    }

    _isTracking = true;

    // Get initial position
    _currentPosition = await Geolocator.getCurrentPosition(
      desiredAccuracy: LocationAccuracy.high,
    );

    // Send location every 10 seconds
    _locationTimer = Timer.periodic(const Duration(seconds: 10), (timer) async {
      await _updateLocation(bookingId);
    });

    // Send initial location
    await _updateLocation(bookingId);

    notifyListeners();
    debugPrint('Tracking started for booking: $bookingId');
  }

  /// Stop tracking
  void stopTracking() {
    _locationTimer?.cancel();
    _locationTimer = null;
    _isTracking = false;
    notifyListeners();
    debugPrint('Tracking stopped');
  }

  /// Update location to server
  Future<void> _updateLocation(int bookingId) async {
    try {
      final position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      );

      _currentPosition = position;

      // Send to server via API or WebSocket
      _socket?.emit('location.update', {
        'booking_id': bookingId,
        'latitude': position.latitude,
        'longitude': position.longitude,
        'accuracy': position.accuracy,
        'speed': position.speed,
        'heading': position.heading,
        'timestamp': DateTime.now().toIso8601String(),
      });

      notifyListeners();
    } catch (e) {
      debugPrint('Error updating location: $e');
    }
  }

  /// Subscribe to provider location updates for a booking
  void subscribeToProviderLocation(int bookingId) {
    _socket?.on('booking.$bookingId:provider.location.updated', (data) {
      final location = ProviderLocation.fromJson(data);
      _providerLocations[bookingId] = location;
      notifyListeners();
    });
  }

  /// Unsubscribe from location updates
  void unsubscribeFromProviderLocation(int bookingId) {
    _socket?.off('booking.$bookingId:provider.location.updated');
    _providerLocations.remove(bookingId);
  }

  /// Get provider's current location for a booking
  ProviderLocation? getProviderLocation(int bookingId) {
    return _providerLocations[bookingId];
  }

  /// Calculate distance between two points (Haversine formula)
  double calculateDistance(
    double lat1,
    double lon1,
    double lat2,
    double lon2,
  ) {
    const earthRadius = 6371; // km

    final dLat = _toRadians(lat2 - lat1);
    final dLon = _toRadians(lon2 - lon1);

    final a = math.sin(dLat / 2) * math.sin(dLat / 2) +
        math.cos(_toRadians(lat1)) *
            math.cos(_toRadians(lat2)) *
            math.sin(dLon / 2) *
            math.sin(dLon / 2);

    final c = 2 * math.atan2(math.sqrt(a), math.sqrt(1 - a));

    return earthRadius * c;
  }

  double _toRadians(double degrees) {
    return degrees * math.pi / 180;
  }

  /// Calculate ETA based on distance and speed
  int? calculateETA(double distanceKm, double speedKmh) {
    if (speedKmh <= 0) return null;

    final hours = distanceKm / speedKmh;
    return (hours * 60).ceil(); // minutes
  }

  /// Check and request location permission
  Future<bool> _checkPermission() async {
    LocationPermission permission = await Geolocator.checkPermission();

    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied) {
        return false;
      }
    }

    if (permission == LocationPermission.deniedForever) {
      return false;
    }

    return true;
  }

  /// Check if location services are enabled
  Future<bool> isLocationServiceEnabled() async {
    return await Geolocator.isLocationServiceEnabled();
  }

  /// Open location settings
  Future<void> openLocationSettings() async {
    await Geolocator.openLocationSettings();
  }

  /// Dispose
  @override
  void dispose() {
    stopTracking();
    _socket?.dispose();
    super.dispose();
  }
}

/// Provider location model
class ProviderLocation {
  final String providerId;
  final double latitude;
  final double longitude;
  final double? speed;
  final double? heading;
  final DateTime timestamp;

  ProviderLocation({
    required this.providerId,
    required this.latitude,
    required this.longitude,
    this.speed,
    this.heading,
    required this.timestamp,
  });

  factory ProviderLocation.fromJson(Map<String, dynamic> json) {
    return ProviderLocation(
      providerId: json['provider_id'],
      latitude: json['latitude'].toDouble(),
      longitude: json['longitude'].toDouble(),
      speed: json['speed']?.toDouble(),
      heading: json['heading']?.toDouble(),
      timestamp: DateTime.parse(json['timestamp']),
    );
  }
}

/// Import math for calculations
import 'dart:math' as math;
