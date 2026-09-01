<?php

declare(strict_types=1);

namespace Fleet\Application\Booking;

use Fleet\Domain\Booking\BookingRepository;
use Fleet\Domain\Booking\SeatAvailability;
use Fleet\Domain\Bus\BusRepository;
use Fleet\Domain\Bus\Seat;
use Fleet\Domain\Trip\TripNotFound;
use Fleet\Domain\Trip\TripRepository;

final class GetAvailableSeats
{
    public function __construct(
        private TripRepository $trips,
        private BusRepository $buses,
        private BookingRepository $bookings,
    ) {
    }

    /**
     * @return list<Seat>
     */
    public function handle(int $tripId, int $startStationId, int $endStationId): array
    {
        $trip = $this->trips->find($tripId);

        if ($trip === null) {
            throw TripNotFound::withId($tripId);
        }

        $bus = $this->buses->find($trip->busId);

        if ($bus === null) {
            throw TripNotFound::withId($tripId);
        }

        $segment = $trip->segmentBetween($startStationId, $endStationId);
        $existing = $this->bookings->forTrip($tripId);

        return (new SeatAvailability())->availableSeats($segment, $bus->seats, $existing);
    }
}
