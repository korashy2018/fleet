<?php

declare(strict_types=1);

namespace App\Infrastructure\Locking;

use Fleet\Application\Booking\LockUnavailable;
use Fleet\Domain\Booking\Port\SeatLock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;

final class RedisSeatLock implements SeatLock
{
    private const TTL_SECONDS = 15;

    private const WAIT_SECONDS = 5;

    public function __construct(
        private readonly int $ttlSeconds = self::TTL_SECONDS,
        private readonly int $waitSeconds = self::WAIT_SECONDS,
    ) {
    }

    public function acquire(int $tripId, int $seatNumber, callable $action): mixed
    {
        try {
            $lock = Cache::store('redis')->lock(
                sprintf('booking:%d:%d', $tripId, $seatNumber),
                $this->ttlSeconds,
            );
            $lock->block($this->waitSeconds);
        } catch (LockTimeoutException) {
            throw LockUnavailable::timeout($tripId, $seatNumber);
        } catch (\Throwable $e) {
            throw LockUnavailable::becauseRedisFailed($e);
        }

        try {
            return $action();
        } finally {
            try {
                $lock->release();
            } catch (\Throwable) {
                // Token release is best-effort; TTL still expires the key.
            }
        }
    }
}
