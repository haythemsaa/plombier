import 'package:flutter/foundation.dart';
import 'package:socket_io_client/socket_io_client.dart' as IO;
import '../services/api_service.dart';
import '../services/storage_service.dart';

/// Real-time chat service using Socket.IO
class ChatService with ChangeNotifier {
  final ApiService _apiService;
  final StorageService _storageService;

  IO.Socket? _socket;
  bool _isConnected = false;
  Map<int, List<Message>> _conversationMessages = {};
  Map<int, bool> _typingIndicators = {};

  ChatService(this._apiService, this._storageService);

  bool get isConnected => _isConnected;

  /// Initialize WebSocket connection
  Future<void> connect() async {
    try {
      final token = await _storageService.getToken();
      if (token == null) {
        debugPrint('No auth token found, cannot connect to chat');
        return;
      }

      const socketUrl = String.fromEnvironment(
        'SOCKET_URL',
        defaultValue: 'http://localhost:6001',
      );

      _socket = IO.io(socketUrl, <String, dynamic>{
        'transports': ['websocket'],
        'auth': {'token': token},
        'autoConnect': true,
      });

      _socket?.onConnect((_) {
        debugPrint('Connected to chat server');
        _isConnected = true;
        notifyListeners();
      });

      _socket?.onDisconnect((_) {
        debugPrint('Disconnected from chat server');
        _isConnected = false;
        notifyListeners();
      });

      _socket?.onConnectError((error) {
        debugPrint('Connection error: $error');
        _isConnected = false;
        notifyListeners();
      });

      _socket?.connect();
    } catch (e) {
      debugPrint('Failed to connect to chat: $e');
    }
  }

  /// Disconnect from WebSocket
  void disconnect() {
    _socket?.disconnect();
    _socket?.dispose();
    _socket = null;
    _isConnected = false;
    notifyListeners();
  }

  /// Join a conversation channel
  void joinConversation(int conversationId) {
    if (!_isConnected) {
      debugPrint('Not connected to chat server');
      return;
    }

    _socket?.emit('join', {'conversation_id': conversationId});

    // Listen for new messages
    _socket?.on('conversation.$conversationId:message.sent', (data) {
      _handleNewMessage(conversationId, data);
    });

    // Listen for typing indicator
    _socket?.on('conversation.$conversationId:user.typing', (data) {
      _handleTypingIndicator(conversationId, data);
    });

    debugPrint('Joined conversation: $conversationId');
  }

  /// Leave a conversation channel
  void leaveConversation(int conversationId) {
    _socket?.emit('leave', {'conversation_id': conversationId});
    _socket?.off('conversation.$conversationId:message.sent');
    _socket?.off('conversation.$conversationId:user.typing');
    debugPrint('Left conversation: $conversationId');
  }

  /// Handle incoming message
  void _handleNewMessage(int conversationId, dynamic data) {
    try {
      final message = Message.fromJson(data['message']);

      if (!_conversationMessages.containsKey(conversationId)) {
        _conversationMessages[conversationId] = [];
      }

      _conversationMessages[conversationId]!.add(message);
      notifyListeners();

      debugPrint('New message received in conversation $conversationId');
    } catch (e) {
      debugPrint('Error handling new message: $e');
    }
  }

  /// Handle typing indicator
  void _handleTypingIndicator(int conversationId, dynamic data) {
    _typingIndicators[conversationId] = true;
    notifyListeners();

    // Clear typing indicator after 3 seconds
    Future.delayed(const Duration(seconds: 3), () {
      _typingIndicators[conversationId] = false;
      notifyListeners();
    });
  }

  /// Send typing indicator
  void sendTypingIndicator(int conversationId) {
    _apiService.post('/chat/$conversationId/typing', {});
  }

  /// Get or create conversation for a booking
  Future<Conversation?> getOrCreateConversation(String bookingId) async {
    try {
      final response = await _apiService.get('/chat/booking/$bookingId');

      if (response['success']) {
        return Conversation.fromJson(response['conversation']);
      }
      return null;
    } catch (e) {
      debugPrint('Error getting conversation: $e');
      return null;
    }
  }

  /// Get user's conversations
  Future<List<Conversation>> getConversations({int page = 1}) async {
    try {
      final response = await _apiService.get('/chat/conversations?page=$page');

      final conversations = (response['data'] as List)
          .map((json) => Conversation.fromJson(json))
          .toList();

      return conversations;
    } catch (e) {
      debugPrint('Error getting conversations: $e');
      return [];
    }
  }

  /// Get messages for a conversation
  Future<List<Message>> getMessages(int conversationId, {int page = 1}) async {
    try {
      final response = await _apiService.get('/chat/$conversationId/messages?page=$page');

      final messages = (response['messages']['data'] as List)
          .map((json) => Message.fromJson(json))
          .toList();

      _conversationMessages[conversationId] = messages;
      notifyListeners();

      return messages;
    } catch (e) {
      debugPrint('Error getting messages: $e');
      return [];
    }
  }

  /// Send a message
  Future<Message?> sendMessage(
    int conversationId, {
    required String content,
    String type = 'text',
    Map<String, dynamic>? metadata,
  }) async {
    try {
      final response = await _apiService.post('/chat/$conversationId/messages', {
        'type': type,
        'content': content,
        'metadata': metadata,
      });

      if (response['success']) {
        final message = Message.fromJson(response['message']);

        if (!_conversationMessages.containsKey(conversationId)) {
          _conversationMessages[conversationId] = [];
        }
        _conversationMessages[conversationId]!.add(message);
        notifyListeners();

        return message;
      }
      return null;
    } catch (e) {
      debugPrint('Error sending message: $e');
      return null;
    }
  }

  /// Mark messages as read
  Future<void> markAsRead(int conversationId) async {
    try {
      await _apiService.post('/chat/$conversationId/read', {});
    } catch (e) {
      debugPrint('Error marking messages as read: $e');
    }
  }

  /// Get total unread count
  Future<int> getUnreadCount() async {
    try {
      final response = await _apiService.get('/chat/unread-count');
      return response['unread_count'] ?? 0;
    } catch (e) {
      debugPrint('Error getting unread count: $e');
      return 0;
    }
  }

  /// Get messages for a conversation from cache
  List<Message> getCachedMessages(int conversationId) {
    return _conversationMessages[conversationId] ?? [];
  }

  /// Check if someone is typing
  bool isTyping(int conversationId) {
    return _typingIndicators[conversationId] ?? false;
  }
}

/// Conversation model
class Conversation {
  final int id;
  final String bookingId;
  final User client;
  final User provider;
  final DateTime? lastMessageAt;
  final int unreadCount;
  final Message? lastMessage;

  Conversation({
    required this.id,
    required this.bookingId,
    required this.client,
    required this.provider,
    this.lastMessageAt,
    this.unreadCount = 0,
    this.lastMessage,
  });

  factory Conversation.fromJson(Map<String, dynamic> json) {
    return Conversation(
      id: json['id'],
      bookingId: json['booking_id'],
      client: User.fromJson(json['client']),
      provider: User.fromJson(json['provider']),
      lastMessageAt: json['last_message_at'] != null
          ? DateTime.parse(json['last_message_at'])
          : null,
      unreadCount: json['unread_count'] ?? 0,
      lastMessage: json['last_message'] != null
          ? Message.fromJson(json['last_message'])
          : null,
    );
  }
}

/// Message model
class Message {
  final int id;
  final int conversationId;
  final String senderId;
  final String senderType;
  final String type;
  final String content;
  final Map<String, dynamic>? metadata;
  final DateTime? readAt;
  final DateTime createdAt;
  final User? sender;

  Message({
    required this.id,
    required this.conversationId,
    required this.senderId,
    required this.senderType,
    required this.type,
    required this.content,
    this.metadata,
    this.readAt,
    required this.createdAt,
    this.sender,
  });

  bool get isRead => readAt != null;

  factory Message.fromJson(Map<String, dynamic> json) {
    return Message(
      id: json['id'],
      conversationId: json['conversation_id'],
      senderId: json['sender_id'],
      senderType: json['sender_type'],
      type: json['type'],
      content: json['content'],
      metadata: json['metadata'],
      readAt: json['read_at'] != null ? DateTime.parse(json['read_at']) : null,
      createdAt: DateTime.parse(json['created_at']),
      sender: json['sender'] != null ? User.fromJson(json['sender']) : null,
    );
  }
}

/// User model (simplified for chat)
class User {
  final String id;
  final String name;
  final String? avatar;

  User({required this.id, required this.name, this.avatar});

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      name: json['name'],
      avatar: json['avatar'],
    );
  }
}
