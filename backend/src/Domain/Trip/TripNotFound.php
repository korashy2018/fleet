<?php

declare(strict_types=1);

namespace Fleet\Domain\Trip;

final class TripNotFound extends \RuntimeException
{
    public static function withId(int $id): self
    {
        return new self(sprintf('Trip %d was not found.', $id));
    }
}
