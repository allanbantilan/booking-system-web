<?php

namespace App\Console\Commands;

use App\Services\Reservations\ReservationCancellationService;
use Illuminate\Console\Command;

class ExpireReservationCancellationRequests extends Command
{
    protected $signature = 'reservations:expire-cancellation-requests';

    protected $description = 'Expire pending reservation cancellation requests past their review cutoff.';

    public function handle(ReservationCancellationService $cancellations): int
    {
        $count = $cancellations->expireOverdueRequests();

        $this->info("Expired {$count} cancellation request(s).");

        return self::SUCCESS;
    }
}
