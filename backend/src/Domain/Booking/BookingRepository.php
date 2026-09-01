<?php

declare(strict_types=1);

namespace Fleet\Domain\Booking;

interface BookingRepository
{
    /**
     * @return list<Booking>
     */
    public function forTrip(int $tripId): array;

    /**
     * @return list<Booking>
     */
    public function forTripAndSeat(int $tripId, int $seatNumber): array;

    public function save(Booking $booking): Booking;
}
