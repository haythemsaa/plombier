<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiVersion
{
    /**
     * Supported API versions
     */
    const SUPPORTED_VERSIONS = ['v1', 'v2'];
    const DEFAULT_VERSION = 'v1';
    const LATEST_VERSION = 'v1';

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $version = null): Response
    {
        // Determine API version from:
        // 1. Route parameter (highest priority)
        // 2. Accept header
        // 3. Query parameter
        // 4. Default version

        $apiVersion = $version
            ?? $this->getVersionFromHeader($request)
            ?? $request->query('version')
            ?? self::DEFAULT_VERSION;

        // Validate version
        if (!in_array($apiVersion, self::SUPPORTED_VERSIONS)) {
            return response()->json([
                'success' => false,
                'message' => "Unsupported API version: {$apiVersion}",
                'supported_versions' => self::SUPPORTED_VERSIONS,
                'latest_version' => self::LATEST_VERSION,
            ], 400);
        }

        // Store version in request for controllers to access
        $request->attributes->set('api_version', $apiVersion);

        $response = $next($request);

        // Add version headers to response
        return $this->addVersionHeaders($response, $apiVersion);
    }

    /**
     * Extract version from Accept header.
     * Format: Accept: application/vnd.servicehub.v1+json
     */
    protected function getVersionFromHeader(Request $request): ?string
    {
        $accept = $request->header('Accept', '');

        if (preg_match('/application\/vnd\.servicehub\.(v\d+)\+json/', $accept, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Add API version headers to response.
     */
    protected function addVersionHeaders(Response $response, string $version): Response
    {
        $response->headers->set('X-API-Version', $version);
        $response->headers->set('X-API-Latest-Version', self::LATEST_VERSION);
        $response->headers->set('X-API-Supported-Versions', implode(', ', self::SUPPORTED_VERSIONS));

        // Add deprecation warning if using old version
        if ($version !== self::LATEST_VERSION) {
            $response->headers->set(
                'X-API-Deprecation-Warning',
                "You are using API version {$version}. Latest version is " . self::LATEST_VERSION
            );
        }

        return $response;
    }

    /**
     * Get current API version from request.
     */
    public static function getCurrentVersion(Request $request): string
    {
        return $request->attributes->get('api_version', self::DEFAULT_VERSION);
    }

    /**
     * Check if current version matches specified version.
     */
    public static function is(Request $request, string $version): bool
    {
        return self::getCurrentVersion($request) === $version;
    }

    /**
     * Check if current version is at least the specified version.
     */
    public static function isAtLeast(Request $request, string $version): bool
    {
        $current = (int) str_replace('v', '', self::getCurrentVersion($request));
        $required = (int) str_replace('v', '', $version);

        return $current >= $required;
    }
}
