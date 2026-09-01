<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mappers;

use Fleet\Domain\Trip\Trip;

final class TripMapper
{
    /**
     * @param  array<string, mixed>  $trip
     * @param  list<array<string, mixed>>  $stops
     */
    public static function toDomain(array $trip, array $stops): Trip
    {
        $stationIds = array_map(
            static fn (array $stop): int => (int) $stop['station_id'],
            $stops,
        );

        return Trip::define(
            (int) $trip['id'],
            (string) $trip['name'],
            (int) $trip['bus_id'],
            ...$stationIds,
        );
    }
}
