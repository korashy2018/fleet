<?php

declare(strict_types=1);

namespace Fleet\Domain\Booking;

use Fleet\Domain\Booking\ValueObject\Passenger;
use Fleet\Domain\Trip\ValueObject\Segment;

final readonly class Booking
{
    public function __construct(
        public int $id,
        public int $tripId,
        public int $seatNumber,
        public int $startStationId,
        public int $endStationId,
        public Segment $segment,
        public Passenger $passenger,
        public ?int $userId,
    ) {
    }

    public function occupies(Segment $requested): bool
    {
        return $this->segment->overlaps($requested);
    }

    public function occupiesSeat(int $seatNumber): bool
    {
        return $this->seatNumber === $seatNumber;
    }
}
