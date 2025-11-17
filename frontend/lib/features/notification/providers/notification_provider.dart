import 'package:flutter/foundation.dart';
import '../../../core/services/api_service.dart';

class NotificationProvider with ChangeNotifier {
  final ApiService _apiService;

  NotificationProvider(this._apiService);

  List<dynamic> _notifications = [];
  int _unreadCount = 0;
  bool _isLoading = false;
  String? _error;

  List<dynamic> get notifications => _notifications;
  int get unreadCount => _unreadCount;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> fetchNotifications() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.get('/notifications');

      if (response['success']) {
        _notifications = response['data'] ?? [];
        _updateUnreadCount();
      } else {
        _error = response['message'] ?? 'Failed to fetch notifications';
      }
    } catch (e) {
      _error = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> getUnreadCount() async {
    try {
      final response = await _apiService.get('/notifications/unread-count');

      if (response['success']) {
        _unreadCount = response['count'] ?? 0;
        notifyListeners();
      }
    } catch (e) {
      // Silent fail for badge count
    }
  }

  Future<bool> markAsRead(String notificationId) async {
    try {
      final response = await _apiService.put(
        '/notifications/$notificationId/mark-read',
        {},
      );

      if (response['success']) {
        // Update local state
        final index = _notifications.indexWhere((n) => n['id'] == notificationId);
        if (index != -1) {
          _notifications[index]['read_at'] = DateTime.now().toIso8601String();
          _updateUnreadCount();
          notifyListeners();
        }
        return true;
      }
      return false;
    } catch (e) {
      return false;
    }
  }

  Future<bool> markAllAsRead() async {
    _isLoading = true;
    notifyListeners();

    try {
      final response = await _apiService.post('/notifications/mark-all-read', {});

      if (response['success']) {
        // Update all notifications to read
        for (var notification in _notifications) {
          notification['read_at'] = DateTime.now().toIso8601String();
        }
        _unreadCount = 0;
        notifyListeners();
        return true;
      }
      return false;
    } catch (e) {
      return false;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  void _updateUnreadCount() {
    _unreadCount = _notifications.where((n) => n['read_at'] == null).length;
  }
}
