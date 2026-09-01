<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mappers;

use Fleet\Domain\Booking\Booking;
use Fleet\Domain\Booking\Passenger;
use Fleet\Domain\Trip\Segment;

final class BookingMapper
{
    /**
     * @param  array<string, mixed>  $row
     */
    public static function toDomain(array $row): Booking
    {
        return new Booking(
            (int) $row['id'],
            (int) $row['trip_id'],
            (int) $row['seat_number'],
            (int) $row['start_station_id'],
            (int) $row['end_station_id'],
            new Segment((int) $row['start_position'], (int) $row['end_position']),
            new Passenger((string) $row['passenger_name'], (string) $row['passenger_email']),
            $row['user_id'] === null ? null : (int) $row['user_id'],
        );
    }

    /**
     * @return array{
     *     trip_id: int,
     *     seat_number: int,
     *     start_station_id: int,
     *     end_station_id: int,
     *     start_position: int,
     *     end_position: int,
     *     user_id: int|null,
     *     passenger_name: string,
     *     passenger_email: string
     * }
     */
    public static function toRow(Booking $booking): array
    {
        return [
            'trip_id' => $booking->tripId,
            'seat_number' => $booking->seatNumber,
            'start_station_id' => $booking->startStationId,
            'end_station_id' => $booking->endStationId,
            'start_position' => $booking->segment->startPosition,
            'end_position' => $booking->segment->endPosition,
            'user_id' => $booking->userId,
            'passenger_name' => $booking->passenger->name,
            'passenger_email' => $booking->passenger->email,
        ];
    }
}
