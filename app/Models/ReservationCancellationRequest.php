<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationCancellationRequest extends Model
{
    use HasFactory;

    public const STATUS_REQUESTED = 'requested';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';

    public const REFUND_NOT_REQUIRED = 'not_required';
    public const REFUND_PENDING = 'pending';
    public const REFUND_PROCESSED = 'processed';

    protected $fillable = [
        'reservation_id',
        'user_id',
        'booking_id',
        'merchant_id',
        'status',
        'reason',
        'merchant_note',
        'requested_at',
        'reviewed_at',
        'expires_at',
        'refund_required',
        'refund_status',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'expires_at' => 'datetime',
            'refund_required' => 'boolean',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(BackendUser::class, 'merchant_id');
    }
}
