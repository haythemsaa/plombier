import 'package:flutter/material.dart';
import '../../../core/services/api_service.dart';
import '../../../core/services/storage_service.dart';

class AuthProvider with ChangeNotifier {
  final ApiService _apiService;
  final StorageService _storageService;

  bool _isAuthenticated = false;
  bool _isLoading = false;
  String? _error;
  Map<String, dynamic>? _user;

  AuthProvider(this._apiService, this._storageService) {
    _checkAuthStatus();
  }

  bool get isAuthenticated => _isAuthenticated;
  bool get isLoading => _isLoading;
  String? get error => _error;
  Map<String, dynamic>? get user => _user;

  Future<void> _checkAuthStatus() async {
    final token = await _storageService.getToken();
    if (token != null) {
      _user = _storageService.getUserData();
      _isAuthenticated = true;
      notifyListeners();
    }
  }

  Future<bool> login(String phone, String password) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.login(phone, password);

      if (response.statusCode == 200) {
        final data = response.data;
        await _storageService.saveToken(data['token']);
        await _storageService.saveUserData(data['user']);

        _user = data['user'];
        _isAuthenticated = true;
        _isLoading = false;
        notifyListeners();
        return true;
      }
    } catch (e) {
      _error = 'Erreur de connexion. Vérifiez vos identifiants.';
      _isLoading = false;
      notifyListeners();
    }

    _isLoading = false;
    notifyListeners();
    return false;
  }

  Future<bool> register(Map<String, dynamic> data) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.register(data);

      if (response.statusCode == 201) {
        final responseData = response.data;
        await _storageService.saveToken(responseData['token']);
        await _storageService.saveUserData(responseData['user']);

        _user = responseData['user'];
        _isAuthenticated = true;
        _isLoading = false;
        notifyListeners();
        return true;
      }
    } catch (e) {
      _error = 'Erreur lors de l\'inscription.';
      _isLoading = false;
      notifyListeners();
    }

    _isLoading = false;
    notifyListeners();
    return false;
  }

  Future<void> logout() async {
    await _storageService.clearAll();
    _isAuthenticated = false;
    _user = null;
    notifyListeners();
  }

  void clearError() {
    _error = null;
    notifyListeners();
  }
}
