<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user,
        public string $title,
        public string $body,
        public array $data = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Check if user has FCM token
        if (empty($this->user->fcm_token)) {
            \Log::info('User has no FCM token, skipping push notification', [
                'user_id' => $this->user->id,
            ]);
            return;
        }

        try {
            $messaging = app('firebase.messaging');

            $notification = Notification::create($this->title, $this->body);

            $message = CloudMessage::withTarget('token', $this->user->fcm_token)
                ->withNotification($notification)
                ->withData($this->data);

            $messaging->send($message);

            \Log::info('Push notification sent successfully', [
                'user_id' => $this->user->id,
                'title' => $this->title,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send push notification', [
                'user_id' => $this->user->id,
                'title' => $this->title,
                'error' => $e->getMessage(),
            ]);

            // If token is invalid, clear it
            if (str_contains($e->getMessage(), 'invalid-registration-token')) {
                $this->user->update(['fcm_token' => null]);
                \Log::info('Cleared invalid FCM token', ['user_id' => $this->user->id]);
                return; // Don't retry
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        \Log::error('Push notification job failed', [
            'user_id' => $this->user->id,
            'title' => $this->title,
            'error' => $exception->getMessage(),
        ]);
    }
}
