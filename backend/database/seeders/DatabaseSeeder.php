<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    private const CAIRO = 1;

    private const GIZA = 2;

    private const FAYYUM = 3;

    private const MINYA = 4;

    private const ASYUT = 5;

    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

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
            ['id' => 1, 'name' => 'Cairo to Asyut', 'bus_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'Cairo to Asyut via Giza', 'bus_id' => 2, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->stops(1, [self::CAIRO, self::FAYYUM, self::MINYA, self::ASYUT]);
        $this->stops(2, [self::CAIRO, self::GIZA, self::FAYYUM, self::MINYA, self::ASYUT]);

        DB::table('bookings')->insert([
            $this->booking(1, 5, self::CAIRO, self::MINYA, 0, 2, 'Mona Ali', 'mona@example.com', $now),
            $this->booking(1, 5, self::MINYA, self::ASYUT, 2, 3, 'Mona Ali', 'mona@example.com', $now),
            $this->booking(2, 1, self::CAIRO, self::GIZA, 0, 1, 'Omar Hassan', 'omar@example.com', $now),
        ]);
    }

    /**
     * @param  list<int>  $stationIds
     */
    private function stops(int $tripId, array $stationIds): void
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

    /**
     * @return array<string, mixed>
     */
    private function booking(
        int $tripId,
        int $seatNumber,
        int $startStationId,
        int $endStationId,
        int $startPosition,
        int $endPosition,
        string $name,
        string $email,
        mixed $now,
    ): array {
        return [
            'trip_id' => $tripId,
            'seat_number' => $seatNumber,
            'start_station_id' => $startStationId,
            'end_station_id' => $endStationId,
            'start_position' => $startPosition,
            'end_position' => $endPosition,
            'user_id' => null,
            'passenger_name' => $name,
            'passenger_email' => $email,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
