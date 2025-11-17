import 'package:flutter/material.dart';
import '../../../core/services/api_service.dart';

class BookingProvider with ChangeNotifier {
  final ApiService _apiService;

  List<Map<String, dynamic>> _bookings = [];
  bool _isLoading = false;
  String? _error;

  BookingProvider(this._apiService);

  List<Map<String, dynamic>> get bookings => _bookings;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> fetchBookings() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.getMyBookings();

      if (response.statusCode == 200) {
        _bookings = List<Map<String, dynamic>>.from(response.data['bookings']);
      }
    } catch (e) {
      _error = 'Erreur lors du chargement des réservations';
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<bool> createBooking(Map<String, dynamic> bookingData) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.createBooking(bookingData);

      if (response.statusCode == 201) {
        await fetchBookings(); // Refresh bookings list
        return true;
      }
    } catch (e) {
      _error = 'Erreur lors de la création de la réservation';
    }

    _isLoading = false;
    notifyListeners();
    return false;
  }

  Future<bool> cancelBooking(String bookingId, String reason) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.cancelBooking(bookingId, reason);

      if (response.statusCode == 200) {
        await fetchBookings(); // Refresh bookings list
        return true;
      }
    } catch (e) {
      _error = 'Erreur lors de l\'annulation';
    }

    _isLoading = false;
    notifyListeners();
    return false;
  }

  void clearError() {
    _error = null;
    notifyListeners();
  }
}
