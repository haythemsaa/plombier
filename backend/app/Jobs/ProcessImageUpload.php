<?php

namespace App\Jobs;

use App\Services\ImageOptimizationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessImageUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 300; // 5 minutes for image processing

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $imagePath,
        public string $storagePath,
        public array $sizes = ['thumbnail', 'small', 'medium', 'large']
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ImageOptimizationService $imageService): void
    {
        try {
            \Log::info('Processing image upload', [
                'path' => $this->imagePath,
                'sizes' => $this->sizes,
            ]);

            // Process each size
            $urls = [];
            foreach ($this->sizes as $size) {
                $url = $imageService->optimizeExisting($this->imagePath, $size);
                $urls[$size] = $url;
            }

            \Log::info('Image processing completed', [
                'path' => $this->imagePath,
                'urls' => $urls,
            ]);

            // Delete original if specified
            if (config('filesystems.delete_original_after_optimization', false)) {
                Storage::delete($this->imagePath);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to process image', [
                'path' => $this->imagePath,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        \Log::error('Image processing job failed', [
            'path' => $this->imagePath,
            'error' => $exception->getMessage(),
        ]);
    }
}
