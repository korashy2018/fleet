<?php

declare(strict_types=1);

namespace Fleet\Domain\Bus;

interface BusRepository
{
    public function find(int $id): ?Bus;
}
