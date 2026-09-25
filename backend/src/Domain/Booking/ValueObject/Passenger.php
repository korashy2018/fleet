<?php

declare(strict_types=1);

namespace Fleet\Domain\Booking\ValueObject;

final readonly class Passenger
{
    public function __construct(
        public string $name,
        public string $email,
    ) {
    }

    public function equals(self $other): bool
    {
        return $this->name === $other->name
            && $this->email === $other->email;
    }
}
