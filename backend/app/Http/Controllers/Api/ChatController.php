<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Booking;
use App\Events\MessageSent;
use App\Jobs\SendPushNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Chat",
 *     description="Real-time chat between clients and providers"
 * )
 */
class ChatController extends Controller
{
    /**
     * Get or create conversation for a booking.
     */
    public function getOrCreateConversation(Request $request, string $bookingId): JsonResponse
    {
        $booking = Booking::findOrFail($bookingId);
        $user = $request->user();

        // Verify user is part of this booking
        if ($booking->client_id !== $user->id && $booking->provider_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Get or create conversation
        $conversation = Conversation::firstOrCreate(
            ['booking_id' => $bookingId],
            [
                'client_id' => $booking->client_id,
                'provider_id' => $booking->provider_id,
                'is_active' => true,
            ]
        );

        // Load relationships
        $conversation->load(['client', 'provider', 'lastMessage']);

        return response()->json([
            'success' => true,
            'conversation' => $conversation,
        ]);
    }

    /**
     * Get user's conversations.
     */
    public function getConversations(Request $request): JsonResponse
    {
        $user = $request->user();
        $userType = $user->type;

        $query = Conversation::with(['client', 'provider', 'booking', 'lastMessage'])
            ->where('is_active', true)
            ->orderBy('last_message_at', 'desc');

        if ($userType === 'client') {
            $query->where('client_id', $user->id);
        } else {
            $query->where('provider_id', $user->id);
        }

        $conversations = $query->paginate(20);

        // Add unread count for each conversation
        $conversations->each(function ($conversation) use ($userType) {
            $conversation->unread_count = $conversation->getUnreadCountForUser($userType);
        });

        return response()->json($conversations);
    }

    /**
     * Get messages for a conversation.
     */
    public function getMessages(Request $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);
        $user = $request->user();

        // Verify user is part of this conversation
        if ($conversation->client_id !== $user->id && $conversation->provider_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $messages = $conversation->messages()
            ->with('sender')
            ->paginate(50);

        // Mark messages as read
        $userType = $user->type;
        $conversation->markAsRead($user->id, $userType);

        return response()->json([
            'success' => true,
            'messages' => $messages,
            'conversation' => $conversation,
        ]);
    }

    /**
     * Send a message.
     */
    public function sendMessage(Request $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);
        $user = $request->user();

        // Verify user is part of this conversation
        if ($conversation->client_id !== $user->id && $conversation->provider_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:text,image,location',
            'content' => 'required|string',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $senderType = $user->type;

        // Create message
        $message = $conversation->messages()->create([
            'sender_id' => $user->id,
            'sender_type' => $senderType,
            'type' => $request->type,
            'content' => $request->content,
            'metadata' => $request->metadata,
            'delivered_at' => now(),
        ]);

        // Update conversation
        $conversation->update([
            'last_message_at' => now(),
            'last_message_by' => $user->id,
        ]);

        // Increment unread count for recipient
        $conversation->incrementUnreadCount($senderType);

        // Load sender relationship
        $message->load('sender');

        // Broadcast message via WebSocket
        broadcast(new MessageSent($message, $conversation))->toOthers();

        // Send push notification to recipient
        $recipient = $conversation->getOtherParticipant($user->id);
        if ($recipient->fcm_token) {
            SendPushNotification::dispatch(
                $recipient,
                'New message from ' . $user->name,
                $message->type === 'text' ? $message->content : 'Sent a ' . $message->type,
                [
                    'type' => 'new_message',
                    'conversation_id' => $conversation->id,
                    'message_id' => $message->id,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => $message,
        ], 201);
    }

    /**
     * Mark messages as read.
     */
    public function markAsRead(Request $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);
        $user = $request->user();

        // Verify user is part of this conversation
        if ($conversation->client_id !== $user->id && $conversation->provider_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $conversation->markAsRead($user->id, $user->type);

        return response()->json([
            'success' => true,
            'message' => 'Messages marked as read',
        ]);
    }

    /**
     * Get total unread messages count.
     */
    public function getUnreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        $userType = $user->type;

        $column = $userType === 'client' ? 'client_unread_count' : 'provider_unread_count';

        $totalUnread = Conversation::where('is_active', true)
            ->where($userType === 'client' ? 'client_id' : 'provider_id', $user->id)
            ->sum($column);

        return response()->json([
            'success' => true,
            'unread_count' => $totalUnread,
        ]);
    }

    /**
     * Typing indicator.
     */
    public function typing(Request $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);
        $user = $request->user();

        // Verify user is part of this conversation
        if ($conversation->client_id !== $user->id && $conversation->provider_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Broadcast typing event
        broadcast(new \App\Events\UserTyping($conversation, $user))->toOthers();

        return response()->json(['success' => true]);
    }
}
