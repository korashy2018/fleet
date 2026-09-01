<?php

declare(strict_types=1);

namespace Fleet\Domain\Station;

interface StationRepository
{
    public function find(int $id): ?Station;

    /**
     * @return list<Station>
     */
    public function all(): array;
}
