<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Fleet\Application\Booking\LockUnavailable;
use Fleet\Domain\Booking\SeatLock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Doubles\PassThroughSeatLock;
use Tests\Fixtures\EgyptCatalog;
use Tests\TestCase;

final class BookingApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(SeatLock::class, new PassThroughSeatLock());
        EgyptCatalog::seed();
        EgyptCatalog::occupySeatFiveCairoToMinya();
    }

    #[Test]
    public function it_books_a_free_seat_on_cairo_to_minya(): void
    {
        $this->postJson('/api/v1/bookings', $this->cairoToMinya(seatNumber: 6))
            ->assertCreated()
            ->assertJsonPath('data.trip_id', EgyptCatalog::TRIP_CAIRO_ASYUT)
            ->assertJsonPath('data.seat_number', 6)
            ->assertJsonPath('data.start_station_id', EgyptCatalog::CAIRO)
            ->assertJsonPath('data.end_station_id', EgyptCatalog::MINYA)
            ->assertJsonPath('data.passenger.email', 'omar@example.com')
            ->assertJsonPath('data.user_id', null);
    }

    #[Test]
    public function it_rejects_an_overlapping_booking_on_seat_five(): void
    {
        $this->assertDatabaseHas('bookings', [
            'trip_id' => EgyptCatalog::TRIP_CAIRO_ASYUT,
            'seat_number' => 5,
            'start_station_id' => EgyptCatalog::CAIRO,
            'end_station_id' => EgyptCatalog::MINYA,
            'start_position' => 0,
            'end_position' => 2,
        ]);

        $this->postJson('/api/v1/bookings', $this->cairoToMinya(seatNumber: 5))
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'seat_unavailable');
    }

    #[Test]
    public function it_returns_422_for_a_station_not_on_the_trip(): void
    {
        $this->postJson('/api/v1/bookings', $this->payload(
            tripId: EgyptCatalog::TRIP_CAIRO_ASYUT,
            startStationId: EgyptCatalog::CAIRO,
            endStationId: EgyptCatalog::GIZA,
            seatNumber: 6,
        ))
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'invalid_station');
    }

    #[Test]
    public function it_returns_422_for_a_reversed_segment(): void
    {
        $this->postJson('/api/v1/bookings', $this->payload(
            tripId: EgyptCatalog::TRIP_CAIRO_ASYUT,
            startStationId: EgyptCatalog::MINYA,
            endStationId: EgyptCatalog::CAIRO,
            seatNumber: 6,
        ))
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'invalid_station_order');
    }

    #[Test]
    public function it_returns_404_for_an_unknown_trip(): void
    {
        $this->postJson('/api/v1/bookings', $this->payload(
            tripId: 99,
            startStationId: EgyptCatalog::CAIRO,
            endStationId: EgyptCatalog::MINYA,
            seatNumber: 6,
        ))
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

        $this->postJson('/api/v1/bookings', $this->cairoToMinya(seatNumber: 6))
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'lock_unavailable');
    }

    #[Test]
    public function it_returns_422_when_the_body_is_invalid(): void
    {
        $this->postJson('/api/v1/bookings', ['trip_id' => EgyptCatalog::TRIP_CAIRO_ASYUT])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_error');
    }

    #[Test]
    public function it_attaches_the_authenticated_user_to_the_booking(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createToken('api')->plainTextToken)
            ->postJson('/api/v1/bookings', $this->cairoToMinya(seatNumber: 6))
            ->assertCreated()
            ->assertJsonPath('data.user_id', $user->id);

        $this->assertDatabaseHas('bookings', [
            'seat_number' => 6,
            'user_id' => $user->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function cairoToMinya(int $seatNumber): array
    {
        return $this->payload(
            tripId: EgyptCatalog::TRIP_CAIRO_ASYUT,
            startStationId: EgyptCatalog::CAIRO,
            endStationId: EgyptCatalog::MINYA,
            seatNumber: $seatNumber,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(
        int $tripId,
        int $startStationId,
        int $endStationId,
        int $seatNumber,
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
