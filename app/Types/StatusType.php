<?php

namespace App\Types;

enum StatusType: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Cancelled => 'Cancelled',
        };
    }

    public static function options(): array
    {
        return [
            self::Pending->value => self::Pending->label(),
            self::Confirmed->value => self::Confirmed->label(),
            self::Cancelled->value => self::Cancelled->label(),
        ];
    }
}
