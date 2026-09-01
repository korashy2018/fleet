<?php

declare(strict_types=1);

namespace App\Http\Exceptions;

use Fleet\Application\Booking\LockUnavailable;
use Fleet\Domain\Booking\SeatUnavailable;
use Fleet\Domain\Bus\InvalidSeat;
use Fleet\Domain\Trip\InvalidSegment;
use Fleet\Domain\Trip\StationNotOnTrip;
use Fleet\Domain\Trip\TripNotFound;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final class MapsApiExceptions
{
    public function __invoke(Throwable $exception, Request $request): ?JsonResponse
    {
        if (! $request->is('api/*')) {
            return null;
        }

        if ($exception instanceof HttpExceptionInterface) {
            return null;
        }

        return match (true) {
            $exception instanceof ValidationException => $this->error(
                'validation_error',
                'The given data was invalid.',
                422,
                $exception->errors(),
            ),
            $exception instanceof TripNotFound => $this->error('trip_not_found', $exception->getMessage(), 404),
            $exception instanceof StationNotOnTrip => $this->error('invalid_station', $exception->getMessage(), 422),
            $exception instanceof InvalidSegment => $this->error('invalid_station_order', $exception->getMessage(), 422),
            $exception instanceof InvalidSeat => $this->error('invalid_seat', $exception->getMessage(), 422),
            $exception instanceof SeatUnavailable => $this->error('seat_unavailable', $exception->getMessage(), 409),
            $exception instanceof LockUnavailable => $this->error('lock_unavailable', $exception->getMessage(), 503),
            default => $this->error('internal_error', 'An unexpected error occurred.', 500),
        };
    }

    /**
     * @param  array<string, mixed>|null  $details
     */
    private function error(string $code, string $message, int $status, ?array $details = null): JsonResponse
    {
        $error = [
            'code' => $code,
            'message' => $message,
        ];

        if ($details !== null) {
            $error['details'] = $details;
        }

        return response()->json(['error' => $error], $status);
    }
}
