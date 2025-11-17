import 'package:dio/dio.dart';

class ApiService {
  late final Dio _dio;
  final String baseUrl = 'https://api.servicehub.tn/api'; // À configurer

  ApiService() {
    _dio = Dio(
      BaseOptions(
        baseUrl: baseUrl,
        connectTimeout: const Duration(seconds: 30),
        receiveTimeout: const Duration(seconds: 30),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      ),
    );

    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) {
          // Add auth token here if available
          // options.headers['Authorization'] = 'Bearer $token';
          return handler.next(options);
        },
        onError: (error, handler) {
          // Handle errors globally
          return handler.next(error);
        },
      ),
    );
  }

  Dio get dio => _dio;

  // Auth endpoints
  Future<Response> login(String phone, String password) async {
    return await _dio.post('/auth/login', data: {
      'phone': phone,
      'password': password,
    });
  }

  Future<Response> register(Map<String, dynamic> data) async {
    return await _dio.post('/auth/register', data: data);
  }

  // Services endpoints
  Future<Response> getServices() async {
    return await _dio.get('/services');
  }

  Future<Response> getServiceCategories() async {
    return await _dio.get('/service-categories');
  }

  // Providers endpoints
  Future<Response> searchProviders({
    required int serviceId,
    required Map<String, dynamic> location,
    Map<String, dynamic>? filters,
  }) async {
    return await _dio.post('/providers/search', data: {
      'service_id': serviceId,
      'location': location,
      'filters': filters,
    });
  }

  Future<Response> getProviderDetails(int providerId) async {
    return await _dio.get('/providers/$providerId');
  }

  // Bookings endpoints
  Future<Response> createBooking(Map<String, dynamic> data) async {
    return await _dio.post('/bookings', data: data);
  }

  Future<Response> getMyBookings() async {
    return await _dio.get('/bookings');
  }

  Future<Response> getBookingDetails(String bookingId) async {
    return await _dio.get('/bookings/$bookingId');
  }

  Future<Response> cancelBooking(String bookingId, String reason) async {
    return await _dio.post('/bookings/$bookingId/cancel', data: {
      'reason': reason,
    });
  }

  // Reviews endpoints
  Future<Response> submitReview(String bookingId, Map<String, dynamic> data) async {
    return await _dio.post('/reviews', data: {
      'booking_id': bookingId,
      ...data,
    });
  }

  // Payments endpoints
  Future<Response> initiatePayment(String bookingId, String method) async {
    return await _dio.post('/payments/initiate', data: {
      'booking_id': bookingId,
      'payment_method': method,
    });
  }
}
