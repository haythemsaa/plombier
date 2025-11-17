<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IPWhitelist
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get whitelist from config
        $whitelist = config('security.ip_whitelist', []);

        // If whitelist is empty, allow all
        if (empty($whitelist)) {
            return $next($request);
        }

        $clientIp = $request->ip();

        // Check if IP is whitelisted
        if (!in_array($clientIp, $whitelist)) {
            // Log unauthorized access attempt
            \Log::warning('Unauthorized IP access attempt', [
                'ip' => $clientIp,
                'path' => $request->path(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Access denied. Your IP is not whitelisted.',
            ], 403);
        }

        return $next($request);
    }
}
