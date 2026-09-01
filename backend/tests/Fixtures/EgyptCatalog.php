<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Illuminate\Support\Facades\DB;

final class EgyptCatalog
{
    public const TRIP_CAIRO_ASYUT = 1;

    public const TRIP_VIA_GIZA = 2;

    public const CAIRO = 1;

    public const GIZA = 2;

    public const FAYYUM = 3;

    public const MINYA = 4;

    public const ASYUT = 5;

    public static function seed(): void
    {
        $now = now();

        DB::table('stations')->insert([
            ['id' => self::CAIRO, 'name' => 'Cairo', 'created_at' => $now, 'updated_at' => $now],
            ['id' => self::GIZA, 'name' => 'Giza', 'created_at' => $now, 'updated_at' => $now],
            ['id' => self::FAYYUM, 'name' => 'Al Fayyum', 'created_at' => $now, 'updated_at' => $now],
            ['id' => self::MINYA, 'name' => 'Al Minya', 'created_at' => $now, 'updated_at' => $now],
            ['id' => self::ASYUT, 'name' => 'Asyut', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('buses')->insert([
            ['id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('trips')->insert([
            [
                'id' => self::TRIP_CAIRO_ASYUT,
                'name' => 'Cairo to Asyut',
                'bus_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => self::TRIP_VIA_GIZA,
                'name' => 'Cairo to Asyut via Giza',
                'bus_id' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        self::stops(self::TRIP_CAIRO_ASYUT, [self::CAIRO, self::FAYYUM, self::MINYA, self::ASYUT]);
        self::stops(self::TRIP_VIA_GIZA, [self::CAIRO, self::GIZA, self::FAYYUM, self::MINYA, self::ASYUT]);
    }

    public static function occupySeatFiveCairoToMinya(): void
    {
        $now = now();

        DB::table('bookings')->insert([
            'trip_id' => self::TRIP_CAIRO_ASYUT,
            'seat_number' => 5,
            'start_station_id' => self::CAIRO,
            'end_station_id' => self::MINYA,
            'start_position' => 0,
            'end_position' => 2,
            'user_id' => null,
            'passenger_name' => 'Mona Ali',
            'passenger_email' => 'mona@example.com',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * @param  list<int>  $stationIds
     */
    private static function stops(int $tripId, array $stationIds): void
    {
        $rows = [];

        foreach ($stationIds as $position => $stationId) {
            $rows[] = [
                'trip_id' => $tripId,
                'station_id' => $stationId,
                'position' => $position,
            ];
        }

        DB::table('trip_stations')->insert($rows);
    }
}
