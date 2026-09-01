<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListAvailableSeatsRequest;
use Fleet\Application\Booking\GetAvailableSeats;
use Fleet\Domain\Bus\Bus;
use Fleet\Domain\Station\StationRepository;
use Fleet\Domain\Trip\Trip;
use Fleet\Domain\Trip\TripNotFound;
use Fleet\Domain\Trip\TripRepository;
use Illuminate\Http\JsonResponse;

final class TripController extends Controller
{
    public function __construct(
        private TripRepository $trips,
        private StationRepository $stations,
        private GetAvailableSeats $availableSeats,
    ) {
    }

    public function index(): JsonResponse
    {
        $names = $this->stationNames();

        return response()->json([
            'data' => array_map(
                fn (Trip $trip): array => $this->tripPayload($trip, $names),
                $this->trips->all(),
            ),
        ]);
    }

    public function show(int $trip): JsonResponse
    {
        return response()->json([
            'data' => $this->tripPayload($this->trip($trip), $this->stationNames()),
        ]);
    }

    public function availableSeats(ListAvailableSeatsRequest $request, int $trip): JsonResponse
    {
        $this->trip($trip);

        $free = [];

        foreach ($this->availableSeats->handle(
            $trip,
            (int) $request->validated('start_station_id'),
            (int) $request->validated('end_station_id'),
        ) as $seat) {
            $free[$seat->number] = true;
        }

        $seats = [];

        for ($number = 1; $number <= Bus::SEAT_COUNT; $number++) {
            $seats[] = [
                'number' => $number,
                'available' => isset($free[$number]),
            ];
        }

        return response()->json(['data' => ['seats' => $seats]]);
    }

    /**
     * @param  array<int, string>  $names
     * @return array<string, mixed>
     */
    private function tripPayload(Trip $trip, array $names): array
    {
        $stations = [];

        foreach ($trip->stationIds as $position => $stationId) {
            $stations[] = [
                'id' => $stationId,
                'name' => $names[$stationId] ?? '',
                'position' => $position,
            ];
        }

        return [
            'id' => $trip->id,
            'name' => $trip->name,
            'bus_id' => $trip->busId,
            'seat_count' => Bus::SEAT_COUNT,
            'stations' => $stations,
        ];
    }

    private function trip(int $id): Trip
    {
        return $this->trips->find($id) ?? throw TripNotFound::withId($id);
    }

    /**
     * @return array<int, string>
     */
    private function stationNames(): array
    {
        $names = [];

        foreach ($this->stations->all() as $station) {
            $names[$station->id] = $station->name;
        }

        return $names;
    }
}
