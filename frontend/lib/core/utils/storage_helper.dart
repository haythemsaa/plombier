import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';

/// Helper class for local storage using SharedPreferences
class StorageHelper {
  static SharedPreferences? _prefs;

  /// Initialize storage
  static Future<void> initialize() async {
    _prefs = await SharedPreferences.getInstance();
  }

  /// Get SharedPreferences instance
  static SharedPreferences get prefs {
    if (_prefs == null) {
      throw Exception('StorageHelper not initialized. Call initialize() first.');
    }
    return _prefs!;
  }

  /// Save string
  static Future<bool> setString(String key, String value) async {
    return await prefs.setString(key, value);
  }

  /// Get string
  static String? getString(String key, {String? defaultValue}) {
    return prefs.getString(key) ?? defaultValue;
  }

  /// Save int
  static Future<bool> setInt(String key, int value) async {
    return await prefs.setInt(key, value);
  }

  /// Get int
  static int? getInt(String key, {int? defaultValue}) {
    return prefs.getInt(key) ?? defaultValue;
  }

  /// Save double
  static Future<bool> setDouble(String key, double value) async {
    return await prefs.setDouble(key, value);
  }

  /// Get double
  static double? getDouble(String key, {double? defaultValue}) {
    return prefs.getDouble(key) ?? defaultValue;
  }

  /// Save bool
  static Future<bool> setBool(String key, bool value) async {
    return await prefs.setBool(key, value);
  }

  /// Get bool
  static bool? getBool(String key, {bool? defaultValue}) {
    return prefs.getBool(key) ?? defaultValue;
  }

  /// Save list of strings
  static Future<bool> setStringList(String key, List<String> value) async {
    return await prefs.setStringList(key, value);
  }

  /// Get list of strings
  static List<String>? getStringList(String key) {
    return prefs.getStringList(key);
  }

  /// Save JSON object
  static Future<bool> setJson(String key, Map<String, dynamic> value) async {
    return await prefs.setString(key, jsonEncode(value));
  }

  /// Get JSON object
  static Map<String, dynamic>? getJson(String key) {
    final String? jsonString = prefs.getString(key);
    if (jsonString == null) return null;
    try {
      return jsonDecode(jsonString) as Map<String, dynamic>;
    } catch (e) {
      return null;
    }
  }

  /// Remove key
  static Future<bool> remove(String key) async {
    return await prefs.remove(key);
  }

  /// Clear all data
  static Future<bool> clear() async {
    return await prefs.clear();
  }

  /// Check if key exists
  static bool containsKey(String key) {
    return prefs.containsKey(key);
  }

  /// Get all keys
  static Set<String> getKeys() {
    return prefs.getKeys();
  }
}
