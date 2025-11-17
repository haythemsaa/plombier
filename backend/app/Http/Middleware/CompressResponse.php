<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompressResponse
{
    /**
     * Minimum size in bytes to compress (1KB)
     */
    const MIN_SIZE = 1024;

    /**
     * Content types that should be compressed
     */
    const COMPRESSIBLE_TYPES = [
        'application/json',
        'application/xml',
        'text/html',
        'text/plain',
        'text/css',
        'text/javascript',
        'application/javascript',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Check if compression is supported by client
        if (!$this->shouldCompress($request, $response)) {
            return $response;
        }

        return $this->compress($response);
    }

    /**
     * Determine if response should be compressed.
     */
    protected function shouldCompress(Request $request, Response $response): bool
    {
        // Check if client accepts gzip
        $acceptEncoding = $request->header('Accept-Encoding', '');
        if (stripos($acceptEncoding, 'gzip') === false) {
            return false;
        }

        // Don't compress if already compressed
        if ($response->headers->has('Content-Encoding')) {
            return false;
        }

        // Check content type
        $contentType = $response->headers->get('Content-Type', '');
        $isCompressible = false;
        foreach (self::COMPRESSIBLE_TYPES as $type) {
            if (stripos($contentType, $type) !== false) {
                $isCompressible = true;
                break;
            }
        }

        if (!$isCompressible) {
            return false;
        }

        // Check minimum size
        $content = $response->getContent();
        if (strlen($content) < self::MIN_SIZE) {
            return false;
        }

        return true;
    }

    /**
     * Compress the response content.
     */
    protected function compress(Response $response): Response
    {
        $content = $response->getContent();
        $originalSize = strlen($content);

        // Compress with gzip
        $compressed = gzencode($content, 6); // Level 6 is a good balance

        if ($compressed === false) {
            return $response;
        }

        $compressedSize = strlen($compressed);

        // Only use compression if it actually reduces size
        if ($compressedSize >= $originalSize) {
            return $response;
        }

        // Update response
        $response->setContent($compressed);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Content-Length', $compressedSize);
        $response->headers->set('X-Original-Size', $originalSize);
        $response->headers->set('X-Compressed-Size', $compressedSize);
        $response->headers->set('X-Compression-Ratio', round(($originalSize - $compressedSize) / $originalSize * 100, 2) . '%');

        return $response;
    }
}
