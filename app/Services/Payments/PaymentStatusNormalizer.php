<?php

namespace App\Services\Payments;

class PaymentStatusNormalizer
{
    public static function normalize(?string $status): ?string
    {
        if (!$status) {
            return null;
        }

        $upper = strtoupper($status);

        if (in_array($upper, ['PAYMENT_SUCCESS', 'SUCCESS', 'COMPLETED', 'CAPTURED', 'PAID', 'AUTHORIZED'], true)) {
            return 'succeeded';
        }

        if (in_array($upper, ['PAYMENT_FAILED', 'FAILED', 'EXPIRED'], true)) {
            return 'failed';
        }

        if (in_array($upper, ['CANCELLED', 'CANCELED'], true)) {
            return 'cancelled';
        }

        // Return null for unrecognized statuses (9.6) so callers skip processing
        // rather than downgrading a succeeded payment back to 'pending'.
        return null;
    }
}
