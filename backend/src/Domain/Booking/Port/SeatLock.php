<?php

declare(strict_types=1);

namespace Fleet\Domain\Booking\Port;

interface SeatLock
{
    /**
     * Hold the lock for (trip, seat) while $action runs, then release it.
     *
     * @template T
     *
     * @param  callable(): T  $action
     * @return T
     */
    public function acquire(int $tripId, int $seatNumber, callable $action): mixed;
}
