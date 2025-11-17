import 'package:flutter/foundation.dart';
import '../../../core/services/api_service.dart';

class ProviderProvider with ChangeNotifier {
  final ApiService _apiService;

  ProviderProvider(this._apiService);

  List<dynamic> _providers = [];
  Map<String, dynamic>? _currentProvider;
  bool _isLoading = false;
  String? _error;

  List<dynamic> get providers => _providers;
  Map<String, dynamic>? get currentProvider => _currentProvider;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> searchProviders({
    String? serviceId,
    double? latitude,
    double? longitude,
    int? radius,
  }) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final queryParams = <String, String>{};

      if (serviceId != null) queryParams['service_id'] = serviceId;
      if (latitude != null) queryParams['latitude'] = latitude.toString();
      if (longitude != null) queryParams['longitude'] = longitude.toString();
      if (radius != null) queryParams['radius'] = radius.toString();

      final queryString = queryParams.entries
          .map((e) => '${e.key}=${Uri.encodeComponent(e.value)}')
          .join('&');

      final response = await _apiService.get(
        '/providers/search${queryString.isNotEmpty ? '?$queryString' : ''}',
      );

      if (response['success']) {
        _providers = response['data'] ?? [];
      } else {
        _error = response['message'] ?? 'Failed to fetch providers';
      }
    } catch (e) {
      _error = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> getProviderDetails(String providerId) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.get('/providers/$providerId');

      if (response['success']) {
        _currentProvider = response['data'];
      } else {
        _error = response['message'] ?? 'Failed to fetch provider details';
      }
    } catch (e) {
      _error = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  void clearCurrentProvider() {
    _currentProvider = null;
    notifyListeners();
  }
}
