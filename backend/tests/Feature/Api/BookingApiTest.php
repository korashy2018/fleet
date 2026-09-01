<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Database\Seeders\DatabaseSeeder;
use Fleet\Application\Booking\LockUnavailable;
use Fleet\Domain\Booking\SeatLock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Doubles\PassThroughSeatLock;
use Tests\TestCase;

final class BookingApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(SeatLock::class, new PassThroughSeatLock());
        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function it_books_a_free_seat(): void
    {
        $this->postJson('/api/v1/bookings', $this->payload(6))
            ->assertCreated()
            ->assertJsonPath('data.seat_number', 6)
            ->assertJsonPath('data.start_station_id', 1)
            ->assertJsonPath('data.end_station_id', 4)
            ->assertJsonPath('data.passenger.email', 'omar@example.com');
    }

    #[Test]
    public function it_returns_409_when_the_seat_overlaps(): void
    {
        $this->postJson('/api/v1/bookings', $this->payload(5))
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'seat_unavailable');
    }

    #[Test]
    public function it_returns_422_for_a_station_not_on_the_trip(): void
    {
        $this->postJson('/api/v1/bookings', $this->payload(6, endStationId: 2))
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'invalid_station');
    }

    #[Test]
    public function it_returns_422_for_a_reversed_segment(): void
    {
        $this->postJson('/api/v1/bookings', $this->payload(6, startStationId: 4, endStationId: 1))
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'invalid_station_order');
    }

    #[Test]
    public function it_returns_404_for_an_unknown_trip(): void
    {
        $this->postJson('/api/v1/bookings', $this->payload(6, tripId: 99))
            ->assertNotFound()
            ->assertJsonPath('error.code', 'trip_not_found');
    }

    #[Test]
    public function it_returns_503_when_the_lock_is_unavailable(): void
    {
        $this->app->instance(SeatLock::class, new class implements SeatLock
        {
            public function acquire(int $tripId, int $seatNumber, callable $action): mixed
            {
                throw LockUnavailable::timeout($tripId, $seatNumber);
            }
        });

        $this->postJson('/api/v1/bookings', $this->payload(6))
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'lock_unavailable');
    }

    #[Test]
    public function it_returns_422_when_the_body_is_invalid(): void
    {
        $this->postJson('/api/v1/bookings', ['trip_id' => 1])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_error');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(
        int $seatNumber,
        int $tripId = 1,
        int $startStationId = 1,
        int $endStationId = 4,
    ): array {
        return [
            'trip_id' => $tripId,
            'start_station_id' => $startStationId,
            'end_station_id' => $endStationId,
            'seat_number' => $seatNumber,
            'passenger' => [
                'name' => 'Omar Hassan',
                'email' => 'omar@example.com',
            ],
        ];
    }
}
