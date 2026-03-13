<?php

namespace App\Types;

enum StatusType: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
        };
    }

    public static function options(): array
    {
        return [
            self::Pending->value => self::Pending->label(),
            self::Confirmed->value => self::Confirmed->label(),
        ];
    }
}
