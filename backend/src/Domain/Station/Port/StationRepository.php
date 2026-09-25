<?php

declare(strict_types=1);

namespace Fleet\Domain\Station\Port;

use Fleet\Domain\Station\Station;

interface StationRepository
{
    public function find(int $id): ?Station;

    /**
     * @return list<Station>
     */
    public function all(): array;
}
