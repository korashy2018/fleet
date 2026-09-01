<?php

declare(strict_types=1);

namespace Tests\Domain\Booking;

use Fleet\Domain\Booking\Booking;
use Fleet\Domain\Booking\Passenger;
use Fleet\Domain\Booking\SeatAvailability;
use Fleet\Domain\Bus\Bus;
use Fleet\Domain\Bus\Seat;
use Fleet\Domain\Trip\Trip;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SeatAvailabilityTest extends TestCase
{
    private const CAIRO = 1;

    private const FAYYUM = 2;

    private const MINYA = 3;

    private const ASYUT = 4;

    private Trip $trip;

    private Bus $bus;

    private SeatAvailability $availability;

    protected function setUp(): void
    {
        $this->trip = Trip::define(
            1,
            'Cairo to Asyut',
            1,
            self::CAIRO,
            self::FAYYUM,
            self::MINYA,
            self::ASYUT,
        );
        $this->bus = Bus::withTwelveSeats(1);
        $this->availability = new SeatAvailability();
    }

    #[Test]
    #[DataProvider('cairoMinyaOccupancy')]
    public function seat_five_booked_cairo_to_minya(
        int $start,
        int $end,
        bool $seatFiveAvailable,
    ): void {
        $existing = [$this->booking(self::CAIRO, self::MINYA)];
        $requested = $this->trip->segmentBetween($start, $end);
        $available = $this->availability->availableSeats($requested, $this->bus->seats, $existing);
        $numbers = array_map(fn (Seat $seat): int => $seat->number, $available);

        $this->assertSame($seatFiveAvailable, in_array(5, $numbers, true));
    }

    /**
     * @return iterable<string, array{int, int, bool}>
     */
    public static function cairoMinyaOccupancy(): iterable
    {
        yield 'cairo_fayyum_blocked' => [self::CAIRO, self::FAYYUM, false];
        yield 'fayyum_minya_blocked' => [self::FAYYUM, self::MINYA, false];
        yield 'fayyum_asyut_blocked' => [self::FAYYUM, self::ASYUT, false];
        yield 'minya_asyut_allowed' => [self::MINYA, self::ASYUT, true];
    }

    #[Test]
    public function a_seat_can_be_booked_again_on_a_non_overlapping_segment(): void
    {
        $existing = [$this->booking(self::CAIRO, self::MINYA)];
        $reuse = $this->trip->segmentBetween(self::MINYA, self::ASYUT);

        $this->assertFalse($this->availability->isOccupied($this->bus->seat(5), $reuse, $existing));
    }

    private function booking(int $start, int $end): Booking
    {
        return new Booking(
            id: 1,
            tripId: $this->trip->id,
            seatNumber: 5,
            startStationId: $start,
            endStationId: $end,
            segment: $this->trip->segmentBetween($start, $end),
            passenger: new Passenger('Mona Ali', 'mona@example.com'),
            userId: null,
        );
    }
}
