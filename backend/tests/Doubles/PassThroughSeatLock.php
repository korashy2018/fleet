<?php

declare(strict_types=1);

namespace Tests\Doubles;

use Fleet\Domain\Booking\Port\SeatLock;

final class PassThroughSeatLock implements SeatLock
{
    public function acquire(int $tripId, int $seatNumber, callable $action): mixed
    {
        return $action();
    }
}
