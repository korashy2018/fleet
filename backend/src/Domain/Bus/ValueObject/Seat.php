<?php

declare(strict_types=1);

namespace Fleet\Domain\Bus\ValueObject;

final readonly class Seat
{
    public function __construct(
        public int $busId,
        public int $number,
    ) {
        if ($number < 1 || $number > 12) {
            throw new \InvalidArgumentException('A bus seat number must be between 1 and 12.');
        }
    }

    public function isOnBus(int $busId): bool
    {
        return $this->busId === $busId;
    }

    public function equals(self $other): bool
    {
        return $this->busId === $other->busId
            && $this->number === $other->number;
    }
}
