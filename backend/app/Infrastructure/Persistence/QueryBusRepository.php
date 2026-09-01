<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use Fleet\Domain\Bus\Bus;
use Fleet\Domain\Bus\BusRepository;
use Illuminate\Support\Facades\DB;

final class QueryBusRepository implements BusRepository
{
    public function find(int $id): ?Bus
    {
        $exists = DB::table('buses')->where('id', $id)->exists();

        return $exists ? Bus::withTwelveSeats($id) : null;
    }
}
