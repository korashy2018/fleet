<?php

declare(strict_types=1);

namespace Fleet\Domain\Trip\Port;

use Fleet\Domain\Trip\Trip;

interface TripRepository
{
    public function find(int $id): ?Trip;

    /**
     * @return list<Trip>
     */
    public function all(): array;
}
