<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantRequest extends Model
{
    protected $fillable = [
        'user_id',
        'message',
        'status',
        'backend_user_id',
        'handled_by',
        'handled_at',
        'decision_note',
    ];

    protected function casts(): array
    {
        return [
            'handled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function backendUser(): BelongsTo
    {
        return $this->belongsTo(BackendUser::class, 'backend_user_id');
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(BackendUser::class, 'handled_by');
    }
}

