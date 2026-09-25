<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Infrastructure\Persistence\Mappers\TripMapper;
use Fleet\Domain\Trip\Port\TripRepository;
use Fleet\Domain\Trip\Trip;
use Illuminate\Support\Facades\DB;

final class QueryTripRepository implements TripRepository
{
    public function find(int $id): ?Trip
    {
        $row = DB::table('trips')->select(['id', 'name', 'bus_id'])->where('id', $id)->first();

        if ($row === null) {
            return null;
        }

        return TripMapper::toDomain((array) $row, $this->stopsFor($id)[$id] ?? []);
    }

    public function all(): array
    {
        $trips = array_map(
            static fn (object $row): array => (array) $row,
            DB::table('trips')->select(['id', 'name', 'bus_id'])->orderBy('id')->get()->all(),
        );

        if ($trips === []) {
            return [];
        }

        $stops = $this->stopsFor(...array_map(static fn (array $trip): int => (int) $trip['id'], $trips));

        return array_map(
            static fn (array $trip): Trip => TripMapper::toDomain($trip, $stops[(int) $trip['id']] ?? []),
            $trips,
        );
    }

    /**
     * @return array<int, list<array<string, mixed>>>
     */
    private function stopsFor(int ...$tripIds): array
    {
        if ($tripIds === []) {
            return [];
        }

        $grouped = [];

        foreach (DB::table('trip_stations')
            ->select(['trip_id', 'station_id', 'position'])
            ->whereIn('trip_id', $tripIds)
            ->orderBy('position')
            ->get()
            ->all() as $row) {
            $stop = (array) $row;
            $grouped[(int) $stop['trip_id']][] = $stop;
        }

        return $grouped;
    }
}
