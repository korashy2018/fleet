<?php

declare(strict_types=1);

namespace Tests\Feature\Booking;

use App\Infrastructure\Locking\RedisSeatLock;
use Fleet\Application\Booking\LockUnavailable;
use Fleet\Domain\Booking\SeatLock;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RedisSeatLockTest extends TestCase
{
    #[Test]
    public function it_fails_closed_when_redis_cannot_be_reached(): void
    {
        Cache::shouldReceive('store')->once()->with('redis')->andThrow(new \RuntimeException('connection refused'));

        $this->expectException(LockUnavailable::class);

        $this->app->make(RedisSeatLock::class)->acquire(1, 5, static fn (): bool => true);
    }

    #[Test]
    public function the_container_binds_the_redis_lock(): void
    {
        $this->assertInstanceOf(RedisSeatLock::class, $this->app->make(SeatLock::class));
    }
}
