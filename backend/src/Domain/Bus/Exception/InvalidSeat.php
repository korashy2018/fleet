<?php

declare(strict_types=1);

namespace Fleet\Domain\Bus\Exception;

final class InvalidSeat extends \InvalidArgumentException
{
    public static function number(int $number): self
    {
        return new self(sprintf('Seat %d is not on this trip\'s bus.', $number));
    }
}
