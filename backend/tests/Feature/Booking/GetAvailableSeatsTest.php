<?php

declare(strict_types=1);

namespace Tests\Feature\Booking;

use Database\Seeders\DatabaseSeeder;
use Fleet\Application\Booking\BookSeat;
use Fleet\Application\Booking\GetAvailableSeats;
use Fleet\Domain\Booking\Passenger;
use Fleet\Domain\Booking\SeatLock;
use Fleet\Domain\Trip\TripNotFound;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Doubles\PassThroughSeatLock;
use Tests\TestCase;

final class GetAvailableSeatsTest extends TestCase
{
    use RefreshDatabase;

    private const CAIRO = 1;

    private const MINYA = 4;

    private const ASYUT = 5;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(SeatLock::class, new PassThroughSeatLock());
        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function a_cairo_minya_booking_leaves_that_seat_free_onward_from_minya(): void
    {
        $this->app->make(BookSeat::class)->handle(
            1,
            self::CAIRO,
            self::MINYA,
            8,
            new Passenger('Omar Hassan', 'omar@example.com'),
        );

        $availability = $this->app->make(GetAvailableSeats::class);
        $cairoMinya = array_map(
            static fn ($seat): int => $seat->number,
            $availability->handle(1, self::CAIRO, self::MINYA),
        );
        $minyaAsyut = array_map(
            static fn ($seat): int => $seat->number,
            $availability->handle(1, self::MINYA, self::ASYUT),
        );

        $this->assertNotContains(8, $cairoMinya);
        $this->assertContains(8, $minyaAsyut);
        $this->assertNotContains(5, $cairoMinya);
        $this->assertCount(10, $cairoMinya);
    }

    #[Test]
    public function it_rejects_an_unknown_trip(): void
    {
        $this->expectException(TripNotFound::class);

        $this->app->make(GetAvailableSeats::class)->handle(99, self::CAIRO, self::MINYA);
    }
}
