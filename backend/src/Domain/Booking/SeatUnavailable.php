<?php

declare(strict_types=1);

namespace Fleet\Domain\Booking;

final class SeatUnavailable extends \RuntimeException
{
    public static function onTrip(int $tripId, int $seatNumber): self
    {
        return new self(sprintf(
            'Seat %d is not available on trip %d for that segment.',
            $seatNumber,
            $tripId,
        ));
    }
}
