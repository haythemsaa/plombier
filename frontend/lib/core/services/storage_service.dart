import 'package:shared_preferences.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class StorageService {
  late SharedPreferences _prefs;
  final _secureStorage = const FlutterSecureStorage();

  Future<void> init() async {
    _prefs = await SharedPreferences.getInstance();
  }

  // User token
  Future<void> saveToken(String token) async {
    await _secureStorage.write(key: 'auth_token', value: token);
  }

  Future<String?> getToken() async {
    return await _secureStorage.read(key: 'auth_token');
  }

  Future<void> deleteToken() async {
    await _secureStorage.delete(key: 'auth_token');
  }

  // User preferences
  Future<void> setLanguage(String lang) async {
    await _prefs.setString('language', lang);
  }

  String getLanguage() {
    return _prefs.getString('language') ?? 'fr';
  }

  Future<void> setOnboardingComplete(bool complete) async {
    await _prefs.setBool('onboarding_complete', complete);
  }

  bool isOnboardingComplete() {
    return _prefs.getBool('onboarding_complete') ?? false;
  }

  // User data
  Future<void> saveUserData(Map<String, dynamic> user) async {
    await _prefs.setString('user_id', user['id']);
    await _prefs.setString('user_name', '${user['first_name']} ${user['last_name']}');
    await _prefs.setString('user_type', user['type']);
    await _prefs.setString('user_phone', user['phone']);
    if (user['email'] != null) {
      await _prefs.setString('user_email', user['email']);
    }
  }

  Map<String, dynamic>? getUserData() {
    final userId = _prefs.getString('user_id');
    if (userId == null) return null;

    return {
      'id': userId,
      'name': _prefs.getString('user_name'),
      'type': _prefs.getString('user_type'),
      'phone': _prefs.getString('user_phone'),
      'email': _prefs.getString('user_email'),
    };
  }

  Future<void> clearAll() async {
    await _secureStorage.deleteAll();
    await _prefs.clear();
  }
}
