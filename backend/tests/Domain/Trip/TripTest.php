<?php

declare(strict_types=1);

namespace Tests\Domain\Trip;

use Fleet\Domain\Trip\InvalidSegment;
use Fleet\Domain\Trip\StationNotOnTrip;
use Fleet\Domain\Trip\Trip;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TripTest extends TestCase
{
    private const CAIRO = 1;

    private const FAYYUM = 2;

    private const MINYA = 3;

    private const ASYUT = 4;

    private Trip $trip;

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
    }

    #[Test]
    public function it_maps_cairo_to_minya_to_a_half_open_segment(): void
    {
        $segment = $this->trip->segmentBetween(self::CAIRO, self::MINYA);

        $this->assertSame(0, $segment->startPosition);
        $this->assertSame(2, $segment->endPosition);
    }

    #[Test]
    #[DataProvider('invalidStationPairs')]
    public function it_rejects_invalid_station_pairs(
        int $start,
        int $end,
        string $exception,
    ): void {
        $this->expectException($exception);

        $this->trip->segmentBetween($start, $end);
    }

    /**
     * @return iterable<string, array{int, int, class-string<\Throwable>}>
     */
    public static function invalidStationPairs(): iterable
    {
        yield 'unknown_start' => [99, self::ASYUT, StationNotOnTrip::class];
        yield 'unknown_end' => [self::CAIRO, 99, StationNotOnTrip::class];
        yield 'reversed' => [self::MINYA, self::CAIRO, InvalidSegment::class];
        yield 'same_station' => [self::CAIRO, self::CAIRO, InvalidSegment::class];
    }
}
