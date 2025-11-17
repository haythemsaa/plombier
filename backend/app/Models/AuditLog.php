<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'description',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
        'severity',
        'created_at',
    ];

    /**
     * Get the user that performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Severity levels
     */
    const SEVERITY_INFO = 'info';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_CRITICAL = 'critical';

    /**
     * Common action types
     */
    const ACTION_LOGIN = 'user.login';
    const ACTION_LOGOUT = 'user.logout';
    const ACTION_LOGIN_FAILED = 'user.login_failed';
    const ACTION_REGISTER = 'user.register';
    const ACTION_PASSWORD_CHANGE = 'user.password_change';
    const ACTION_PROFILE_UPDATE = 'user.profile_update';
    const ACTION_BOOKING_CREATED = 'booking.created';
    const ACTION_BOOKING_UPDATED = 'booking.updated';
    const ACTION_BOOKING_CANCELLED = 'booking.cancelled';
    const ACTION_BOOKING_COMPLETED = 'booking.completed';
    const ACTION_PAYMENT_INITIATED = 'payment.initiated';
    const ACTION_PAYMENT_COMPLETED = 'payment.completed';
    const ACTION_PAYMENT_FAILED = 'payment.failed';
    const ACTION_REVIEW_CREATED = 'review.created';
    const ACTION_ADMIN_ACCESS = 'admin.access';
    const ACTION_DATA_EXPORT = 'data.export';
    const ACTION_SETTINGS_CHANGE = 'settings.change';
}
