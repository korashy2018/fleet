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
        private TripRepository $tripRepository,
        private BusRepository $busRepository,
        private BookingRepository $bookingRepository,
        private SeatLock $seatLock,
        private TransactionBoundary $transactionBoundary,
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
        return $this->seatLock->acquire($tripId, $seatNumber, function () use (
            $tripId,
            $startStationId,
            $endStationId,
            $seatNumber,
            $passenger,
            $userId,
        ): Booking {
            return $this->transactionBoundary->run(function () use (
                $tripId,
                $startStationId,
                $endStationId,
                $seatNumber,
                $passenger,
                $userId,
            ): Booking {
                $trip = $this->tripRepository->find($tripId);

                if ($trip === null) {
                    throw TripNotFound::withId($tripId);
                }

                $bus = $this->busRepository->find($trip->busId);

                if ($bus === null) {
                    throw TripNotFound::withId($tripId);
                }

                try {
                    $seat = $bus->seat($seatNumber);
                } catch (\InvalidArgumentException) {
                    throw InvalidSeat::number($seatNumber);
                }

                $segment = $trip->segmentBetween($startStationId, $endStationId);
                $existing = $this->bookingRepository->forTripAndSeat($tripId, $seatNumber);

                if ((new SeatAvailability())->isOccupied($seat, $segment, $existing)) {
                    throw SeatUnavailable::onTrip($tripId, $seatNumber);
                }

                return $this->bookingRepository->save(new Booking(
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
