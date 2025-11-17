import 'package:flutter/foundation.dart';
import '../../../core/services/api_service.dart';

class AddressProvider with ChangeNotifier {
  final ApiService _apiService;

  AddressProvider(this._apiService);

  List<dynamic> _addresses = [];
  bool _isLoading = false;
  String? _error;

  List<dynamic> get addresses => _addresses;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> fetchAddresses() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.get('/addresses');

      if (response['success']) {
        _addresses = response['data'] ?? [];
      } else {
        _error = response['message'] ?? 'Failed to fetch addresses';
      }
    } catch (e) {
      _error = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> createAddress({
    required String label,
    required String addressLine1,
    String? addressLine2,
    required String city,
    required String postalCode,
    String? state,
    double? latitude,
    double? longitude,
    String? instructions,
    bool isDefault = false,
  }) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.post('/addresses', {
        'label': label,
        'address_line1': addressLine1,
        'address_line2': addressLine2,
        'city': city,
        'postal_code': postalCode,
        'state': state,
        'country': 'TN',
        'latitude': latitude,
        'longitude': longitude,
        'instructions': instructions,
        'is_default': isDefault,
      });

      if (response['success']) {
        await fetchAddresses(); // Refresh the list
        return true;
      } else {
        _error = response['message'] ?? 'Failed to create address';
        return false;
      }
    } catch (e) {
      _error = e.toString();
      return false;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> updateAddress({
    required String addressId,
    required String label,
    required String addressLine1,
    String? addressLine2,
    required String city,
    required String postalCode,
    String? state,
    double? latitude,
    double? longitude,
    String? instructions,
    bool isDefault = false,
  }) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.put('/addresses/$addressId', {
        'label': label,
        'address_line1': addressLine1,
        'address_line2': addressLine2,
        'city': city,
        'postal_code': postalCode,
        'state': state,
        'country': 'TN',
        'latitude': latitude,
        'longitude': longitude,
        'instructions': instructions,
        'is_default': isDefault,
      });

      if (response['success']) {
        await fetchAddresses(); // Refresh the list
        return true;
      } else {
        _error = response['message'] ?? 'Failed to update address';
        return false;
      }
    } catch (e) {
      _error = e.toString();
      return false;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> deleteAddress(String addressId) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.delete('/addresses/$addressId');

      if (response['success']) {
        await fetchAddresses(); // Refresh the list
        return true;
      } else {
        _error = response['message'] ?? 'Failed to delete address';
        return false;
      }
    } catch (e) {
      _error = e.toString();
      return false;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> setDefaultAddress(String addressId) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.put('/addresses/$addressId/set-default', {});

      if (response['success']) {
        await fetchAddresses(); // Refresh the list
        return true;
      } else {
        _error = response['message'] ?? 'Failed to set default address';
        return false;
      }
    } catch (e) {
      _error = e.toString();
      return false;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }
}
