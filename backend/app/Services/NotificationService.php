<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    /**
     * Create a notification
     */
    public function create($userId, $type, $title, $body, $data = [])
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'created_at' => now(),
        ]);
    }

    /**
     * Send booking notification
     */
    public function sendBookingNotification($booking, $type)
    {
        $notifications = [];

        switch ($type) {
            case 'booking_created':
                // Notify provider
                $notifications[] = $this->create(
                    $booking->provider_id,
                    'booking_request',
                    'Nouvelle demande de réservation',
                    "Vous avez une nouvelle demande pour {$booking->service->name_fr}",
                    ['booking_id' => $booking->id]
                );
                break;

            case 'booking_confirmed':
                // Notify client
                $notifications[] = $this->create(
                    $booking->client_id,
                    'booking_confirmed',
                    'Réservation confirmée',
                    "Votre réservation #{$booking->booking_number} a été confirmée",
                    ['booking_id' => $booking->id]
                );
                break;

            case 'booking_started':
                // Notify client
                $notifications[] = $this->create(
                    $booking->client_id,
                    'booking_started',
                    'Service démarré',
                    "Le prestataire a commencé votre service",
                    ['booking_id' => $booking->id]
                );
                break;

            case 'booking_completed':
                // Notify both
                $notifications[] = $this->create(
                    $booking->client_id,
                    'booking_completed',
                    'Service terminé',
                    "Votre service est terminé. N'oubliez pas de laisser un avis!",
                    ['booking_id' => $booking->id]
                );
                $notifications[] = $this->create(
                    $booking->provider_id,
                    'booking_completed',
                    'Service terminé',
                    "Service #{$booking->booking_number} terminé avec succès",
                    ['booking_id' => $booking->id]
                );
                break;

            case 'booking_cancelled':
                $cancelledBy = $booking->cancelled_by;
                $targetUserId = $cancelledBy === 'client'
                    ? $booking->provider_id
                    : $booking->client_id;

                $notifications[] = $this->create(
                    $targetUserId,
                    'booking_cancelled',
                    'Réservation annulée',
                    "La réservation #{$booking->booking_number} a été annulée",
                    ['booking_id' => $booking->id, 'reason' => $booking->cancellation_reason]
                );
                break;
        }

        return $notifications;
    }

    /**
     * Send review notification
     */
    public function sendReviewNotification($review)
    {
        return $this->create(
            $review->provider_id,
            'new_review',
            'Nouvel avis reçu',
            "Vous avez reçu un nouvel avis de {$review->client->full_name}",
            ['review_id' => $review->id, 'rating' => $review->rating]
        );
    }

    /**
     * Send payment notification
     */
    public function sendPaymentNotification($payment, $type)
    {
        $notifications = [];

        switch ($type) {
            case 'payment_completed':
                $notifications[] = $this->create(
                    $payment->booking->client_id,
                    'payment_confirmed',
                    'Paiement confirmé',
                    "Votre paiement de {$payment->amount} TND a été confirmé",
                    ['payment_id' => $payment->id]
                );
                break;

            case 'payment_failed':
                $notifications[] = $this->create(
                    $payment->booking->client_id,
                    'payment_failed',
                    'Échec du paiement',
                    "Le paiement a échoué. Veuillez réessayer.",
                    ['payment_id' => $payment->id]
                );
                break;
        }

        return $notifications;
    }

    /**
     * Get user notifications
     */
    public function getUserNotifications($userId, $unreadOnly = false)
    {
        $query = Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        if ($unreadOnly) {
            $query->unread();
        }

        return $query->get();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId, $userId)
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $notification->markAsRead();

        return $notification;
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead($userId)
    {
        return Notification::where('user_id', $userId)
            ->unread()
            ->update(['read_at' => now()]);
    }

    /**
     * Get unread count
     */
    public function getUnreadCount($userId)
    {
        return Notification::where('user_id', $userId)
            ->unread()
            ->count();
    }
}
