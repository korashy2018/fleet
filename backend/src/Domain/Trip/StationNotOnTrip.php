<?php

declare(strict_types=1);

namespace Fleet\Domain\Trip;

final class StationNotOnTrip extends \RuntimeException
{
    public static function withId(int $stationId): self
    {
        return new self(sprintf('Station %d is not on this trip.', $stationId));
    }
}
