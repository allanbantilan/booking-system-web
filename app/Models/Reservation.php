<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Types\StatusType;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'booking_id',
        'quantity',
        'nights',
        'total_price',
        'status',
        'check_in_date',
        'check_out_date',
    ];

    protected function casts(): array
    {
        return [
            'total_price' => 'decimal:2',
            'status' => StatusType::class,
            'nights' => 'integer',
            'check_in_date' => 'date',
            'check_out_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class);
    }
}
