<?php

namespace App\Types;

enum CancellationRequestStatus: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Requested',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Expired => 'Expired',
        };
    }

    public static function options(): array
    {
        return [
            self::Requested->value => self::Requested->label(),
            self::Approved->value => self::Approved->label(),
            self::Rejected->value => self::Rejected->label(),
            self::Expired->value => self::Expired->label(),
        ];
    }
}
