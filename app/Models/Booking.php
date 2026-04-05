<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Models\BackendUser;

class Booking extends Model implements HasMedia
{
    use HasFactory;
    use SoftDeletes;
    use InteractsWithMedia;

    public const TYPE_EVENT = 'event';
    public const TYPE_ACCOMMODATION = 'accommodation';
    public const TYPE_SERVICE = 'service';
    public const TYPE_RENTAL = 'rental';
    public const TYPE_PACKAGE = 'package';

    protected $appends = [
        'image_urls',
    ];

    protected $fillable = [
        'title',
        'description',
        'category_id',
        'location',
        'event_date',
        'capacity',
        'availability_label',
        'quantity_label',
        'meta_line',
        'amenities',
        'price',
        'extra_rate',
        'discount_percentage',
        'created_by',
        'booking_type',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'datetime',
            'price' => 'decimal:2',
            'extra_rate' => 'decimal:2',
            'discount_percentage' => 'integer',
            'amenities' => 'array',
        ];
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_EVENT => 'Event',
            self::TYPE_ACCOMMODATION => 'Accommodation',
            self::TYPE_SERVICE => 'Service',
            self::TYPE_RENTAL => 'Rental',
            self::TYPE_PACKAGE => 'Package',
        ];
    }

    public static function typeDefaults(string $type): array
    {
        return match ($type) {
            self::TYPE_ACCOMMODATION => [
                'quantity_label' => 'room(s)',
                'availability_label' => 'Rooms left',
                'nights_required' => true,
                'duration_label' => 'Nights',
            ],
            self::TYPE_SERVICE => [
                'quantity_label' => 'slot(s)',
                'availability_label' => 'Slots left',
                'nights_required' => false,
                'duration_label' => 'Duration',
            ],
            self::TYPE_RENTAL => [
                'quantity_label' => 'unit(s)',
                'availability_label' => 'Units left',
                'nights_required' => true,
                'duration_label' => 'Days',
            ],
            self::TYPE_PACKAGE => [
                'quantity_label' => 'package(s)',
                'availability_label' => 'Packages left',
                'nights_required' => false,
                'duration_label' => 'Duration',
            ],
            default => [
                'quantity_label' => 'ticket(s)',
                'availability_label' => 'Tickets left',
                'nights_required' => false,
                'duration_label' => 'Duration',
            ],
        };
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
    }

    public function getImageUrlsAttribute(): array
    {
        return $this->getMedia('images')
            ->map(fn ($media) => $media->getUrl())
            ->all();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(BackendUser::class, 'created_by');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
