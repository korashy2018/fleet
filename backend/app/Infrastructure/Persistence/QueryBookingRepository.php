<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Infrastructure\Persistence\Mappers\BookingMapper;
use Fleet\Domain\Booking\Booking;
use Fleet\Domain\Booking\BookingRepository;
use Illuminate\Support\Facades\DB;

final class QueryBookingRepository implements BookingRepository
{
    public function forTrip(int $tripId): array
    {
        return array_map(
            static fn (object $row): Booking => BookingMapper::toDomain((array) $row),
            DB::table('bookings')
                ->select($this->columns())
                ->where('trip_id', $tripId)
                ->orderBy('id')
                ->get()
                ->all(),
        );
    }

    public function forTripAndSeat(int $tripId, int $seatNumber): array
    {
        return array_map(
            static fn (object $row): Booking => BookingMapper::toDomain((array) $row),
            DB::table('bookings')
                ->select($this->columns())
                ->where('trip_id', $tripId)
                ->where('seat_number', $seatNumber)
                ->orderBy('id')
                ->get()
                ->all(),
        );
    }

    public function save(Booking $booking): Booking
    {
        $row = BookingMapper::toRow($booking);
        $now = now();
        $row['created_at'] = $now;
        $row['updated_at'] = $now;

        if ($booking->id > 0) {
            $row['id'] = $booking->id;
            DB::table('bookings')->insert($row);

            return $booking;
        }

        $id = (int) DB::table('bookings')->insertGetId($row);
        $row['id'] = $id;

        return BookingMapper::toDomain($row);
    }

    /**
     * @return list<string>
     */
    private function columns(): array
    {
        return [
            'id',
            'trip_id',
            'seat_number',
            'start_station_id',
            'end_station_id',
            'start_position',
            'end_position',
            'user_id',
            'passenger_name',
            'passenger_email',
        ];
    }
}
