<?php

namespace App\Models;

use App\Types\CancellationRefundStatus;
use App\Types\CancellationRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationCancellationRequest extends Model
{
    use HasFactory;

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
            'status' => CancellationRequestStatus::class,
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'expires_at' => 'datetime',
            'refund_required' => 'boolean',
            'refund_status' => CancellationRefundStatus::class,
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
