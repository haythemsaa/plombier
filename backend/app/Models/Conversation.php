<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'booking_id',
        'client_id',
        'provider_id',
        'last_message_at',
        'last_message_by',
        'client_unread_count',
        'provider_unread_count',
        'is_active',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the booking for this conversation.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the client user.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Get the provider user.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    /**
     * Get all messages for this conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get the last message.
     */
    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latest();
    }

    /**
     * Mark messages as read for a user.
     */
    public function markAsRead(string $userId, string $userType): void
    {
        // Mark all unread messages as read
        $this->messages()
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        // Reset unread count
        if ($userType === 'client') {
            $this->update(['client_unread_count' => 0]);
        } else {
            $this->update(['provider_unread_count' => 0]);
        }
    }

    /**
     * Increment unread count for the recipient.
     */
    public function incrementUnreadCount(string $senderType): void
    {
        if ($senderType === 'client') {
            $this->increment('provider_unread_count');
        } else {
            $this->increment('client_unread_count');
        }
    }

    /**
     * Get unread count for a user.
     */
    public function getUnreadCountForUser(string $userType): int
    {
        return $userType === 'client'
            ? $this->client_unread_count
            : $this->provider_unread_count;
    }

    /**
     * Get the other participant in the conversation.
     */
    public function getOtherParticipant(string $userId)
    {
        return $this->client_id === $userId
            ? $this->provider
            : $this->client;
    }
}
