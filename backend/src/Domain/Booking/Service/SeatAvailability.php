<?php

declare(strict_types=1);

namespace Fleet\Domain\Booking\Service;

use Fleet\Domain\Booking\Booking;
use Fleet\Domain\Bus\ValueObject\Seat;
use Fleet\Domain\Trip\ValueObject\Segment;

final class SeatAvailability
{
    /**
     * @param  list<Seat>  $seats
     * @param  list<Booking>  $bookings
     * @return list<Seat>
     */
    public function availableSeats(Segment $requested, array $seats, array $bookings): array
    {
        return array_values(array_filter(
            $seats,
            fn (Seat $seat): bool => ! $this->isOccupied($seat, $requested, $bookings),
        ));
    }

    /**
     * @param  list<Booking>  $bookings
     */
    public function isOccupied(Seat $seat, Segment $requested, array $bookings): bool
    {
        foreach ($bookings as $booking) {
            if ($booking->occupiesSeat($seat->number) && $booking->occupies($requested)) {
                return true;
            }
        }

        return false;
    }
}
