<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmailNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user,
        public string $subject,
        public string $template,
        public array $data = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Mail::send($this->template, $this->data, function ($message) {
                $message->to($this->user->email, $this->user->name)
                    ->subject($this->subject);
            });

            \Log::info('Email sent successfully', [
                'user_id' => $this->user->id,
                'subject' => $this->subject,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send email', [
                'user_id' => $this->user->id,
                'subject' => $this->subject,
                'error' => $e->getMessage(),
            ]);

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        \Log::error('Email job failed after all retries', [
            'user_id' => $this->user->id,
            'subject' => $this->subject,
            'error' => $exception->getMessage(),
        ]);

        // Could send alert to admin, log to Sentry, etc.
        if (app()->bound('sentry')) {
            app('sentry')->captureException($exception);
        }
    }
}
