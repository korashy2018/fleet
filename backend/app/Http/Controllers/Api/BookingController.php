<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use Fleet\Application\Booking\BookSeat;
use Fleet\Domain\Booking\Booking;
use Fleet\Domain\Booking\Passenger;
use Illuminate\Http\JsonResponse;

final class BookingController extends Controller
{
    public function __construct(
        private BookSeat $bookSeat,
    ) {
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        $user = $request->user('sanctum');

        $booking = $this->bookSeat->handle(
            (int) $request->validated('trip_id'),
            (int) $request->validated('start_station_id'),
            (int) $request->validated('end_station_id'),
            (int) $request->validated('seat_number'),
            new Passenger(
                (string) $request->validated('passenger.name'),
                (string) $request->validated('passenger.email'),
            ),
            $user === null ? null : (int) $user->id,
        );

        return response()->json(['data' => $this->payload($booking)], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'trip_id' => $booking->tripId,
            'seat_number' => $booking->seatNumber,
            'start_station_id' => $booking->startStationId,
            'end_station_id' => $booking->endStationId,
            'start_position' => $booking->segment->startPosition,
            'end_position' => $booking->segment->endPosition,
            'user_id' => $booking->userId,
            'passenger' => [
                'name' => $booking->passenger->name,
                'email' => $booking->passenger->email,
            ],
        ];
    }
}
