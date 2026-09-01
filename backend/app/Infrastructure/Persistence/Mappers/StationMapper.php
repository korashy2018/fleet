<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mappers;

use Fleet\Domain\Station\Station;

final class StationMapper
{
    /**
     * @param  array<string, mixed>  $row
     */
    public static function toDomain(array $row): Station
    {
        return new Station((int) $row['id'], (string) $row['name']);
    }
}
