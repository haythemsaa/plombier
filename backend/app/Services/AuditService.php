<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * Log an action to the audit trail.
     */
    public static function log(
        string $action,
        ?string $description = null,
        ?string $entityType = null,
        ?string $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
        string $severity = AuditLog::SEVERITY_INFO,
        ?User $user = null,
        ?Request $request = null
    ): AuditLog {
        $user = $user ?? Auth::user();
        $request = $request ?? request();

        return AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => $metadata,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'severity' => $severity,
            'created_at' => now(),
        ]);
    }

    /**
     * Log a successful login.
     */
    public static function logLogin(User $user, Request $request): void
    {
        self::log(
            AuditLog::ACTION_LOGIN,
            "User {$user->name} logged in successfully",
            'User',
            $user->id,
            metadata: [
                'user_type' => $user->type,
                'login_method' => 'credentials',
            ],
            severity: AuditLog::SEVERITY_INFO,
            user: $user,
            request: $request
        );
    }

    /**
     * Log a failed login attempt.
     */
    public static function logLoginFailed(string $phone, Request $request): void
    {
        self::log(
            AuditLog::ACTION_LOGIN_FAILED,
            "Failed login attempt for phone: {$phone}",
            metadata: [
                'phone' => $phone,
                'attempt_count' => self::getFailedLoginCount($request->ip()),
            ],
            severity: AuditLog::SEVERITY_WARNING,
            request: $request
        );
    }

    /**
     * Log user registration.
     */
    public static function logRegistration(User $user, Request $request): void
    {
        self::log(
            AuditLog::ACTION_REGISTER,
            "New user registered: {$user->name}",
            'User',
            $user->id,
            newValues: [
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'type' => $user->type,
            ],
            metadata: [
                'user_type' => $user->type,
            ],
            severity: AuditLog::SEVERITY_INFO,
            user: $user,
            request: $request
        );
    }

    /**
     * Log booking creation.
     */
    public static function logBookingCreated($booking, User $user, Request $request): void
    {
        self::log(
            AuditLog::ACTION_BOOKING_CREATED,
            "Booking created for service #{$booking->provider_service_id}",
            'Booking',
            $booking->id,
            newValues: [
                'total_price' => $booking->total_price,
                'scheduled_at' => $booking->scheduled_at,
                'status' => $booking->status,
            ],
            metadata: [
                'provider_service_id' => $booking->provider_service_id,
                'is_urgent' => $booking->is_urgent ?? false,
            ],
            severity: AuditLog::SEVERITY_INFO,
            user: $user,
            request: $request
        );
    }

    /**
     * Log booking cancellation.
     */
    public static function logBookingCancelled($booking, User $user, Request $request): void
    {
        self::log(
            AuditLog::ACTION_BOOKING_CANCELLED,
            "Booking #{$booking->id} cancelled",
            'Booking',
            $booking->id,
            oldValues: [
                'status' => 'confirmed',
            ],
            newValues: [
                'status' => 'cancelled',
            ],
            metadata: [
                'cancelled_by' => $user->type,
                'cancellation_reason' => $booking->cancellation_reason,
            ],
            severity: AuditLog::SEVERITY_WARNING,
            user: $user,
            request: $request
        );
    }

    /**
     * Log payment initiation.
     */
    public static function logPaymentInitiated($payment, User $user, Request $request): void
    {
        self::log(
            AuditLog::ACTION_PAYMENT_INITIATED,
            "Payment initiated for booking #{$payment->booking_id}",
            'Payment',
            $payment->id,
            newValues: [
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'gateway' => $payment->gateway,
            ],
            metadata: [
                'booking_id' => $payment->booking_id,
            ],
            severity: AuditLog::SEVERITY_INFO,
            user: $user,
            request: $request
        );
    }

    /**
     * Log admin access to sensitive endpoints.
     */
    public static function logAdminAccess(string $endpoint, User $user, Request $request): void
    {
        self::log(
            AuditLog::ACTION_ADMIN_ACCESS,
            "Admin accessed: {$endpoint}",
            metadata: [
                'endpoint' => $endpoint,
                'method' => $request->method(),
            ],
            severity: AuditLog::SEVERITY_WARNING,
            user: $user,
            request: $request
        );
    }

    /**
     * Log data export.
     */
    public static function logDataExport(string $dataType, User $user, Request $request): void
    {
        self::log(
            AuditLog::ACTION_DATA_EXPORT,
            "Data exported: {$dataType}",
            metadata: [
                'data_type' => $dataType,
                'format' => $request->input('format', 'csv'),
            ],
            severity: AuditLog::SEVERITY_WARNING,
            user: $user,
            request: $request
        );
    }

    /**
     * Get failed login count for IP in last hour.
     */
    protected static function getFailedLoginCount(string $ip): int
    {
        return AuditLog::where('action', AuditLog::ACTION_LOGIN_FAILED)
            ->where('ip_address', $ip)
            ->where('created_at', '>=', now()->subHour())
            ->count();
    }

    /**
     * Get recent audit logs for a user.
     */
    public static function getUserLogs(User $user, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return AuditLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit logs for an entity.
     */
    public static function getEntityLogs(string $entityType, string $entityId, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return AuditLog::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Clean up old audit logs (data retention).
     */
    public static function cleanup(int $days = 90): int
    {
        return AuditLog::where('created_at', '<', now()->subDays($days))
            ->where('severity', '!=', AuditLog::SEVERITY_CRITICAL)
            ->delete();
    }
}
