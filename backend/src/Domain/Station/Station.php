<?php

declare(strict_types=1);

namespace Fleet\Domain\Station;

final readonly class Station
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }
}
