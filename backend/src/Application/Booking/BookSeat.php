<?php

declare(strict_types=1);

namespace Fleet\Application\Booking;

use Fleet\Application\TransactionBoundary;
use Fleet\Domain\Booking\Booking;
use Fleet\Domain\Booking\BookingRepository;
use Fleet\Domain\Booking\Passenger;
use Fleet\Domain\Booking\SeatAvailability;
use Fleet\Domain\Booking\SeatLock;
use Fleet\Domain\Booking\SeatUnavailable;
use Fleet\Domain\Bus\BusRepository;
use Fleet\Domain\Bus\InvalidSeat;
use Fleet\Domain\Trip\TripNotFound;
use Fleet\Domain\Trip\TripRepository;

final class BookSeat
{
    public function __construct(
        private TripRepository $trips,
        private BusRepository $buses,
        private BookingRepository $bookings,
        private SeatLock $lock,
        private TransactionBoundary $transactions,
    ) {
    }

    public function handle(
        int $tripId,
        int $startStationId,
        int $endStationId,
        int $seatNumber,
        Passenger $passenger,
        ?int $userId = null,
    ): Booking {
        return $this->lock->acquire($tripId, $seatNumber, function () use (
            $tripId,
            $startStationId,
            $endStationId,
            $seatNumber,
            $passenger,
            $userId,
        ): Booking {
            return $this->transactions->run(function () use (
                $tripId,
                $startStationId,
                $endStationId,
                $seatNumber,
                $passenger,
                $userId,
            ): Booking {
                $trip = $this->trips->find($tripId);

                if ($trip === null) {
                    throw TripNotFound::withId($tripId);
                }

                $bus = $this->buses->find($trip->busId);

                if ($bus === null) {
                    throw TripNotFound::withId($tripId);
                }

                try {
                    $seat = $bus->seat($seatNumber);
                } catch (\InvalidArgumentException) {
                    throw InvalidSeat::number($seatNumber);
                }

                $segment = $trip->segmentBetween($startStationId, $endStationId);
                $existing = $this->bookings->forTripAndSeat($tripId, $seatNumber);

                if ((new SeatAvailability())->isOccupied($seat, $segment, $existing)) {
                    throw SeatUnavailable::onTrip($tripId, $seatNumber);
                }

                return $this->bookings->save(new Booking(
                    0,
                    $trip->id,
                    $seatNumber,
                    $startStationId,
                    $endStationId,
                    $segment,
                    $passenger,
                    $userId,
                ));
            });
        });
    }
}
