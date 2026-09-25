<?php

declare(strict_types=1);

namespace Tests\Domain\Bus;

use Fleet\Domain\Bus\ValueObject\Seat;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SeatTest extends TestCase
{
    #[Test]
    public function seats_with_the_same_bus_and_number_are_equal(): void
    {
        $left = new Seat(1, 5);
        $right = new Seat(1, 5);

        $this->assertTrue($left->equals($right));
        $this->assertTrue($right->equals($left));
        $this->assertTrue($left->isOnBus(1));
    }

    #[Test]
    public function seats_differ_when_any_attribute_differs(): void
    {
        $seat = new Seat(1, 5);

        $this->assertFalse($seat->equals(new Seat(2, 5)));
        $this->assertFalse($seat->equals(new Seat(1, 6)));
        $this->assertFalse($seat->isOnBus(2));
    }

    #[Test]
    public function it_rejects_a_number_outside_one_to_twelve(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Seat(1, 13);
    }
}
