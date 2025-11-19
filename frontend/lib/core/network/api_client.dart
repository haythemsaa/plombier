import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:flutter/foundation.dart';
import '../config/app_config.dart';
import '../utils/storage_helper.dart';

/// Complete API Client with interceptors and error handling
class ApiClient {
  final String baseUrl;
  final http.Client _client;
  String? _authToken;

  ApiClient({
    String? baseUrl,
    http.Client? client,
  })  : baseUrl = baseUrl ?? AppConfig.apiBaseUrl,
        _client = client ?? http.Client();

  /// Initialize with auth token
  Future<void> initialize() async {
    _authToken = await StorageHelper.getString(StorageKeys.authToken);
  }

  /// Set auth token
  void setAuthToken(String? token) {
    _authToken = token;
    if (token != null) {
      StorageHelper.setString(StorageKeys.authToken, token);
    } else {
      StorageHelper.remove(StorageKeys.authToken);
    }
  }

  /// Get common headers
  Map<String, String> _getHeaders({Map<String, String>? extraHeaders}) {
    final headers = <String, String>{
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };

    if (_authToken != null) {
      headers['Authorization'] = 'Bearer $_authToken';
    }

    if (extraHeaders != null) {
      headers.addAll(extraHeaders);
    }

    return headers;
  }

  /// GET request
  Future<ApiResponse<T>> get<T>(
    String endpoint, {
    Map<String, dynamic>? queryParameters,
    Map<String, String>? headers,
  }) async {
    try {
      final uri = _buildUri(endpoint, queryParameters);

      if (AppConfig.enableLogging) {
        debugPrint('GET: $uri');
      }

      final response = await _client
          .get(uri, headers: _getHeaders(extraHeaders: headers))
          .timeout(AppConfig.connectionTimeout);

      return _handleResponse<T>(response);
    } on SocketException {
      return ApiResponse.error('Pas de connexion internet');
    } on TimeoutException {
      return ApiResponse.error('Délai d\'attente dépassé');
    } catch (e) {
      if (AppConfig.enableLogging) {
        debugPrint('GET Error: $e');
      }
      return ApiResponse.error('Une erreur est survenue: $e');
    }
  }

  /// POST request
  Future<ApiResponse<T>> post<T>(
    String endpoint, {
    dynamic body,
    Map<String, dynamic>? queryParameters,
    Map<String, String>? headers,
  }) async {
    try {
      final uri = _buildUri(endpoint, queryParameters);

      if (AppConfig.enableLogging) {
        debugPrint('POST: $uri');
        debugPrint('Body: ${jsonEncode(body)}');
      }

      final response = await _client
          .post(
            uri,
            headers: _getHeaders(extraHeaders: headers),
            body: body != null ? jsonEncode(body) : null,
          )
          .timeout(AppConfig.connectionTimeout);

      return _handleResponse<T>(response);
    } on SocketException {
      return ApiResponse.error('Pas de connexion internet');
    } on TimeoutException {
      return ApiResponse.error('Délai d\'attente dépassé');
    } catch (e) {
      if (AppConfig.enableLogging) {
        debugPrint('POST Error: $e');
      }
      return ApiResponse.error('Une erreur est survenue: $e');
    }
  }

  /// PUT request
  Future<ApiResponse<T>> put<T>(
    String endpoint, {
    dynamic body,
    Map<String, dynamic>? queryParameters,
    Map<String, String>? headers,
  }) async {
    try {
      final uri = _buildUri(endpoint, queryParameters);

      if (AppConfig.enableLogging) {
        debugPrint('PUT: $uri');
        debugPrint('Body: ${jsonEncode(body)}');
      }

      final response = await _client
          .put(
            uri,
            headers: _getHeaders(extraHeaders: headers),
            body: body != null ? jsonEncode(body) : null,
          )
          .timeout(AppConfig.connectionTimeout);

      return _handleResponse<T>(response);
    } on SocketException {
      return ApiResponse.error('Pas de connexion internet');
    } on TimeoutException {
      return ApiResponse.error('Délai d\'attente dépassé');
    } catch (e) {
      if (AppConfig.enableLogging) {
        debugPrint('PUT Error: $e');
      }
      return ApiResponse.error('Une erreur est survenue: $e');
    }
  }

  /// DELETE request
  Future<ApiResponse<T>> delete<T>(
    String endpoint, {
    Map<String, dynamic>? queryParameters,
    Map<String, String>? headers,
  }) async {
    try {
      final uri = _buildUri(endpoint, queryParameters);

      if (AppConfig.enableLogging) {
        debugPrint('DELETE: $uri');
      }

      final response = await _client
          .delete(uri, headers: _getHeaders(extraHeaders: headers))
          .timeout(AppConfig.connectionTimeout);

      return _handleResponse<T>(response);
    } on SocketException {
      return ApiResponse.error('Pas de connexion internet');
    } on TimeoutException {
      return ApiResponse.error('Délai d\'attente dépassé');
    } catch (e) {
      if (AppConfig.enableLogging) {
        debugPrint('DELETE Error: $e');
      }
      return ApiResponse.error('Une erreur est survenue: $e');
    }
  }

  /// Upload file
  Future<ApiResponse<T>> upload<T>(
    String endpoint,
    File file, {
    String fieldName = 'file',
    Map<String, String>? fields,
  }) async {
    try {
      final uri = _buildUri(endpoint);

      if (AppConfig.enableLogging) {
        debugPrint('UPLOAD: $uri');
      }

      final request = http.MultipartRequest('POST', uri);
      request.headers.addAll(_getHeaders());

      // Add file
      request.files.add(await http.MultipartFile.fromPath(fieldName, file.path));

      // Add fields
      if (fields != null) {
        request.fields.addAll(fields);
      }

      final streamedResponse = await request.send().timeout(AppConfig.receiveTimeout);
      final response = await http.Response.fromStream(streamedResponse);

      return _handleResponse<T>(response);
    } on SocketException {
      return ApiResponse.error('Pas de connexion internet');
    } on TimeoutException {
      return ApiResponse.error('Délai d\'attente dépassé');
    } catch (e) {
      if (AppConfig.enableLogging) {
        debugPrint('UPLOAD Error: $e');
      }
      return ApiResponse.error('Une erreur est survenue: $e');
    }
  }

  /// Build URI with query parameters
  Uri _buildUri(String endpoint, [Map<String, dynamic>? queryParameters]) {
    final path = endpoint.startsWith('/') ? endpoint : '/$endpoint';

    if (queryParameters != null && queryParameters.isNotEmpty) {
      return Uri.parse('$baseUrl$path').replace(
        queryParameters: queryParameters.map(
          (key, value) => MapEntry(key, value.toString()),
        ),
      );
    }

    return Uri.parse('$baseUrl$path');
  }

  /// Handle HTTP response
  ApiResponse<T> _handleResponse<T>(http.Response response) {
    if (AppConfig.enableLogging) {
      debugPrint('Response Status: ${response.statusCode}');
      debugPrint('Response Body: ${response.body}');
    }

    try {
      final dynamic decodedBody = response.body.isNotEmpty
          ? jsonDecode(response.body)
          : null;

      switch (response.statusCode) {
        case 200:
        case 201:
          return ApiResponse.success(
            data: decodedBody is Map && decodedBody.containsKey('data')
                ? decodedBody['data']
                : decodedBody,
            message: decodedBody is Map && decodedBody.containsKey('message')
                ? decodedBody['message']
                : null,
          );

        case 400:
          return ApiResponse.error(
            decodedBody is Map && decodedBody.containsKey('error')
                ? decodedBody['error']
                : 'Requête invalide',
          );

        case 401:
          // Token expired or invalid
          setAuthToken(null);
          return ApiResponse.error(
            'Session expirée. Veuillez vous reconnecter.',
            statusCode: 401,
          );

        case 403:
          return ApiResponse.error(
            'Accès refusé',
            statusCode: 403,
          );

        case 404:
          return ApiResponse.error(
            'Ressource non trouvée',
            statusCode: 404,
          );

        case 422:
          // Validation error
          final errors = decodedBody is Map && decodedBody.containsKey('errors')
              ? decodedBody['errors']
              : null;
          return ApiResponse.error(
            'Données invalides',
            statusCode: 422,
            validationErrors: errors is Map ? errors.cast<String, dynamic>() : null,
          );

        case 500:
        case 502:
        case 503:
          return ApiResponse.error(
            'Erreur serveur. Veuillez réessayer plus tard.',
            statusCode: response.statusCode,
          );

        default:
          return ApiResponse.error(
            decodedBody is Map && decodedBody.containsKey('error')
                ? decodedBody['error']
                : 'Erreur inconnue',
            statusCode: response.statusCode,
          );
      }
    } catch (e) {
      if (AppConfig.enableLogging) {
        debugPrint('Response parsing error: $e');
      }
      return ApiResponse.error('Erreur de traitement de la réponse');
    }
  }

  /// Close client
  void dispose() {
    _client.close();
  }
}

/// API Response wrapper
class ApiResponse<T> {
  final bool success;
  final T? data;
  final String? message;
  final String? error;
  final int? statusCode;
  final Map<String, dynamic>? validationErrors;

  ApiResponse._({
    required this.success,
    this.data,
    this.message,
    this.error,
    this.statusCode,
    this.validationErrors,
  });

  factory ApiResponse.success({
    T? data,
    String? message,
  }) {
    return ApiResponse._(
      success: true,
      data: data,
      message: message,
    );
  }

  factory ApiResponse.error(
    String error, {
    int? statusCode,
    Map<String, dynamic>? validationErrors,
  }) {
    return ApiResponse._(
      success: false,
      error: error,
      statusCode: statusCode,
      validationErrors: validationErrors,
    );
  }

  bool get isSuccess => success;
  bool get isError => !success;
  bool get isUnauthorized => statusCode == 401;
  bool get isValidationError => statusCode == 422;
}

/// Timeout Exception
class TimeoutException implements Exception {
  final String message;
  TimeoutException([this.message = 'Request timeout']);

  @override
  String toString() => message;
}
