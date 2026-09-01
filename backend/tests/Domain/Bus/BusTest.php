<?php

declare(strict_types=1);

namespace Tests\Domain\Bus;

use Fleet\Domain\Bus\Bus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BusTest extends TestCase
{
    #[Test]
    public function it_has_twelve_seats_numbered_on_the_bus(): void
    {
        $bus = Bus::withTwelveSeats(1);

        $this->assertCount(Bus::SEAT_COUNT, $bus->seats);
        $this->assertSame(5, $bus->seat(5)->number);
        $this->assertSame(1, $bus->seat(5)->busId);
    }

    #[Test]
    public function it_rejects_a_seat_number_outside_the_bus(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Bus::withTwelveSeats(1)->seat(13);
    }
}
