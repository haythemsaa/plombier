<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class ImageOptimizationService
{
    /**
     * Image size configurations
     */
    const SIZES = [
        'thumbnail' => ['width' => 150, 'height' => 150],
        'small' => ['width' => 300, 'height' => 300],
        'medium' => ['width' => 600, 'height' => 600],
        'large' => ['width' => 1200, 'height' => 1200],
    ];

    /**
     * Maximum file size in kilobytes
     */
    const MAX_FILE_SIZE = 5120; // 5MB

    /**
     * Allowed image mime types
     */
    const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];

    /**
     * Upload and optimize an image with multiple sizes.
     *
     * @param UploadedFile $file
     * @param string $path Directory path (e.g., 'avatars', 'services')
     * @param array $sizes Sizes to generate (default: all)
     * @return array Array with URLs for each size
     * @throws \Exception
     */
    public function uploadAndOptimize(
        UploadedFile $file,
        string $path,
        array $sizes = ['thumbnail', 'small', 'medium', 'large']
    ): array {
        // Validate file
        $this->validateImage($file);

        // Generate unique filename
        $filename = Str::random(40);
        $extension = $this->getBestExtension($file);

        $urls = [];

        foreach ($sizes as $sizeName) {
            if (!isset(self::SIZES[$sizeName])) {
                throw new \Exception("Invalid size: $sizeName");
            }

            $sizeConfig = self::SIZES[$sizeName];
            $sizeFilename = "{$filename}_{$sizeName}.{$extension}";
            $fullPath = "{$path}/{$sizeFilename}";

            // Process and optimize image
            $image = Image::make($file)
                ->resize($sizeConfig['width'], $sizeConfig['height'], function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize(); // Prevent upsizing
                })
                ->encode($extension, $this->getQuality($sizeName));

            // Store image
            Storage::disk(config('filesystems.default'))->put($fullPath, $image);

            // Get public URL
            $urls[$sizeName] = Storage::url($fullPath);
        }

        return $urls;
    }

    /**
     * Optimize an existing image.
     *
     * @param string $imagePath Path to the existing image
     * @param string $sizeName Size to optimize to
     * @return string URL of optimized image
     * @throws \Exception
     */
    public function optimizeExisting(string $imagePath, string $sizeName = 'medium'): string
    {
        if (!isset(self::SIZES[$sizeName])) {
            throw new \Exception("Invalid size: $sizeName");
        }

        $sizeConfig = self::SIZES[$sizeName];

        // Load and optimize image
        $image = Image::make(Storage::path($imagePath))
            ->resize($sizeConfig['width'], $sizeConfig['height'], function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })
            ->encode('jpg', $this->getQuality($sizeName));

        // Generate new filename
        $pathInfo = pathinfo($imagePath);
        $optimizedPath = "{$pathInfo['dirname']}/{$pathInfo['filename']}_{$sizeName}.jpg";

        // Store optimized image
        Storage::disk(config('filesystems.default'))->put($optimizedPath, $image);

        return Storage::url($optimizedPath);
    }

    /**
     * Delete an image and all its variants.
     *
     * @param string $imagePath Path to the image (any variant)
     * @return bool
     */
    public function deleteImage(string $imagePath): bool
    {
        $pathInfo = pathinfo($imagePath);
        $baseFilename = preg_replace('/_(' . implode('|', array_keys(self::SIZES)) . ')$/', '', $pathInfo['filename']);

        $deleted = true;

        foreach (array_keys(self::SIZES) as $sizeName) {
            $variantPath = "{$pathInfo['dirname']}/{$baseFilename}_{$sizeName}.{$pathInfo['extension']}";
            if (Storage::exists($variantPath)) {
                $deleted = Storage::delete($variantPath) && $deleted;
            }
        }

        return $deleted;
    }

    /**
     * Validate uploaded image.
     *
     * @param UploadedFile $file
     * @throws \Exception
     */
    protected function validateImage(UploadedFile $file): void
    {
        // Check mime type
        if (!in_array($file->getMimeType(), self::ALLOWED_MIMES)) {
            throw new \Exception('Invalid image type. Allowed types: JPEG, PNG, WebP');
        }

        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE * 1024) {
            throw new \Exception('Image size exceeds maximum allowed (' . self::MAX_FILE_SIZE . 'KB)');
        }

        // Check if file is actually an image
        if (!@getimagesize($file->getRealPath())) {
            throw new \Exception('File is not a valid image');
        }
    }

    /**
     * Get the best file extension for storage.
     *
     * @param UploadedFile $file
     * @return string
     */
    protected function getBestExtension(UploadedFile $file): string
    {
        // Prefer WebP for modern browsers, fallback to JPEG
        if (extension_loaded('gd') && function_exists('imagewebp')) {
            return 'webp';
        }

        return 'jpg';
    }

    /**
     * Get compression quality based on size.
     *
     * @param string $sizeName
     * @return int Quality percentage (0-100)
     */
    protected function getQuality(string $sizeName): int
    {
        return match ($sizeName) {
            'thumbnail' => 70,
            'small' => 75,
            'medium' => 80,
            'large' => 85,
            default => 80,
        };
    }

    /**
     * Convert image to WebP format.
     *
     * @param string $imagePath
     * @return string URL of WebP image
     */
    public function convertToWebP(string $imagePath): string
    {
        $image = Image::make(Storage::path($imagePath))
            ->encode('webp', 85);

        $pathInfo = pathinfo($imagePath);
        $webpPath = "{$pathInfo['dirname']}/{$pathInfo['filename']}.webp";

        Storage::disk(config('filesystems.default'))->put($webpPath, $image);

        return Storage::url($webpPath);
    }

    /**
     * Generate responsive image srcset.
     *
     * @param string $baseImagePath Path without size suffix
     * @param array $sizes Sizes to include
     * @return string HTML srcset attribute value
     */
    public function generateSrcSet(string $baseImagePath, array $sizes = ['small', 'medium', 'large']): string
    {
        $srcset = [];

        foreach ($sizes as $sizeName) {
            if (!isset(self::SIZES[$sizeName])) {
                continue;
            }

            $width = self::SIZES[$sizeName]['width'];
            $url = $this->getImageUrl($baseImagePath, $sizeName);
            $srcset[] = "{$url} {$width}w";
        }

        return implode(', ', $srcset);
    }

    /**
     * Get URL for a specific image size.
     *
     * @param string $baseImagePath
     * @param string $sizeName
     * @return string
     */
    protected function getImageUrl(string $baseImagePath, string $sizeName): string
    {
        $pathInfo = pathinfo($baseImagePath);
        return Storage::url("{$pathInfo['dirname']}/{$pathInfo['filename']}_{$sizeName}.{$pathInfo['extension']}");
    }
}
