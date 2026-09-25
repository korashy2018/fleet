<?php

declare(strict_types=1);

namespace Tests\Domain\Booking;

use Fleet\Domain\Booking\ValueObject\Passenger;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PassengerTest extends TestCase
{
    #[Test]
    public function passengers_with_the_same_name_and_email_are_equal(): void
    {
        $left = new Passenger('Mona Ali', 'mona@example.com');
        $right = new Passenger('Mona Ali', 'mona@example.com');

        $this->assertTrue($left->equals($right));
        $this->assertTrue($right->equals($left));
    }

    #[Test]
    public function passengers_differ_when_any_attribute_differs(): void
    {
        $mona = new Passenger('Mona Ali', 'mona@example.com');

        $this->assertFalse($mona->equals(new Passenger('Omar Hassan', 'mona@example.com')));
        $this->assertFalse($mona->equals(new Passenger('Mona Ali', 'omar@example.com')));
    }
}
