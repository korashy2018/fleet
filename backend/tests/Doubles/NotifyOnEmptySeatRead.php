<?php

declare(strict_types=1);

namespace Tests\Doubles;

use Fleet\Domain\Booking\Booking;
use Fleet\Domain\Booking\Port\BookingRepository;

final class NotifyOnEmptySeatRead implements BookingRepository
{
    private bool $notified = false;

    public function __construct(
        private BookingRepository $inner,
        private \Closure $afterEmptyRead,
    ) {
    }

    public function forTrip(int $tripId): array
    {
        return $this->inner->forTrip($tripId);
    }

    public function forTripAndSeat(int $tripId, int $seatNumber): array
    {
        $rows = $this->inner->forTripAndSeat($tripId, $seatNumber);

        if ($rows === [] && ! $this->notified) {
            $this->notified = true;
            ($this->afterEmptyRead)();
        }

        return $rows;
    }

    public function save(Booking $booking): Booking
    {
        return $this->inner->save($booking);
    }
}
