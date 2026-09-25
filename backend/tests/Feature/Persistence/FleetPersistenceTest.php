<?php

declare(strict_types=1);

namespace Tests\Feature\Persistence;

use Database\Seeders\DatabaseSeeder;
use Fleet\Domain\Booking\Booking;
use Fleet\Domain\Booking\ValueObject\Passenger;
use Fleet\Domain\Booking\Port\BookingRepository;
use Fleet\Domain\Bus\Bus;
use Fleet\Domain\Bus\Port\BusRepository;
use Fleet\Domain\Station\Port\StationRepository;
use Fleet\Domain\Trip\Port\TripRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class FleetPersistenceTest extends TestCase
{
    use RefreshDatabase;

    private const CAIRO = 1;

    private const FAYYUM = 2;

    private const MINYA = 3;

    private const ASYUT = 4;

    #[Test]
    public function it_round_trips_a_trip_bus_and_booking(): void
    {
        $this->catalog();

        $trip = $this->app->make(TripRepository::class)->find(1);
        $bus = $this->app->make(BusRepository::class)->find(1);
        $cairo = $this->app->make(StationRepository::class)->find(self::CAIRO);

        $this->assertNotNull($trip);
        $this->assertNotNull($bus);
        $this->assertNotNull($cairo);
        $this->assertSame('Cairo to Asyut', $trip->name);
        $this->assertSame([self::CAIRO, self::FAYYUM, self::MINYA, self::ASYUT], $trip->stationIds);
        $this->assertSame(0, $trip->segmentBetween(self::CAIRO, self::MINYA)->startPosition);
        $this->assertSame(2, $trip->segmentBetween(self::CAIRO, self::MINYA)->endPosition);
        $this->assertCount(Bus::SEAT_COUNT, $bus->seats);
        $this->assertSame('Cairo', $cairo->name);

        $bookings = $this->app->make(BookingRepository::class);
        $saved = $bookings->save(new Booking(
            0,
            $trip->id,
            5,
            self::CAIRO,
            self::MINYA,
            $trip->segmentBetween(self::CAIRO, self::MINYA),
            new Passenger('Mona Ali', 'mona@example.com'),
            null,
        ));

        $this->assertGreaterThan(0, $saved->id);

        $loaded = $bookings->forTripAndSeat($trip->id, 5);

        $this->assertCount(1, $loaded);
        $this->assertSame(5, $loaded[0]->seatNumber);
        $this->assertSame(self::CAIRO, $loaded[0]->startStationId);
        $this->assertSame(self::MINYA, $loaded[0]->endStationId);
        $this->assertSame(0, $loaded[0]->segment->startPosition);
        $this->assertSame(2, $loaded[0]->segment->endPosition);
        $this->assertSame('Mona Ali', $loaded[0]->passenger->name);
        $this->assertNull($loaded[0]->userId);
    }

    #[Test]
    public function the_seeder_loads_the_egypt_catalog(): void
    {
        $this->seed(DatabaseSeeder::class);

        $trips = $this->app->make(TripRepository::class)->all();
        $stations = $this->app->make(StationRepository::class)->all();
        $bookings = $this->app->make(BookingRepository::class)->forTrip(1);

        $this->assertCount(5, $stations);
        $this->assertCount(2, $trips);
        $this->assertSame('Cairo to Asyut', $trips[0]->name);
        $this->assertSame('Cairo to Asyut via Giza', $trips[1]->name);
        $this->assertCount(2, $bookings);
        $this->assertTrue($bookings[0]->occupiesSeat(5));
        $this->assertFalse($bookings[0]->occupies($trips[0]->segmentBetween(4, 5)));
    }

    private function catalog(): void
    {
        $now = now();

        DB::table('stations')->insert([
            ['id' => self::CAIRO, 'name' => 'Cairo', 'created_at' => $now, 'updated_at' => $now],
            ['id' => self::FAYYUM, 'name' => 'Al Fayyum', 'created_at' => $now, 'updated_at' => $now],
            ['id' => self::MINYA, 'name' => 'Al Minya', 'created_at' => $now, 'updated_at' => $now],
            ['id' => self::ASYUT, 'name' => 'Asyut', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('buses')->insert(['id' => 1, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('trips')->insert([
            'id' => 1,
            'name' => 'Cairo to Asyut',
            'bus_id' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('trip_stations')->insert([
            ['trip_id' => 1, 'station_id' => self::CAIRO, 'position' => 0],
            ['trip_id' => 1, 'station_id' => self::FAYYUM, 'position' => 1],
            ['trip_id' => 1, 'station_id' => self::MINYA, 'position' => 2],
            ['trip_id' => 1, 'station_id' => self::ASYUT, 'position' => 3],
        ]);
    }
}
