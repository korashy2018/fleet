<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Infrastructure\Persistence\Mappers\StationMapper;
use Fleet\Domain\Station\Station;
use Fleet\Domain\Station\StationRepository;
use Illuminate\Support\Facades\DB;

final class QueryStationRepository implements StationRepository
{
    public function find(int $id): ?Station
    {
        $row = DB::table('stations')->select(['id', 'name'])->where('id', $id)->first();

        return $row === null ? null : StationMapper::toDomain((array) $row);
    }

    public function all(): array
    {
        return array_map(
            static fn (object $row): Station => StationMapper::toDomain((array) $row),
            DB::table('stations')->select(['id', 'name'])->orderBy('id')->get()->all(),
        );
    }
}
