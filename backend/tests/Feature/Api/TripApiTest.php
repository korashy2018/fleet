<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\EgyptCatalog;
use Tests\TestCase;

final class TripApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        EgyptCatalog::seed();
        EgyptCatalog::occupySeatFiveCairoToMinya();
    }

    #[Test]
    public function it_lists_trips_with_ordered_stations(): void
    {
        $this->getJson('/api/v1/trips')
            ->assertOk()
            ->assertJsonPath('data.0.id', EgyptCatalog::TRIP_CAIRO_ASYUT)
            ->assertJsonPath('data.0.name', 'Cairo to Asyut')
            ->assertJsonPath('data.0.seat_count', 12)
            ->assertJsonPath('data.0.stations.0.name', 'Cairo')
            ->assertJsonPath('data.1.id', EgyptCatalog::TRIP_VIA_GIZA)
            ->assertJsonPath('data.1.name', 'Cairo to Asyut via Giza');
    }

    #[Test]
    public function it_shows_a_trip(): void
    {
        $this->getJson('/api/v1/trips/'.EgyptCatalog::TRIP_CAIRO_ASYUT)
            ->assertOk()
            ->assertJsonPath('data.id', EgyptCatalog::TRIP_CAIRO_ASYUT)
            ->assertJsonPath('data.stations.2.name', 'Al Minya');
    }

    #[Test]
    public function it_returns_404_for_an_unknown_trip(): void
    {
        $this->getJson('/api/v1/trips/99')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'trip_not_found');
    }

    #[Test]
    public function occupied_seat_five_is_unavailable_cairo_to_minya(): void
    {
        $this->assertDatabaseHas('bookings', [
            'trip_id' => EgyptCatalog::TRIP_CAIRO_ASYUT,
            'seat_number' => 5,
            'start_station_id' => EgyptCatalog::CAIRO,
            'end_station_id' => EgyptCatalog::MINYA,
        ]);

        $seats = $this->getJson(sprintf(
            '/api/v1/trips/%d/available-seats?start_station_id=%d&end_station_id=%d',
            EgyptCatalog::TRIP_CAIRO_ASYUT,
            EgyptCatalog::CAIRO,
            EgyptCatalog::MINYA,
        ))
            ->assertOk()
            ->json('data.seats');

        $available = [];

        foreach ($seats as $seat) {
            $available[$seat['number']] = $seat['available'];
        }

        $this->assertFalse($available[5]);
        $this->assertTrue($available[6]);
    }

    #[Test]
    public function it_rejects_available_seats_without_stations(): void
    {
        $this->getJson('/api/v1/trips/'.EgyptCatalog::TRIP_CAIRO_ASYUT.'/available-seats')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_error');
    }
}
