<?php

declare(strict_types=1);

namespace Tests\Doubles;

use Fleet\Application\Booking\LockUnavailable;
use Fleet\Domain\Booking\Port\SeatLock;

final class ExclusiveSeatLock implements SeatLock
{
    /** @var array<string, true> */
    private array $held = [];

    public function acquire(int $tripId, int $seatNumber, callable $action): mixed
    {
        $key = $tripId.':'.$seatNumber;

        if (isset($this->held[$key])) {
            throw LockUnavailable::timeout($tripId, $seatNumber);
        }

        $this->held[$key] = true;

        try {
            return $action();
        } finally {
            unset($this->held[$key]);
        }
    }
}
