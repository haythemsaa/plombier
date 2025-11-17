import 'package:flutter/material.dart';
import '../../../core/services/api_service.dart';

class ReviewProvider with ChangeNotifier {
  final ApiService _apiService;

  List<Map<String, dynamic>> _myReviews = [];
  List<Map<String, dynamic>> _reviewsAboutMe = [];
  Map<String, dynamic>? _stats;
  bool _isLoading = false;
  String? _error;

  ReviewProvider(this._apiService);

  List<Map<String, dynamic>> get myReviews => _myReviews;
  List<Map<String, dynamic>> get reviewsAboutMe => _reviewsAboutMe;
  Map<String, dynamic>? get stats => _stats;
  bool get isLoading => _isLoading;
  String? get error => _error;

  /// Submit a new review
  Future<bool> submitReview(Map<String, dynamic> reviewData) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.submitReview(
        reviewData['booking_id'],
        reviewData,
      );

      if (response.statusCode == 201) {
        _isLoading = false;
        notifyListeners();
        return true;
      }
    } catch (e) {
      _error = 'Erreur lors de la soumission de l\'avis';
      _isLoading = false;
      notifyListeners();
    }

    _isLoading = false;
    notifyListeners();
    return false;
  }

  /// Get my reviews (as client)
  Future<void> fetchMyReviews() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.dio.get('/my-reviews');

      if (response.statusCode == 200) {
        _myReviews = List<Map<String, dynamic>>.from(
          response.data['reviews']['data'],
        );
      }
    } catch (e) {
      _error = 'Erreur lors du chargement des avis';
    }

    _isLoading = false;
    notifyListeners();
  }

  /// Get reviews about me (as provider)
  Future<void> fetchReviewsAboutMe() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.dio.get('/reviews-about-me');

      if (response.statusCode == 200) {
        _reviewsAboutMe = List<Map<String, dynamic>>.from(
          response.data['reviews']['data'],
        );
        _stats = response.data['stats'];
      }
    } catch (e) {
      _error = 'Erreur lors du chargement des avis';
    }

    _isLoading = false;
    notifyListeners();
  }

  /// Get provider reviews
  Future<Map<String, dynamic>?> fetchProviderReviews(String providerId) async {
    try {
      final response = await _apiService.dio.get('/providers/$providerId/reviews');

      if (response.statusCode == 200) {
        return {
          'reviews': response.data['reviews']['data'],
          'stats': response.data['stats'],
        };
      }
    } catch (e) {
      _error = 'Erreur lors du chargement des avis';
    }

    return null;
  }

  /// Respond to a review (provider only)
  Future<bool> respondToReview(int reviewId, String response) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final apiResponse = await _apiService.dio.post(
        '/reviews/$reviewId/response',
        data: {'response': response},
      );

      if (apiResponse.statusCode == 200) {
        // Update local reviews
        await fetchReviewsAboutMe();
        return true;
      }
    } catch (e) {
      _error = 'Erreur lors de la réponse à l\'avis';
    }

    _isLoading = false;
    notifyListeners();
    return false;
  }

  /// Check if booking can be reviewed
  Future<Map<String, dynamic>?> canReviewBooking(String bookingId) async {
    try {
      final response = await _apiService.dio.get('/bookings/$bookingId/can-review');

      if (response.statusCode == 200) {
        return response.data;
      }
    } catch (e) {
      _error = 'Erreur lors de la vérification';
    }

    return null;
  }

  void clearError() {
    _error = null;
    notifyListeners();
  }
}
