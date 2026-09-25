<?php

declare(strict_types=1);

namespace Fleet\Domain\Trip;

use Fleet\Domain\Trip\Exception\StationNotOnTrip;
use Fleet\Domain\Trip\ValueObject\Segment;

final readonly class Trip
{
    /**
     * @param list<int> $stationIds
     */
    private function __construct(
        public int $id,
        public string $name,
        public int $busId,
        public array $stationIds,
    ) {
    }

    public static function define(
        int $id,
        string $name,
        int $busId,
        int ...$stationIds,
    ): self {
        if (count($stationIds) < 2) {
            throw new \InvalidArgumentException('A trip needs at least two stations.');
        }

        return new self($id, $name, $busId, array_values($stationIds));
    }

    public function segmentBetween(int $startStationId, int $endStationId): Segment
    {
        return new Segment(
            $this->positionOf($startStationId),
            $this->positionOf($endStationId),
        );
    }

    public function positionOf(int $stationId): int
    {
        $position = array_search($stationId, $this->stationIds, true);

        if ($position === false) {
            throw StationNotOnTrip::withId($stationId);
        }

        return $position;
    }
}
