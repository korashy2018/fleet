<?php

declare(strict_types=1);

namespace Fleet\Application\Booking;

final class LockUnavailable extends \RuntimeException
{
    public static function timeout(int $tripId, int $seatNumber): self
    {
        return new self(sprintf(
            'Could not acquire the booking lock for trip %d seat %d.',
            $tripId,
            $seatNumber,
        ));
    }

    public static function becauseRedisFailed(\Throwable $previous): self
    {
        return new self('Booking lock is unavailable.', 0, $previous);
    }
}
