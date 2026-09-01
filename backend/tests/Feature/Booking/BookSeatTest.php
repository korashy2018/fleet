<?php

declare(strict_types=1);

namespace Tests\Feature\Booking;

use Database\Seeders\DatabaseSeeder;
use Fleet\Application\Booking\BookSeat;
use Fleet\Domain\Booking\Passenger;
use Fleet\Domain\Booking\SeatLock;
use Fleet\Domain\Booking\SeatUnavailable;
use Fleet\Domain\Bus\InvalidSeat;
use Fleet\Domain\Trip\InvalidSegment;
use Fleet\Domain\Trip\StationNotOnTrip;
use Fleet\Domain\Trip\TripNotFound;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Doubles\PassThroughSeatLock;
use Tests\TestCase;

final class BookSeatTest extends TestCase
{
    use RefreshDatabase;

    private const CAIRO = 1;

    private const FAYYUM = 3;

    private const MINYA = 4;

    private const ASYUT = 5;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(SeatLock::class, new PassThroughSeatLock());
        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function it_books_a_free_seat(): void
    {
        $booking = $this->book(6, self::CAIRO, self::MINYA);

        $this->assertGreaterThan(0, $booking->id);
        $this->assertSame(6, $booking->seatNumber);
        $this->assertSame(0, $booking->segment->startPosition);
        $this->assertSame(2, $booking->segment->endPosition);
    }

    #[Test]
    public function it_rejects_an_overlapping_segment_on_the_same_seat(): void
    {
        $this->expectException(SeatUnavailable::class);

        $this->book(5, self::CAIRO, self::FAYYUM);
    }

    #[Test]
    public function it_reuses_a_seat_on_an_adjacent_segment(): void
    {
        $this->book(7, self::CAIRO, self::MINYA);
        $later = $this->book(7, self::MINYA, self::ASYUT);

        $this->assertSame(7, $later->seatNumber);
        $this->assertSame(2, $later->segment->startPosition);
        $this->assertSame(3, $later->segment->endPosition);
    }

    #[Test]
    public function it_rejects_an_unknown_trip(): void
    {
        $this->expectException(TripNotFound::class);

        $this->book(6, self::CAIRO, self::MINYA, tripId: 99);
    }

    #[Test]
    public function it_rejects_a_seat_that_is_not_on_the_bus(): void
    {
        $this->expectException(InvalidSeat::class);

        $this->book(13, self::CAIRO, self::MINYA);
    }

    #[Test]
    public function it_rejects_a_station_that_is_not_on_the_trip(): void
    {
        $this->expectException(StationNotOnTrip::class);

        $this->book(6, self::CAIRO, 2);
    }

    #[Test]
    public function it_rejects_a_reversed_segment(): void
    {
        $this->expectException(InvalidSegment::class);

        $this->book(6, self::MINYA, self::CAIRO);
    }

    private function book(
        int $seatNumber,
        int $start,
        int $end,
        int $tripId = 1,
    ): \Fleet\Domain\Booking\Booking {
        return $this->app->make(BookSeat::class)->handle(
            $tripId,
            $start,
            $end,
            $seatNumber,
            new Passenger('Omar Hassan', 'omar@example.com'),
        );
    }
}
