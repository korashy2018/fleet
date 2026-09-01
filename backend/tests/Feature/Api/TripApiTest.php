<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class TripApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function it_lists_trips_with_ordered_stations(): void
    {
        $response = $this->getJson('/api/v1/trips');

        $response->assertOk()
            ->assertJsonPath('data.0.id', 1)
            ->assertJsonPath('data.0.name', 'Cairo to Asyut')
            ->assertJsonPath('data.0.seat_count', 12)
            ->assertJsonPath('data.0.stations.0.name', 'Cairo')
            ->assertJsonPath('data.1.name', 'Cairo to Asyut via Giza');
    }

    #[Test]
    public function it_shows_a_trip(): void
    {
        $this->getJson('/api/v1/trips/1')
            ->assertOk()
            ->assertJsonPath('data.id', 1)
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
    public function it_marks_seeded_seat_five_unavailable_cairo_to_minya(): void
    {
        $this->getJson('/api/v1/trips/1/available-seats?start_station_id=1&end_station_id=4')
            ->assertOk()
            ->assertJsonPath('data.seats.4.number', 5)
            ->assertJsonPath('data.seats.4.available', false)
            ->assertJsonPath('data.seats.5.available', true);
    }

    #[Test]
    public function it_rejects_available_seats_without_stations(): void
    {
        $this->getJson('/api/v1/trips/1/available-seats')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_error');
    }
}
