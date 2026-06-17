<?php

namespace App\Types;

enum CancellationRefundStatus: string
{
    case NotRequired = 'not_required';
    case Pending = 'pending';
    case Processed = 'processed';

    public function label(): string
    {
        return match ($this) {
            self::NotRequired => 'Not Required',
            self::Pending => 'Pending',
            self::Processed => 'Processed',
        };
    }

    public static function options(): array
    {
        return [
            self::NotRequired->value => self::NotRequired->label(),
            self::Pending->value => self::Pending->label(),
            self::Processed->value => self::Processed->label(),
        ];
    }
}
