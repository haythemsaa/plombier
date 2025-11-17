import 'package:flutter/foundation.dart';
import '../../../core/services/api_service.dart';

class ServiceProvider with ChangeNotifier {
  final ApiService _apiService;

  ServiceProvider(this._apiService);

  List<dynamic> _categories = [];
  List<dynamic> _services = [];
  List<dynamic> _filteredServices = [];
  String? _selectedCategoryId;
  bool _isLoading = false;
  String? _error;

  List<dynamic> get categories => _categories;
  List<dynamic> get services => _filteredServices.isEmpty ? _services : _filteredServices;
  String? get selectedCategoryId => _selectedCategoryId;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> fetchCategories() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.get('/services/categories');
      if (response['success']) {
        _categories = response['data'];
      } else {
        _error = response['message'] ?? 'Failed to fetch categories';
      }
    } catch (e) {
      _error = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> fetchServices({String? categoryId}) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final queryParams = categoryId != null ? '?category_id=$categoryId' : '';
      final response = await _apiService.get('/services$queryParams');

      if (response['success']) {
        _services = response['data'];
        _filteredServices = [];
      } else {
        _error = response['message'] ?? 'Failed to fetch services';
      }
    } catch (e) {
      _error = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  void filterByCategory(String? categoryId) {
    _selectedCategoryId = categoryId;

    if (categoryId == null) {
      _filteredServices = [];
    } else {
      _filteredServices = _services
          .where((service) => service['category_id'] == categoryId)
          .toList();
    }

    notifyListeners();
  }

  void searchServices(String query) {
    if (query.isEmpty) {
      _filteredServices = [];
    } else {
      _filteredServices = _services.where((service) {
        final nameFr = service['name_fr']?.toLowerCase() ?? '';
        final nameAr = service['name_ar']?.toLowerCase() ?? '';
        final searchLower = query.toLowerCase();
        return nameFr.contains(searchLower) || nameAr.contains(searchLower);
      }).toList();
    }

    notifyListeners();
  }

  void clearFilters() {
    _selectedCategoryId = null;
    _filteredServices = [];
    notifyListeners();
  }
}
