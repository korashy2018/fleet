<?php

declare(strict_types=1);

namespace Fleet\Domain\Booking;

final readonly class Passenger
{
    public function __construct(
        public string $name,
        public string $email,
    ) {
    }
}
